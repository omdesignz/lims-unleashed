<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommercialDocumentCreatePageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authorized_user_can_open_every_commercial_document_create_page(): void
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => VAPLab::factory()->create()->id, 'user_id' => $user->id]);

        $pages = [
            'invoices.create' => 'Invoices/Create',
            'quotes.create' => 'Quotes/Create',
            'receipts.create' => 'Receipts/Create',
            'creditnotes.create' => 'CreditNotes/Create',
        ];

        foreach ($pages as $routeName => $component) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }
}
