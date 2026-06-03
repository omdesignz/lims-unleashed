<?php

namespace Tests\Feature;

use App\Models\CustomerRequest;
use App\Models\CustomerRequestCategory;
use App\Models\Profile;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortalCustomerServicesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function portalWarehouse(): Warehouse
    {
        $warehouse = Warehouse::query()
            ->whereNotNull('customer_id')
            ->firstOrFail();

        if (! $warehouse->email) {
            $warehouse->forceFill([
                'email' => 'portal.'.$warehouse->id.'@lims-unleashed.test',
            ])->save();
        }

        return $warehouse;
    }

    private function portalCategory(string $name): CustomerRequestCategory
    {
        return CustomerRequestCategory::query()->firstOrCreate(
            ['name' => $name],
            ['description' => $name.' criado pelos testes do portal']
        );
    }

    private function analysisProfile(): Profile
    {
        return Profile::query()->firstOrCreate(
            ['name' => 'Perfil Portal Smoke'],
            [
                'code' => 'PRT-'.Str::upper(Str::random(6)),
                'description' => 'Perfil criado para smoke tests do portal',
                'price' => 0,
                'category_id' => null,
            ]
        );
    }

    public function test_portal_pages_load_for_authenticated_customer(): void
    {
        $warehouse = $this->portalWarehouse();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClientPortal/Dashboard')
                ->has('charts.request_trend.categories')
                ->has('charts.request_trend.series', 3)
                ->where('charts.request_status.labels.0', 'Pendente')
                ->where('charts.asset_visibility.labels.0', 'Certificados')
            );

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.requests.index'))
            ->assertOk();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.services'))
            ->assertOk();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.profile'))
            ->assertOk();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.security'))
            ->assertOk();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.collections'))
            ->assertOk();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.invoices'))
            ->assertOk();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.receipts'))
            ->assertOk();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.contractguides'))
            ->assertOk();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.creditnotes'))
            ->assertOk();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.quotes'))
            ->assertOk();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.qualitycertificates'))
            ->assertOk();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.faqs'))
            ->assertOk();
    }

    public function test_portal_login_ignores_staff_intended_url_and_redirects_to_portal_home(): void
    {
        $warehouse = $this->portalWarehouse();

        $warehouse->forceFill([
            'email' => 'portal-login-'.$warehouse->id.'@lims-unleashed.test',
            'password' => Hash::make('PortalPassword123!'),
            'email_verified_at' => now(),
        ])->save();

        $this->withSession(['url.intended' => route('dashboard')])
            ->post(route('portal.login.store'), [
                'email' => $warehouse->email,
                'password' => 'PortalPassword123!',
                'remember' => 'on',
            ])
            ->assertRedirect(route('portal.home'));

        $this->assertAuthenticatedAs($warehouse, 'portal');
        $this->assertGuest('web');
    }

    public function test_portal_security_page_loads_for_authenticated_customer(): void
    {
        $warehouse = $this->portalWarehouse();

        $warehouse->forceFill([
            'password' => Hash::make('PortalPassword123!'),
            'email_verified_at' => now(),
        ])->save();

        DB::table('passkeys')->insert([
            'authenticatable_type' => Warehouse::class,
            'authenticatable_id' => $warehouse->getKey(),
            'name' => 'Touch ID do cliente',
            'credential_id' => 'portal-security-test-'.$warehouse->getKey(),
            'data' => json_encode(['id' => 'portal-security-test-'.$warehouse->getKey()]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.security'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClientPortal/Security')
                ->has('warehouse.data')
                ->has('passkeys')
                ->where('passkeys.0.name', 'Touch ID do cliente')
                ->where('security.has_password', true)
                ->where('security.email_verified', true)
                ->where('security.two_factor_enabled', false)
                ->where('security.passkey_count', 1)
            );
    }

    public function test_portal_customer_can_update_their_password(): void
    {
        $warehouse = $this->portalWarehouse();

        $warehouse->forceFill([
            'password' => Hash::make('OldPortalPassword123!'),
            'email_verified_at' => now(),
        ])->save();

        $this->actingAs($warehouse, 'portal')
            ->put(route('portal.user-password.update'), [
                'current_password' => 'OldPortalPassword123!',
                'password' => 'NewPortalPassword123!',
                'password_confirmation' => 'NewPortalPassword123!',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPortalPassword123!', $warehouse->fresh()->password));
    }

    public function test_portal_customer_can_terminate_other_browser_sessions(): void
    {
        $warehouse = $this->portalWarehouse();

        $warehouse->forceFill([
            'password' => Hash::make('PortalPassword123!'),
            'email_verified_at' => now(),
        ])->save();

        $this->actingAs($warehouse, 'portal')
            ->delete(route('portal.other-browser-sessions.destroy'), [
                'password' => 'PortalPassword123!',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'portal-other-browser-sessions-terminated');
    }

    public function test_portal_customer_can_submit_analysis_request(): void
    {
        $warehouse = $this->portalWarehouse();
        $category = $this->portalCategory('Análises');
        $profile = $this->analysisProfile();
        $title = 'Portal Analysis '.Str::upper(Str::random(6));

        $this->actingAs($warehouse, 'portal')
            ->post(route('portal.request.store'), [
                'request_type' => 'analysis_request',
                'title' => $title,
                'description' => 'Solicitamos análise microbiológica e físico-química desta amostra.',
                'email' => $warehouse->email,
                'contact' => $warehouse->primary_phone ?: '900000001',
                'priority' => 'high',
                'preferred_date' => now()->addDay()->toDateString(),
                'category_id' => $category->id,
                'details' => [
                    'sample_name' => 'Amostra Portal',
                    'matrix' => 'Água potável',
                    'requested_profiles' => [$profile->id],
                    'collection_required' => true,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $request = CustomerRequest::query()
            ->where('title', $title)
            ->latest('id')
            ->first();

        $this->assertNotNull($request, 'Expected the portal analysis request to be created.');
        $this->assertSame('analysis_request', $request->request_type);
        $this->assertSame('pending', $request->portal_status);
        $this->assertNotNull($request->reference);
        $this->assertSame([$profile->id], $request->extra_data['requested_profiles']);
    }

    public function test_portal_customer_can_submit_batch_analysis_request(): void
    {
        $warehouse = $this->portalWarehouse();
        $category = $this->portalCategory('Análises em lote');
        $profile = $this->analysisProfile();
        $title = 'Portal Batch Analysis '.Str::upper(Str::random(6));

        $this->actingAs($warehouse, 'portal')
            ->post(route('portal.request.store'), [
                'request_type' => 'analysis_request',
                'title' => $title,
                'description' => 'Pedido em lote para validação técnica com múltiplas amostras do mesmo cliente.',
                'email' => $warehouse->email,
                'contact' => $warehouse->primary_phone ?: '900000101',
                'priority' => 'normal',
                'preferred_date' => now()->addDays(2)->toDateString(),
                'category_id' => $category->id,
                'details' => [
                    'requested_profiles' => [$profile->id],
                    'collection_required' => false,
                    'samples' => [
                        [
                            'sample_name' => 'Água torre 01',
                            'product_name' => 'Água industrial',
                            'matrix' => 'Água',
                            'lot' => 'AT-001',
                            'packaging' => 'Frasco estéril',
                            'quantity' => '2 L',
                            'notes' => 'Colheita da linha norte',
                        ],
                        [
                            'sample_name' => 'Água torre 02',
                            'product_name' => 'Água industrial',
                            'matrix' => 'Água',
                            'lot' => 'AT-002',
                            'packaging' => 'Frasco estéril',
                            'quantity' => '2 L',
                            'notes' => 'Colheita da linha sul',
                        ],
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $request = CustomerRequest::query()
            ->where('title', $title)
            ->latest('id')
            ->first();

        $this->assertNotNull($request, 'Expected the portal batch analysis request to be created.');
        $this->assertSame('analysis_request', $request->request_type);
        $this->assertSame(2, $request->extra_data['sample_count']);
        $this->assertCount(2, $request->extra_data['samples']);
        $this->assertSame('Água torre 01', $request->extra_data['samples'][0]['sample_name']);
        $this->assertSame([$profile->id], $request->extra_data['requested_profiles']);
    }

    public function test_portal_customer_can_submit_collection_request(): void
    {
        $warehouse = $this->portalWarehouse();
        $category = $this->portalCategory('Colheitas');
        $title = 'Portal Collection '.Str::upper(Str::random(6));

        $this->actingAs($warehouse, 'portal')
            ->post(route('portal.request.store'), [
                'request_type' => 'collection_request',
                'title' => $title,
                'description' => 'Necessitamos de recolha no armazém principal na próxima janela disponível.',
                'email' => $warehouse->email,
                'contact' => $warehouse->primary_phone ?: '900000002',
                'priority' => 'normal',
                'preferred_date' => now()->addDays(2)->toDateString(),
                'category_id' => $category->id,
                'details' => [
                    'collection_location' => 'Armazém central',
                    'collection_address' => 'Rua Principal, Zona Industrial',
                    'collection_contact_name' => 'Operador Portal',
                    'collection_contact_phone' => '900000003',
                    'preferred_time_window' => '08:00 - 11:00',
                    'items' => [
                        ['name' => 'Amostra de leite', 'quantity' => 2, 'lot' => 'L-001'],
                        ['name' => 'Amostra de iogurte', 'quantity' => 1, 'lot' => 'L-002'],
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $request = CustomerRequest::query()
            ->where('title', $title)
            ->latest('id')
            ->first();

        $this->assertNotNull($request, 'Expected the portal collection request to be created.');
        $this->assertSame('collection_request', $request->request_type);
        $this->assertCount(2, $request->extra_data['items']);
        $this->assertSame('Armazém central', $request->extra_data['collection_location']);
    }

    public function test_portal_customer_export_and_duplicate_protection_work(): void
    {
        $warehouse = $this->portalWarehouse();
        $category = $this->portalCategory('Suporte Geral');
        $title = 'Portal Duplicate Guard '.Str::upper(Str::random(6));

        $payload = [
            'request_type' => 'general_support',
            'title' => $title,
            'description' => 'Precisamos de acompanhamento operacional para um pedido existente.',
            'email' => $warehouse->email,
            'contact' => $warehouse->primary_phone ?: '900000004',
            'priority' => 'normal',
            'preferred_date' => now()->addDay()->toDateString(),
            'category_id' => $category->id,
            'details' => [
                'document_reference' => 'DOC-123',
            ],
        ];

        $this->actingAs($warehouse, 'portal')
            ->post(route('portal.request.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($warehouse, 'portal')
            ->post(route('portal.request.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('duplicate_submission');

        $this->assertSame(
            1,
            CustomerRequest::query()->where('title', $title)->count(),
            'Expected duplicate portal request submissions to be blocked.'
        );

        $response = $this->actingAs($warehouse, 'portal')
            ->get(route('portal.request.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }

    public function test_language_can_be_swapped_from_the_portal_session(): void
    {
        $warehouse = $this->portalWarehouse();

        $response = $this->actingAs($warehouse, 'portal')
            ->post(route('language.store'), [
                'language' => 'en',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('language', 'en');
    }

    public function test_portal_faqs_support_category_filter(): void
    {
        $warehouse = $this->portalWarehouse();

        $this->actingAs($warehouse, 'portal')
            ->get(route('portal.faqs', [
                'category' => 999999,
            ]))
            ->assertOk();
    }
}
