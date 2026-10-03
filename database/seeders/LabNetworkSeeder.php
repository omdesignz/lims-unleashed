<?php

namespace Database\Seeders;

use App\Models\LabNetwork;
use App\Models\VAPLab;
use Illuminate\Database\Seeder;

class LabNetworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        $network = LabNetwork::factory()->create();
        $lab = VAPLab::factory()->create(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $lab->id]);
    }
}
