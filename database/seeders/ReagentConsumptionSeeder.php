<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReagentConsumptionSeeder extends Seeder
{
    public function run()
    {
        DB::table('reagent_consumption')->truncate();

        // Get the category_id for "Reagents"
        $reagentsCategoryId = DB::table('item_categories')->where('name', 'Reagentes')->value('id');

        // Get reagents from inventory
        $reagents = DB::table('i_items')
            ->where('category_id', 2)
            ->get(['id', 'name']);

        $startDate = Carbon::now()->subDays(60); // 60 days of data

        $data = [];

        for ($i = 0; $i < 60; $i++) {
            $date = $startDate->copy()->addDays($i);

            foreach ($reagents as $reagent) {
                $quantity = rand(50, 200) * (1 + (rand(-20, 20) / 100)); // Variation
                $data[] = [
                    'date' => $date,
                    'reagent_id' => $reagent->id,
                    'reagent_name' => $reagent->name,
                    'quantity_used' => round($quantity, 2),
                    'used_by' => null,
                    'used_at' => now()->subDays($i),
                    'remarks' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('reagent_consumption')->insert($data);
    }
}