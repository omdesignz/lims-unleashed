<?php

namespace App\Http\Controllers;

class ChartDataController extends Controller
{
    public function getChartData($chartType)
    {
        // Example data structure; customize based on chartType
        $data = [];

        switch ($chartType) {
            case 'line':
                $data = [
                    'labels' => ['Janeiro', 'Fevereiro', 'Março', 'Abril'],
                    'datasets' => [
                        [
                            'label' => 'Vendas',
                            'data' => [10, 20, 15, 30],
                            'backgroundColor' => '#3B82F6',
                        ],
                    ],
                    'options' => [
                        'plugins' => [
                            'title' => [
                                'display' => true,
                                'text' => 'Evolução das vendas',
                                'color' => '#374151', // Tailwind gray-700
                                'font' => [
                                    'size' => 18,
                                    'weight' => 'bold',
                                ],
                            ],
                            'subtitle' => [
                                'display' => true,
                                'text' => 'Desempenho trimestral das vendas',
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
                    'labels' => ['Vermelho', 'Azul', 'Amarelo', 'Verde'],
                    'datasets' => [
                        [
                            'label' => 'Votos',
                            'data' => [12, 19, 3, 5],
                            'backgroundColor' => '#3B82F6',
                        ],
                    ],
                    'options' => [
                        'plugins' => [
                            'title' => [
                                'display' => true,
                                'text' => 'Evolução das vendas',
                                'color' => '#374151', // Tailwind gray-700
                                'font' => [
                                    'size' => 18,
                                    'weight' => 'bold',
                                ],
                            ],
                            'subtitle' => [
                                'display' => true,
                                'text' => 'Desempenho trimestral das vendas',
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
                return response()->json(['error' => 'Tipo de gráfico inválido'], 400);
        }

        return response()->json($data);
    }
}
