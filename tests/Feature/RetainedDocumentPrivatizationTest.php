<?php

namespace Tests\Feature;

use App\Models\GestlabMedia;
use App\Models\InventoryItemDocumentMedia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\IsolatedPostgresTestCase;

class RetainedDocumentPrivatizationTest extends IsolatedPostgresTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_legacy_original_conversion_generic_and_signature_bytes_are_preserved_and_public_copies_removed(): void
    {
        $media = InventoryItemDocumentMedia::factory()->create();
        $path = $media->getPathRelativeToRoot();
        Storage::disk('public')->put($path, 'retained catalogue evidence');
        $conversion = dirname($path).'/conversions/preview.png';
        Storage::disk('public')->put($conversion, 'retained conversion');
        $generic = GestlabMedia::query()->create(['name' => 'Retained PDF', 'file_name' => 'private.pdf', 'mime_type' => 'application/pdf', 'size' => 9, 'disk' => 'public']);
        Storage::disk('public')->put($generic->path, 'retained generic evidence');
        $signature = User::factory()->create()->addMedia(UploadedFile::fake()->image('signature.png'))->toMediaCollection('signature', 'public');
        $signatureBytes = Storage::disk('public')->get($signature->getPathRelativeToRoot());
        $before = $media->fresh()->getRawOriginal();
        $this->assertSame(0, $this->runCommand(), Artisan::output());
        $stored = $media->fresh()->getRawOriginal();
        foreach (array_diff(array_keys($before), ['disk', 'conversions_disk', 'updated_at']) as $field) {
            $this->assertSame($before[$field], $stored[$field], $field);
        }
        $this->assertSame('local', $stored['disk']);
        $this->assertSame('local', $generic->fresh()->disk);
        $this->assertSame('local', $signature->fresh()->disk);
        foreach ([$path => 'retained catalogue evidence', $conversion => 'retained conversion', $generic->path => 'retained generic evidence', $signature->getPathRelativeToRoot() => $signatureBytes] as $file => $bytes) {
            $this->assertSame($bytes, Storage::disk('local')->get($file));
            Storage::disk('public')->assertMissing($file);
        }
        $snapshot = DB::table('media')->orderBy('id')->get()->toArray();
        $this->assertSame(0, $this->runCommand(), Artisan::output());
        $this->assertEquals($snapshot, DB::table('media')->orderBy('id')->get()->toArray());
        $this->assertStringNotContainsString('/storage/', $media->getUrl());
    }

    public function test_wrong_target_and_conflicting_private_copy_never_delete_or_replace_evidence(): void
    {
        $media = InventoryItemDocumentMedia::factory()->create();
        $path = $media->getPathRelativeToRoot();
        Storage::disk('public')->put($path, 'public original');
        $before = $media->fresh()->getRawOriginal();
        $this->assertSame(1, Artisan::call('app:privatize-documents', ['--database' => 'lims_unleashed', '--no-interaction' => true]));
        Storage::disk('local')->assertMissing($path);
        $this->assertSame($before, $media->fresh()->getRawOriginal());
        Storage::disk('local')->put($path, 'different retained evidence');
        $this->assertSame(1, $this->runCommand());
        $this->assertSame('public original', Storage::disk('public')->get($path));
        $this->assertSame('different retained evidence', Storage::disk('local')->get($path));
        $this->assertSame($before, $media->fresh()->getRawOriginal());
    }

    public function test_cancelled_metadata_save_keeps_both_copies_and_retry_completes_safely(): void
    {
        $media = InventoryItemDocumentMedia::factory()->create();
        $path = $media->getPathRelativeToRoot();
        Storage::disk('public')->put($path, 'original bytes');
        $before = $media->fresh()->getRawOriginal();
        $cancel = true;
        InventoryItemDocumentMedia::saving(function () use (&$cancel): ?bool {
            return $cancel ? false : null;
        });
        $this->assertSame(1, $this->runCommand());
        $this->assertSame($before, $media->fresh()->getRawOriginal());
        $this->assertSame('original bytes', Storage::disk('public')->get($path));
        $this->assertSame('original bytes', Storage::disk('local')->get($path));
        $cancel = false;
        $this->assertSame(0, $this->runCommand(), Artisan::output());
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('original bytes', Storage::disk('local')->get($path));
    }

    public function test_latest_migration_replays_and_rollback_refuses_to_discard_retained_private_state(): void
    {
        $migration = require database_path('migrations/2026_10_03_072531_protect_retained_private_media.php');
        $this->assertTrue(Schema::hasColumn('media', 'deleted_at'));
        $migration->down();
        $this->assertFalse(Schema::hasColumn('media', 'deleted_at'));
        $migration->up();
        $this->assertTrue(Schema::hasColumn('gestlab_media', 'disk'));
        $media = InventoryItemDocumentMedia::factory()->create(['deleted_at' => now()]);
        $before = $media->fresh()->getRawOriginal();
        try {
            $migration->down();
            $this->fail('Rollback must preserve retained archive state.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('retained', $exception->getMessage());
        }
        $this->assertSame($before, $media->fresh()->getRawOriginal());
        $this->assertTrue(Schema::hasColumn('media', 'deleted_at'));
    }

    public function test_malformed_legacy_filename_never_copies_or_deletes_unrelated_public_files(): void
    {
        $media = InventoryItemDocumentMedia::factory()->create(['file_name' => '']);
        Storage::disk('public')->put('unrelated/keep.pdf', 'unrelated retained bytes');
        $before = $media->fresh()->getRawOriginal();
        $this->assertSame(1, $this->runCommand());
        $this->assertSame($before, $media->fresh()->getRawOriginal());
        $this->assertSame('unrelated retained bytes', Storage::disk('public')->get('unrelated/keep.pdf'));
        Storage::disk('local')->assertMissing('unrelated/keep.pdf');
    }

    private function runCommand(): int
    {
        return Artisan::call('app:privatize-documents', ['--database' => DB::connection()->getDatabaseName(), '--no-interaction' => true]);
    }
}
