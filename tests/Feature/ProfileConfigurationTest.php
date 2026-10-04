<?php

namespace Tests\Feature;

use App\Models\AnalysisCategory;
use App\Models\Department;
use App\Models\Parameter;
use App\Models\Profile;
use App\Models\ResultCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileConfigurationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($user);
    }

    public function test_profile_configuration_can_be_created_edited_and_found_on_postgresql(): void
    {
        $payload = $this->profilePayload();

        $this->from(route('profiles.create'))->post(route('profiles.store'), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $profile = Profile::query()->where('code', 'PROFILE-CONFIG')->firstOrFail();
        $this->assertSame('mg/L', $profile->parameters->first()->pivot->unit_label);
        $this->assertSame(42.5, $profile->price_based_on_parameters);
        $this->get(route('profiles.edit', $profile))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Profiles/Edit')->where('record.parameters.0.optimal_analysis_time', '24 h'));
        $lookup = $this->getJson(route('profiles.getProfile', ['q' => $profile->code]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.active_parameter_count', 1);
        $this->assertSame(42.5, (float) $lookup->json('0.parameters_price'));

        $payload['parameters'][0]['count'] = false;
        $payload['parameters'][0]['ref_val_origin'] = 'Updated procedure';
        $this->put(route('profiles.update', $profile), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(0.0, $profile->fresh()->price_based_on_parameters);
        $this->assertSame('Updated procedure', $profile->fresh()->parameters->first()->pivot->ref_val_origin);
        $this->assertFalse($profile->fresh()->parameters->first()->pivot->accredited);
        $this->assertNull($profile->fresh()->parameters->first()->pivot->uncertainty_coverage_factor);

        $payload['parameters'][0]['accredited'] = true;
        $payload['parameters'][0]['subcontractor'] = '  Laboratório externo  ';
        $payload['parameters'][0]['uncertainty_coverage_factor'] = '1,96';
        $this->put(route('profiles.update', $profile), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $pivot = $profile->fresh()->parameters->first()->pivot;
        $this->assertTrue($pivot->accredited);
        $this->assertSame('Laboratório externo', $pivot->subcontractor);
        $this->assertSame('1.96', $pivot->uncertainty_coverage_factor);
        $this->get(route('profiles.edit', $profile))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('record.parameters.0.accredited', true)
            ->where('record.parameters.0.subcontractor', 'Laboratório externo')
            ->where('record.parameters.0.uncertainty_coverage_factor', '1.96'));

        $payload['parameters'][0]['uncertainty_coverage_factor'] = '0.5';
        $this->put(route('profiles.update', $profile), $payload)->assertSessionHasErrors('parameters.0.uncertainty_coverage_factor');
        $lookup = $this->getJson(route('profiles.getProfile', ['q' => $profile->code]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.total_parameter_count', 1);
        $this->assertSame(0.0, (float) $lookup->json('0.parameters_price'));
    }

    public function test_profile_update_replaces_parameter_selection_and_preserves_other_profiles(): void
    {
        $payload = $this->profilePayload();
        $this->post(route('profiles.store'), $payload)->assertSessionHasNoErrors();
        $profile = Profile::query()->where('code', $payload['code'])->firstOrFail();
        $originalParameterId = $payload['parameters'][0]['parameter_id']['value'];
        $otherProfile = Profile::query()->create(['name' => 'Other profile']);
        $otherProfile->parameters()->attach($originalParameterId);
        $replacement = Parameter::query()->create(['name' => 'Replacement parameter', 'price' => 15, 'active' => true]);
        $payload['parameters'][0]['parameter_id'] = ['value' => $replacement->id, 'label' => $replacement->name];

        $this->put(route('profiles.update', $profile), $payload)->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame([$replacement->id], $profile->fresh()->parameters->modelKeys());
        $this->assertSame(15.0, $profile->fresh()->price_based_on_parameters);
        $this->assertSame([$originalParameterId], $otherProfile->fresh()->parameters->modelKeys());
        $this->assertDatabaseMissing('parameter_profile', ['profile_id' => $profile->id, 'parameter_id' => $originalParameterId]);
    }

    public function test_invalid_configuration_references_return_validation_errors_without_partial_writes(): void
    {
        $payload = $this->profilePayload();
        $this->post(route('profiles.store'), $payload)->assertSessionHasNoErrors();
        $profile = Profile::query()->where('code', $payload['code'])->firstOrFail();
        $originalUnitId = $profile->parameters->first()->pivot->unit_id;
        $fields = ['unit_id', 'protocol_id', 'nwp_id', 'standard_id'];
        foreach ($fields as $field) {
            $payload['parameters'][0][$field] = ['value' => -1, 'label' => 'Missing reference'];
        }
        $payload['name'] = 'Must not be saved';
        $errors = array_map(fn (string $field): string => 'parameters.0.'.$field, $fields);

        $this->putJson(route('profiles.update', $profile), $payload)->assertUnprocessable()->assertJsonValidationErrors($errors);
        $this->assertNotSame($payload['name'], $profile->fresh()->name);
        $this->assertSame($originalUnitId, $profile->fresh()->parameters->first()->pivot->unit_id);

        $payload['code'] = 'INVALID-PROFILE';
        $this->postJson(route('profiles.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors($errors);
        $this->assertDatabaseMissing('profiles', ['code' => $payload['code']]);
    }

    public function test_optional_selectors_can_be_cleared_but_duplicate_parameters_are_rejected(): void
    {
        $payload = $this->profilePayload();
        foreach (['protocol_id', 'nwp_id', 'standard_id', 'formula_id'] as $field) {
            $payload['parameters'][0][$field] = ['value' => null, 'label' => ''];
        }
        $this->post(route('profiles.store'), $payload)->assertSessionHasNoErrors();
        $profile = Profile::query()->where('code', $payload['code'])->firstOrFail();
        $pivot = $profile->parameters->first()->pivot;
        foreach (['protocol', 'nwp', 'standard', 'formula'] as $field) {
            $this->assertNull($pivot->{$field.'_id'});
            $this->assertNull($pivot->{$field.'_label'});
        }

        $payload['parameters'][] = $payload['parameters'][0];
        $this->putJson(route('profiles.update', $profile), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('parameters');
        $this->assertCount(1, $profile->fresh()->parameters);
    }

    public function test_profile_codes_remain_unique_when_creating_and_updating(): void
    {
        $payload = $this->profilePayload();
        $this->post(route('profiles.store'), $payload)->assertSessionHasNoErrors();
        $profile = Profile::query()->where('code', $payload['code'])->firstOrFail();

        $this->postJson(route('profiles.store'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('code');

        $otherProfile = Profile::query()->create(['name' => 'Other profile', 'code' => 'OTHER-PROFILE']);
        $payload['code'] = $otherProfile->code;
        $this->putJson(route('profiles.update', $profile), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->assertSame('PROFILE-CONFIG', $profile->fresh()->code);
    }

    public function test_profile_lookup_excludes_inactive_deleted_and_uncounted_parameters_from_price(): void
    {
        $payload = $this->profilePayload();
        $this->post(route('profiles.store'), $payload)->assertSessionHasNoErrors();
        $profile = Profile::query()->where('code', $payload['code'])->firstOrFail();
        foreach ([['active' => false], ['active' => true], ['active' => true], ['active' => true]] as $index => $attributes) {
            $parameter = Parameter::query()->create(['name' => 'Excluded parameter '.$index, 'price' => 100, ...$attributes]);
            $profile->parameters()->attach($parameter->id, ['count' => $index !== 2]);
            if ($index === 1) {
                $parameter->delete();
            }
            if ($index === 3) {
                DB::table('parameter_profile')->where('profile_id', $profile->id)
                    ->where('parameter_id', $parameter->id)->update(['deleted_at' => now()]);
            }
        }

        $lookup = $this->getJson(route('profiles.getProfile', ['q' => $profile->code]))
            ->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.total_parameter_count', 4)
            ->assertJsonPath('0.active_parameter_count', 2);
        $this->assertSame(42.5, (float) $lookup->json('0.parameters_price'));

        $profile->delete();
        $this->getJson(route('profiles.getProfile', ['q' => $profile->code]))->assertOk()->assertJsonCount(0);
    }

    public function test_configuration_migration_preserves_legacy_labels_in_both_directions(): void
    {
        $migration = require database_path('migrations/2026_09_27_155434_align_parameter_profile_configuration_fields.php');
        $migration->down();
        $id = DB::table('parameter_profile')->insertGetId([
            'unit' => 'mg/L', 'standard' => 'ISO legacy', 'nwp' => 'NWP-1', 'category' => 'Numeric', 'method' => 'Legacy method',
        ]);
        $migration->up();
        $row = DB::table('parameter_profile')->find($id);
        $this->assertSame('mg/L', $row->unit_label);
        $this->assertSame('ISO legacy', $row->standard_label);
        $this->assertSame('NWP-1', $row->nwp_label);
        $this->assertSame('Numeric', $row->category_label);
        $this->assertSame('Legacy method', $row->method);
        $this->assertNull($row->formula_label);
        $migration->down();
        $this->assertSame('mg/L', DB::table('parameter_profile')->find($id)->unit);
        $migration->up();
    }

    private function profilePayload(): array
    {
        $category = AnalysisCategory::query()->create(['name' => 'Chemistry', 'department_id' => Department::factory()->create()->id]);
        $resultCategory = ResultCategory::query()->create(['name' => 'Numeric result']);
        $unit = Unit::query()->create(['code' => 'mg/L']);
        $parameter = Parameter::query()->create(['name' => 'Profile test parameter', 'price' => 42.5, 'active' => true]);

        return [
            'name' => 'Profile configuration regression', 'code' => 'PROFILE-CONFIG',
            'category_id' => ['value' => $category->id, 'label' => $category->name],
            'parameters' => [[
                'parameter_id' => ['value' => $parameter->id, 'label' => $parameter->name],
                'unit_id' => ['value' => $unit->id, 'label' => $unit->code],
                'category_id' => ['value' => $resultCategory->id, 'label' => $resultCategory->name],
                'min_ref_value' => '0', 'max_ref_value' => '10', 'count' => true,
                'optimal_analysis_time' => '24 h', 'ref_val_origin' => 'Validated procedure',
                'extra_data' => ['temperature' => '20 C'],
            ]],
        ];
    }
}
