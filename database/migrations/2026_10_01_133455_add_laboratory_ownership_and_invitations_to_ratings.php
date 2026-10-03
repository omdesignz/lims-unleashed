<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertEmpty();

        Schema::table('rating_requests', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->foreignId('issued_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('recipient_customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->uuid('invitation')->unique();
            $table->json('criteria_snapshot');
            $table->timestamp('expires_at');
            $table->string('rater_type')->nullable(false)->change();
            $table->unsignedBigInteger('rater_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['lab_id', 'status', 'created_at']);
            $table->unique(['id', 'lab_id', 'rateable_type', 'rateable_id', 'rater_type', 'rater_id', 'channel'], 'rating_requests_response_identity_unique');
        });

        Schema::table('ratings', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->foreignId('rating_request_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('rater_type')->nullable(false)->change();
            $table->unsignedBigInteger('rater_id')->nullable(false)->change();
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['lab_id', 'created_at']);
            $table->foreign(['rating_request_id', 'lab_id', 'rateable_type', 'rateable_id', 'rater_type', 'rater_id', 'channel'], 'ratings_invitation_identity_foreign')
                ->references(['id', 'lab_id', 'rateable_type', 'rateable_id', 'rater_type', 'rater_id', 'channel'])->on('rating_requests')->restrictOnDelete();
        });
        DB::statement("CREATE UNIQUE INDEX rating_requests_subject_recipient_unique ON rating_requests (lab_id, rateable_type, rateable_id, rater_type, rater_id, channel) WHERE status = 'pending' AND deleted_at IS NULL");
        DB::statement("CREATE UNIQUE INDEX ratings_subject_recipient_unique ON ratings (lab_id, rateable_type, rateable_id, rater_type, rater_id, channel) WHERE channel = 'internal'");
        DB::statement("ALTER TABLE ratings ADD CONSTRAINT ratings_portal_invitation_required CHECK (channel <> 'portal' OR rating_request_id IS NOT NULL)");
        DB::statement("ALTER TABLE rating_requests ADD CONSTRAINT rating_requests_portal_customer_required CHECK (channel <> 'portal' OR recipient_customer_id IS NOT NULL)");
    }

    public function down(): void
    {
        $this->assertEmpty();

        DB::statement('ALTER TABLE ratings DROP CONSTRAINT ratings_portal_invitation_required');
        DB::statement('ALTER TABLE rating_requests DROP CONSTRAINT rating_requests_portal_customer_required');
        DB::statement('DROP INDEX ratings_subject_recipient_unique');
        DB::statement('DROP INDEX rating_requests_subject_recipient_unique');

        Schema::table('ratings', function (Blueprint $table): void {
            $table->dropForeign('ratings_invitation_identity_foreign');
            $table->dropIndex(['lab_id', 'created_at']);
            $table->dropUnique(['rating_request_id']);
            $table->dropConstrainedForeignId('rating_request_id');
            $table->dropConstrainedForeignId('lab_id');
            $table->string('rater_type')->nullable()->change();
            $table->unsignedBigInteger('rater_id')->nullable()->change();
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('rating_requests', function (Blueprint $table): void {
            $table->dropUnique('rating_requests_response_identity_unique');
            $table->dropIndex(['lab_id', 'status', 'created_at']);
            $table->dropUnique(['invitation']);
            $table->dropForeign(['user_id']);
            $table->dropConstrainedForeignId('issued_by_id');
            $table->dropConstrainedForeignId('recipient_customer_id');
            $table->dropConstrainedForeignId('lab_id');
            $table->dropColumn(['invitation', 'criteria_snapshot', 'expires_at']);
            $table->string('rater_type')->nullable()->change();
            $table->unsignedBigInteger('rater_id')->nullable()->change();
        });
    }

    private function assertEmpty(): void
    {
        if (DB::table('ratings')->exists() || DB::table('rating_requests')->exists()) {
            throw new RuntimeException('Assign explicit laboratory and recipient ownership to retained surveys before changing the schema.');
        }
    }
};
