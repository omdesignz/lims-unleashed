<?php

namespace Tests\Feature;

use App\Models\GestlabMedia;
use App\Models\InventoryItem;
use App\Models\InventoryItemDocumentMedia;
use App\Models\ISOActivityLog;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Support\ReportStudioAssetLibrary;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PrivateDocumentAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_new_documents_are_private_and_authorized_downloads_include_retained_archives(): void
    {
        [$lab, $user, $item, $document] = $this->fixture();
        $this->assertSame('local', $document->disk);
        $path = $document->getPathRelativeToRoot();
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame(route('vap-inventory.items.attachments.download-single', ['model_id' => $document->id]), $document->getUrl());
        $this->get($document->getUrl())->assertOk()->assertDownload('private.pdf');
        $before = Storage::disk('local')->get($path);
        $this->delete(route('vap-inventory.items.attachments.delete', $document), ['model_id' => $item->id])->assertRedirect();
        $this->assertSoftDeleted($document);
        $row = $this->row($document->id);
        $count = ISOActivityLog::query()->where('subject_id', $item->id)->where('subject_type', $item->getMorphClass())->count();
        $this->delete(route('vap-inventory.items.attachments.delete', $document), ['model_id' => $item->id])->assertRedirect();
        $this->assertSame($row, $this->row($document->id));
        $this->assertSame($count, ISOActivityLog::query()->where('subject_id', $item->id)->where('subject_type', $item->getMorphClass())->count());
        $this->get($document->getUrl())->assertOk()->assertDownload('private.pdf');
        $this->patch(route('vap-inventory.items.attachments.restore', $document), ['model_id' => $item->id])->assertRedirect();
        $restored = $this->row($document->id);
        $this->assertNull($restored['deleted_at']);
        $this->patch(route('vap-inventory.items.attachments.restore', $document), ['model_id' => $item->id])->assertRedirect();
        $this->assertSame($restored, $this->row($document->id));
        $this->assertSame($before, Storage::disk('local')->get($path));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_peer_switched_removed_and_kind_only_actors_cannot_access_documents(): void
    {
        [$lab, $user, $item, $document] = $this->fixture();
        $peer = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $peer->id])->get($document->getUrl())->assertNotFound();
        $this->deleteJson(route('vap-inventory.items.attachments.delete', $document), ['model_id' => $item->id])->assertNotFound();
        $this->withSession(['active_lab_id' => $lab->id]);
        $user->revokePermissionTo('view_iitems');
        $user->givePermissionTo(Permission::findOrCreate('view_iequipments', 'web'));
        $this->get($document->getUrl())->assertForbidden();
        $user->givePermissionTo(Permission::findOrCreate('view_iitems', 'web'));
        DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete();
        $this->get($document->getUrl())->assertNotFound();
        $this->patchJson(route('vap-inventory.items.attachments.restore', $document), ['model_id' => $item->id])->assertNotFound();
        $this->assertNull($this->row($document->id)['deleted_at']);
        Storage::disk('local')->assertExists($document->getPathRelativeToRoot());
        auth()->logout();
        $this->get($document->getUrl())->assertRedirect(route('login'));
    }

    #[DataProvider('lifecycleFaults')]
    public function test_lifecycle_failures_roll_back_metadata_and_history_without_losing_bytes(string $operation, string $fault): void
    {
        [$lab, $user, $item, $document] = $this->fixture();
        if ($operation === 'restore') {
            DB::table('media')->where('id', $document->id)->update(['deleted_at' => now()]);
        }
        $before = $this->row($document->id);
        $history = DB::table('activity_log')->orderBy('id')->get()->toArray();
        if ($fault === 'audit') {
            ISOActivityLog::creating(fn (): bool => false);
        } else {
            $event = $operation === 'archive' ? 'deleting' : 'restoring';
            InventoryItemDocumentMedia::$event(function (InventoryItemDocumentMedia $media) use ($fault, $lab, $user): ?bool {
                if ($fault === 'veto') {
                    return false;
                }
                if ($fault === 'metadata') {
                    DB::table('media')->where('id', $media->id)->update(['file_name' => 'forged.pdf']);
                } else {
                    DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete();
                }

                return null;
            });
        }
        $route = route('vap-inventory.items.attachments.'.($operation === 'archive' ? 'delete' : 'restore'), $document);
        $response = $operation === 'archive' ? $this->deleteJson($route, ['model_id' => $item->id])
            : $this->patchJson($route, ['model_id' => $item->id]);
        $response->assertStatus($fault === 'membership' ? 403 : 409);
        $this->assertSame($before, $this->row($document->id));
        $this->assertEquals($history, DB::table('activity_log')->orderBy('id')->get()->toArray());
        $this->assertDatabaseHas('lab_user', ['lab_id' => $lab->id, 'user_id' => $user->id]);
        Storage::disk('local')->assertExists($document->getPathRelativeToRoot());
        Storage::disk('public')->assertMissing($document->getPathRelativeToRoot());
    }

    public static function lifecycleFaults(): array
    {
        $cases = [];
        foreach (['archive', 'restore'] as $operation) {
            foreach (['veto', 'metadata', 'membership', 'audit'] as $fault) {
                $cases[$operation.' '.$fault] = [$operation, $fault];
            }
        }

        return $cases;
    }

    public function test_unowned_library_is_admin_quarantine_and_personal_signatures_are_not_shared_assets(): void
    {
        [$lab, $user] = $this->fixture();
        $legacy = GestlabMedia::query()->create(['name' => 'Private legacy PDF', 'file_name' => 'private.pdf', 'mime_type' => 'application/pdf', 'size' => 7, 'disk' => 'local']);
        Storage::disk('local')->put($legacy->path, 'retained');
        $this->get($legacy->url)->assertForbidden();
        $this->get(route('media.index'))->assertForbidden();
        $this->postJson(route('media.store'), ['file' => UploadedFile::fake()->image('image.png')])->assertForbidden();
        $signature = $user->addMedia(UploadedFile::fake()->image('signature.png'))->toMediaCollection('signature');
        $user->unsetRelation('media');
        $this->assertSame('local', $signature->disk);
        $this->get($user->signature_url)->assertOk();
        $export = $user->addMedia(UploadedFile::fake()->createWithContent('export.pdf', "%PDF-1.4\nretained export\n%%EOF"))->toMediaCollection('exports');
        $this->assertSame('local', $export->disk);
        $this->get(route('users.private-media', $export->id))->assertForbidden();
        $other = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        $this->actingAs($other)->get($user->signature_url)->assertForbidden();
        $this->assertSame([], app(ReportStudioAssetLibrary::class)->assets());
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin)->get($legacy->url)->assertOk()->assertDownload('private.pdf');
        $this->get($user->signature_url)->assertOk();
        $this->get(route('users.private-media', $export->id))->assertOk()->assertDownload('export.pdf');
        $this->assertNotContains('signature-'.$user->id, array_column(app(ReportStudioAssetLibrary::class)->assets(), 'id'));
        $this->postJson(route('media.store'), ['file' => UploadedFile::fake()->create('operational.pdf', 1, 'application/pdf')])->assertUnprocessable();
        $this->get('/media/destroy?recordIds[]='.$legacy->id)->assertNotFound();
        $this->deleteJson('/media/destroy', ['recordIds' => [$legacy->id]])->assertNotFound();
        Storage::disk('local')->assertExists($legacy->path);
        Storage::disk('public')->assertMissing($legacy->path);
    }

    public function test_legacy_kind_attachment_aliases_use_canonical_retention(): void
    {
        foreach (['material' => 'iitems', 'equipment' => 'iequipments'] as $type => $prefix) {
            [$lab, $user, $item, $document] = $this->fixture($type);
            $path = $document->getPathRelativeToRoot();
            $bytes = Storage::disk('local')->get($path);
            $this->delete(route($prefix.'.delete-attachment'), ['model_id' => $item->id, 'id' => $document->id])->assertRedirect();
            $this->assertSoftDeleted($document);
            $this->assertSame($bytes, Storage::disk('local')->get($path));
            $this->assertDatabaseHas('activity_log', ['subject_type' => $item->getMorphClass(), 'subject_id' => $item->id, 'event' => 'document_archived']);
        }
    }

    private function fixture(string $type = 'material'): array
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        $prefix = $type === 'equipment' ? 'iequipments' : 'iitems';
        foreach (['view_'.$prefix, 'edit_'.$prefix] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $category = ItemCategory::query()->create(['name' => fake()->uuid(), 'inventory_type' => $type]);
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Private item', 'category_id' => $category->id]);
        $document = $item->addMedia(UploadedFile::fake()->create('private.pdf', 2, 'application/pdf'))->toMediaCollection('documents');
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return [$lab, $user, $item, $document];
    }

    private function row(int $id): array
    {
        return (array) DB::table('media')->where('id', $id)->first();
    }
}
