<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;

class InvoiceStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Invoice Status Distribution';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'half';
    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $statuses = Invoice::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $labels = array_keys($statuses);
        $data = array_values($statuses);

        // Define colors based on status
        $colorMap = [
            'paid' => 'rgba(16, 185, 129, 0.8)',       // Green for paid
            'pending' => 'rgba(245, 158, 11, 0.8)',    // Yellow for pending
            'overdue' => 'rgba(239, 68, 68, 0.8)',     // Red for overdue
            'draft' => 'rgba(156, 163, 175, 0.8)',     // Gray for draft
            'cancelled' => 'rgba(107, 114, 128, 0.8)', // Dark gray for cancelled
        ];

        $backgroundColors = array_map(function($status) use ($colorMap) {
            return $colorMap[$status] ?? 'rgba(59, 130, 246, 0.8)'; // Default blue
        }, $labels);

        $borderColors = array_map(function($color) {
            return str_replace('0.8', '1', $color);
        }, $backgroundColors);

        return [
            'datasets' => [
                [
                    'label' => 'Number of Invoices',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => array_map('ucfirst', $labels), // Capitalize first letter
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            let value = context.parsed.y;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value / total) * 100);
                            return "Count: " + value + " (" + percentage + "%)";
                        }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Invoices',
                    ],
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Invoice Status',
                    ],
                ],
            ],
        ];
    }
}
