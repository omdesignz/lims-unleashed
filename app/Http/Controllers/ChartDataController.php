<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChartDataController extends Controller
{
    public function getChartData($chartType)
    {
        // Example data structure; customize based on chartType
        $data = [];

        switch ($chartType) {
            case 'line':
                $data = [
                    'labels' => ['January', 'February', 'March', 'April'],
                    'datasets' => [
                        [
                            'label' => 'Sales',
                            'data' => [10, 20, 15, 30],
                            'backgroundColor' => '#3B82F6',
                        ],
                    ],
                    'options' => [
                        'plugins' => [
                            'title' => [
                                'display' => true,
                                'text' => 'Sales Over Time',
                                'color' => '#374151', // Tailwind gray-700
                                'font' => [
                                    'size' => 18,
                                    'weight' => 'bold',
                                ],
                            ],
                            'subtitle' => [
                                'display' => true,
                                'text' => 'Quarterly Sales Performance',
                                'color' => '#6B7280', // Tailwind gray-500
                                'font' => [
                                    'size' => 14,
                                ],
                            ],
                            'tooltip' => [
                                'enabled' => true,
                                'backgroundColor' => '#111827', // Tailwind gray-900
                                'titleColor' => '#FFFFFF',
                                'bodyColor' => '#FFFFFF',
                            ],
                        ],
                        'scales' => [
                            'y' => ['beginAtZero' => true],
                        ],
                    ],
                ];
                break;
                
            case 'bar':
                $data = [
                    'labels' => ['Red', 'Blue', 'Yellow', 'Green'],
                    'datasets' => [
                        [
                            'label' => 'Votes',
                            'data' => [12, 19, 3, 5],
                            'backgroundColor' => '#3B82F6',
                        ],
                    ],
                    'options' => [
                        'plugins' => [
                            'title' => [
                                'display' => true,
                                'text' => 'Sales Over Time',
                                'color' => '#374151', // Tailwind gray-700
                                'font' => [
                                    'size' => 18,
                                    'weight' => 'bold',
                                ],
                            ],
                            'subtitle' => [
                                'display' => true,
                                'text' => 'Quarterly Sales Performance',
                                'color' => '#6B7280', // Tailwind gray-500
                                'font' => [
                                    'size' => 14,
                                ],
                            ],
                            'tooltip' => [
                                'enabled' => true,
                                'backgroundColor' => '#111827', // Tailwind gray-900
                                'titleColor' => '#FFFFFF',
                                'bodyColor' => '#FFFFFF',
                            ],
                        ],
                        'scales' => [
                            'y' => ['beginAtZero' => true],
                        ],
                    ],
                ];
                break;
                
            // Add cases for other chart types as needed

            default:
                return response()->json(['error' => 'Invalid chart type'], 400);
        }

        return response()->json($data);
    }
}
