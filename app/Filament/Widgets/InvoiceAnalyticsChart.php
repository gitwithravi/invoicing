<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class InvoiceAnalyticsChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue & Invoice Analytics';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        // Get last 12 months of data
        $months = collect(range(11, 0))->map(function ($monthsBack) {
            return now()->subMonths($monthsBack);
        });

        $invoiceData = $months->map(function ($date) {
            return Invoice::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        })->toArray();

        $revenueData = $months->map(function ($date) {
            return Payment::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount') ?? 0;
        })->toArray();

        $labels = $months->map(function ($date) {
            return $date->format('M Y');
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Invoices Created',
                    'data' => $invoiceData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Revenue ($)',
                    'data' => $revenueData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Invoices',
                    ],
                    'beginAtZero' => true,
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue ($)',
                    ],
                    'beginAtZero' => true,
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Month',
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
