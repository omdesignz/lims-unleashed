<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->nullableMorphs('rater');
            $table->string('channel')->default('internal')->after('rater_id');
            $table->json('metadata')->nullable()->after('review');
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('rating_requests', function (Blueprint $table) {
            $table->nullableMorphs('rater');
            $table->string('channel')->default('internal')->after('rater_id');
        });

        $this->seedDefaultContinuousImprovementCriteria();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn(['rater_type', 'rater_id', 'channel', 'metadata']);
        });

        Schema::table('rating_requests', function (Blueprint $table) {
            $table->dropColumn(['rater_type', 'rater_id', 'channel']);
        });
    }

    private function seedDefaultContinuousImprovementCriteria(): void
    {
        $now = now();
        $types = [
            'service',
            'order',
            'proposal',
            'sample_entry',
            'customer_request',
            'quality_certificate',
            'maintenance_task',
            'paid_service',
        ];

        $criteria = [
            [
                'name' => 'Comunicação',
                'description' => 'Clareza, disponibilidade e acompanhamento durante o processo.',
            ],
            [
                'name' => 'Cumprimento de prazos',
                'description' => 'Adequação dos tempos de resposta, execução e entrega.',
            ],
            [
                'name' => 'Qualidade técnica',
                'description' => 'Confiança, rastreabilidade e qualidade percebida do serviço prestado.',
            ],
            [
                'name' => 'Experiência geral',
                'description' => 'Satisfação geral com o processo e facilidade de interação.',
            ],
        ];

        foreach ($types as $type) {
            foreach ($criteria as $criterion) {
                $exists = DB::table('criteria_rating')
                    ->where('type', $type)
                    ->where('name', $criterion['name'])
                    ->exists();

                if (! $exists) {
                    DB::table('criteria_rating')->insert([
                        'type' => $type,
                        'name' => $criterion['name'],
                        'description' => $criterion['description'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
};
