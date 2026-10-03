<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyControllerAuditTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $admin->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $admin;
    }

    public function test_verified_admin_can_open_legacy_master_data_pages(): void
    {
        $user = $this->verifiedAdmin();

        $checks = [
            route('countries.index'),
            route('paymentcategories.index'),
            route('standards.index'),
            route('paidservices.index'),
            route('itypes.index'),
            route('itypes.create'),
            route('iorders.index'),
            route('phytosanitary_products.index'),
            route('phytosanitary_products.create'),
            route('users.create'),
            route('units.create'),
            route('iunits.index'),
            route('iunits.create'),
        ];

        $failures = [];

        foreach ($checks as $url) {
            $response = $this->actingAs($user)->get($url);

            if (! $response->isSuccessful()) {
                $failures[] = sprintf(
                    'Expected [%s] to load successfully, got HTTP %d.',
                    $url,
                    $response->getStatusCode()
                );
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_missing_quality_certificate_show_returns_not_found_instead_of_null_page(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->get(route('qualitycertificates.show', ['certificate' => 999999999]))
            ->assertNotFound();
    }

    public function test_missing_inventory_attachment_deletes_return_not_found_instead_of_server_errors(): void
    {
        $user = $this->verifiedAdmin();
        $category = ItemCategory::query()->create(['name' => 'Attachment audit '.fake()->uuid()]);
        $item = InventoryItem::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Attachment audit item',
            'code' => fake()->unique()->bothify('ATT-#######'),
            'category_id' => $category->id,
        ]);
        $missingMediaId = 999999999;

        foreach ([
            route('iitems.delete-attachment', ['model_id' => $item->id, 'id' => $missingMediaId]),
            route('iequipments.delete-attachment', ['model_id' => $item->id, 'id' => $missingMediaId]),
            route('vap-inventory.items.attachments.delete', ['id' => $missingMediaId, 'model_id' => $item->id]),
        ] as $url) {
            $this->actingAs($user)
                ->delete($url)
                ->assertNotFound();
        }
    }
}
