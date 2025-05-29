<?php
/*
namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class PaymentMethodsChart extends ChartWidget
{
    protected static ?string $heading = 'Payment Methods Distribution';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'half';
    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $paymentMethods = Payment::selectRaw('payment_method, COUNT(*) as count')
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method')
            ->toArray();

        $labels = array_keys($paymentMethods);
        $data = array_values($paymentMethods);

        // Generate different colors for each payment method
        $colors = [
            'rgba(59, 130, 246, 0.8)',   // Blue
            'rgba(16, 185, 129, 0.8)',   // Green
            'rgba(245, 158, 11, 0.8)',   // Yellow
            'rgba(239, 68, 68, 0.8)',    // Red
            'rgba(139, 92, 246, 0.8)',   // Purple
            'rgba(236, 72, 153, 0.8)',   // Pink
            'rgba(14, 165, 233, 0.8)',   // Sky Blue
            'rgba(34, 197, 94, 0.8)',    // Emerald
        ];

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderColor' => array_map(function($color) {
                        return str_replace('0.8', '1', $color);
                    }, array_slice($colors, 0, count($data))),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.label || "";
                            let value = context.parsed;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value / total) * 100);
                            return label + ": " + value + " (" + percentage + "%)";
                        }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '50%',
        ];
    }
}
    */
