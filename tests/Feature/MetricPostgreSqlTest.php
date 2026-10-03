<?php

namespace Tests\Feature;

use App\Models\CollectionProduct;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetricPostgreSqlTest extends TestCase
{
    use DatabaseTransactions;

    public function test_average_analysis_duration_uses_postgresql_date_arithmetic(): void
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        CollectionProduct::query()->create([
            'analysis_start_date' => '2026-09-01',
            'analysis_end_date' => '2026-09-02',
        ]);
        CollectionProduct::query()->create([
            'analysis_start_date' => '2026-09-01',
            'analysis_end_date' => '2026-09-04',
        ]);

        $response = $this->actingAs($user)->get(route('metrics.index'));

        $response->assertOk();
        $this->assertSame('2 dias', data_get($response->viewData('page'), 'props.average_response_time'));
    }
}
