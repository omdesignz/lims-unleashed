<?php

namespace Tests\Feature;

use App\Models\Formula;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FormulaManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for formula testing.');

        return $admin;
    }

    public function test_admin_can_create_and_update_a_formula_with_structured_variables(): void
    {
        $user = $this->verifiedAdmin();

        $payload = [
            'name' => 'Moisture Content Validation Formula',
            'code' => 'moisture_content_validation_formula',
            'category' => 'physicochemical',
            'decimal_places' => 2,
            'is_active' => true,
            'output_unit' => '%',
            'description' => 'Production formula smoke test',
            'expression' => '((mp + ma) - mp_a) * 100 / ma',
            'formula_expression' => '(({mp} + {ma}) - {mp_a}) * 100 / {ma}',
            'variables' => [
                ['name' => 'mp', 'label' => 'Mass before drying', 'type' => 'number', 'unit' => 'g', 'value' => 44.35],
                ['name' => 'ma', 'label' => 'Sample mass', 'type' => 'number', 'unit' => 'g', 'value' => 5.00],
                ['name' => 'mp_a', 'label' => 'Mass after drying', 'type' => 'number', 'unit' => 'g', 'value' => 48.77],
            ],
        ];

        $this->actingAs($user)
            ->post(route('formulas.store'), $payload)
            ->assertRedirect(route('formulas.index'));

        $formula = Formula::query()->where('code', $payload['code'])->first();

        $this->assertNotNull($formula, 'Expected the created formula to exist.');
        $this->assertSame($user->id, $formula->created_by);
        $this->assertCount(3, $formula->variables ?? []);

        $this->actingAs($user)
            ->put(route('formulas.update', ['formula' => $formula->id]), array_merge($payload, [
                'name' => 'Moisture Content Validation Formula Updated',
                'decimal_places' => 3,
                'variables' => array_merge($payload['variables'], [
                    ['name' => 'blank_factor', 'label' => 'Blank factor', 'type' => 'number', 'unit' => '', 'value' => 1],
                ]),
            ]))
            ->assertRedirect();

        $formula->refresh();

        $this->assertSame('Moisture Content Validation Formula Updated', $formula->name);
        $this->assertSame(3, $formula->decimal_places);
        $this->assertCount(4, $formula->variables ?? []);
    }

    public function test_formula_creation_rejects_unsafe_or_incomplete_variable_definitions(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->from(route('formulas.create'))
            ->post(route('formulas.store'), [
                'name' => 'Unsafe Formula',
                'code' => 'unsafe_formula',
                'category' => 'custom',
                'decimal_places' => 2,
                'is_active' => true,
                'output_unit' => 'mg/L',
                'expression' => 'exec(a)',
                'formula_expression' => 'exec({a}) + {missing_variable}',
                'variables' => [
                    ['name' => 'a', 'label' => 'Input A', 'type' => 'number', 'unit' => 'mg/L', 'value' => 2],
                ],
            ])
            ->assertRedirect(route('formulas.create'))
            ->assertSessionHasErrors(['formula_expression', 'variables']);
    }
}
