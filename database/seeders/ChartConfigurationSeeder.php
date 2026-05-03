<?php

// database/seeders/ChartConfigurationSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartConfiguration;

class ChartConfigurationSeeder extends Seeder
{
    public function run()
    {
        $chartConfigurations = [
            [
                'chart_type' => 'line',
                'default_settings' => [
                    'plugins' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Default Line Chart Title',
                            'color' => '#374151',
                            'font' => [
                                'size' => 18,
                                'weight' => 'bold',
                            ],
                        ],
                        'tooltip' => [
                            'enabled' => true,
                            'backgroundColor' => '#111827',
                            'titleColor' => '#FFFFFF',
                        ],
                    ],
                    'scales' => [
                        'y' => ['beginAtZero' => true],
                    ],
                ],
            ],
            // Add more configurations for other chart types (bar, doughnut, etc.)
        ];

        foreach ($chartConfigurations as $config) {
            ChartConfiguration::create($config);
        }
    }
}
