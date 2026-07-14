<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommercialDocumentCreatePageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authorized_user_can_open_every_commercial_document_create_page(): void
    {
        $user = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();

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
