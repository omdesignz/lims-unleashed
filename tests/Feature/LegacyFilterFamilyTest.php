<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LegacyFilterFamilyTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $admin->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $admin;
    }

    public function test_verified_admin_can_open_next_legacy_filter_family_pages(): void
    {
        $user = $this->verifiedAdmin();

        $routeNames = [
            'analysiscategories.index',
            'collectioncollaborations.index',
            'collectionendresults.index',
            'collectionreasons.index',
            'contactcategories.index',
            'currencies.index',
            'customerrequestcategories.index',
            'customerrequests.index',
            'discountcategories.index',
            'equipmentcategories.index',
            'exportcertificates.index',
            'faqanswers.index',
            'faqcategories.index',
            'faqs.index',
            'ideliveries.index',
            'iitems.index',
            'ilocations.index',
            'importcertificates.index',
            'invoicecategories.index',
            'itemcategories.index',
            'itransfers.index',
            'iwarehouses.index',
            'matrixes.index',
            'nwps.index',
            'products.index',
            'protocols.index',
            'taxexemptions.index',
            'taxtypes.index',
            'temperatures.index',
            'transportcategories.index',
            'vehicles.index',
            'warehouses.index',
        ];

        $checks = [
            route('boards'),
            route('boards', ['filter' => 'trashed']),
        ];

        foreach ($routeNames as $routeName) {
            $checks[] = route($routeName);
            $checks[] = route($routeName, ['filter' => 'trashed']);
        }

        $canonicalItemList = route('vap-inventory.items.index', ['inventory_type' => 'material']);
        $retiredItemLists = [route('iitems.index'), route('iitems.index', ['filter' => 'trashed'])];
        $failures = [];

        foreach ($checks as $url) {
            $response = $this->actingAs($user)->get($url);

            if (in_array($url, $retiredItemLists, true)) {
                $response->assertRedirect($canonicalItemList);
                $response = $this->get($canonicalItemList);
            }

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

    public function test_legacy_filter_handlers_do_not_assign_trashed_filter_values(): void
    {
        $failures = [];

        foreach (File::allFiles(app_path('Http/Controllers')) as $file) {
            foreach (file($file->getPathname()) ?: [] as $lineNumber => $line) {
                if (str_starts_with(trim($line), '//')) {
                    continue;
                }

                if (preg_match('/\$filter\s*=\s*[\'"]trashed[\'"]/', $line) === 1) {
                    $failures[] = sprintf('%s:%d still assigns the trashed filter.', $file->getRelativePathname(), $lineNumber + 1);
                }
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }
}
