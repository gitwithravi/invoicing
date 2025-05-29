<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        return [
            // Total Invoices with trend
            Stat::make('Total Invoices', $this->getTotalInvoices())
                ->description($this->getInvoicesDescription())
                ->descriptionIcon($this->getInvoicesTrendIcon())
                ->chart($this->getInvoicesChartData())
                ->color($this->getInvoicesTrendColor())
                ->icon('heroicon-o-document-text'),

            // Total Customers with trend
            Stat::make('Total Customers', $this->getTotalCustomers())
                ->description($this->getCustomersDescription())
                ->descriptionIcon($this->getCustomersTrendIcon())
                ->chart($this->getCustomersChartData())
                ->color($this->getCustomersTrendColor())
                ->icon('heroicon-o-users'),

            // Total Payments with trend
            Stat::make('Total Payments', $this->getTotalPayments())
                ->description($this->getPaymentsDescription())
                ->descriptionIcon($this->getPaymentsTrendIcon())
                ->chart($this->getPaymentsChartData())
                ->color($this->getPaymentsTrendColor())
                ->icon('heroicon-o-banknotes'),

            // Revenue stat
            Stat::make('Total Revenue', $this->formatCurrency($this->getTotalRevenue()))
                ->description($this->getRevenueDescription())
                ->descriptionIcon($this->getRevenueTrendIcon())
                ->chart($this->getRevenueChartData())
                ->color($this->getRevenueTrendColor())
                ->icon('heroicon-o-currency-dollar'),

            // Pending Invoices
            Stat::make('Pending Invoices', $this->getPendingInvoices())
                ->description('Invoices awaiting payment')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            // Average Invoice Amount
            Stat::make('Avg Invoice', $this->formatCurrency($this->getAverageInvoiceAmount()))
                ->description('Average invoice value')
                ->color('info')
                ->icon('heroicon-o-calculator'),
        ];
    }

    // Invoice Statistics
    private function getTotalInvoices(): int
    {
        return Invoice::count();
    }

    private function getInvoicesDescription(): string
    {
        $thisMonth = Invoice::whereMonth('created_at', now()->month)->count();
        $lastMonth = Invoice::whereMonth('created_at', now()->subMonth()->month)->count();

        if ($lastMonth === 0) {
            return $thisMonth > 0 ? "{$thisMonth} new this month" : 'No invoices this month';
        }

        $percentage = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
        $trend = $percentage >= 0 ? '+' : '';

        return "{$trend}{$percentage}% from last month";
    }

    private function getInvoicesTrendIcon(): string
    {
        $thisMonth = Invoice::whereMonth('created_at', now()->month)->count();
        $lastMonth = Invoice::whereMonth('created_at', now()->subMonth()->month)->count();

        return $thisMonth >= $lastMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    private function getInvoicesTrendColor(): string
    {
        $thisMonth = Invoice::whereMonth('created_at', now()->month)->count();
        $lastMonth = Invoice::whereMonth('created_at', now()->subMonth()->month)->count();

        return $thisMonth >= $lastMonth ? 'success' : 'danger';
    }

    private function getInvoicesChartData(): array
    {
        return collect(range(6, 0))->map(function ($monthsBack) {
            return Invoice::whereMonth('created_at', now()->subMonths($monthsBack)->month)
                ->whereYear('created_at', now()->subMonths($monthsBack)->year)
                ->count();
        })->toArray();
    }

    // Customer Statistics
    private function getTotalCustomers(): int
    {
        return Customer::count();
    }

    private function getCustomersDescription(): string
    {
        $thisMonth = Customer::whereMonth('created_at', now()->month)->count();
        $lastMonth = Customer::whereMonth('created_at', now()->subMonth()->month)->count();

        if ($lastMonth === 0) {
            return $thisMonth > 0 ? "{$thisMonth} new this month" : 'No new customers';
        }

        $percentage = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
        $trend = $percentage >= 0 ? '+' : '';

        return "{$trend}{$percentage}% from last month";
    }

    private function getCustomersTrendIcon(): string
    {
        $thisMonth = Customer::whereMonth('created_at', now()->month)->count();
        $lastMonth = Customer::whereMonth('created_at', now()->subMonth()->month)->count();

        return $thisMonth >= $lastMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    private function getCustomersTrendColor(): string
    {
        $thisMonth = Customer::whereMonth('created_at', now()->month)->count();
        $lastMonth = Customer::whereMonth('created_at', now()->subMonth()->month)->count();

        return $thisMonth >= $lastMonth ? 'success' : 'danger';
    }

    private function getCustomersChartData(): array
    {
        return collect(range(6, 0))->map(function ($monthsBack) {
            return Customer::whereMonth('created_at', now()->subMonths($monthsBack)->month)
                ->whereYear('created_at', now()->subMonths($monthsBack)->year)
                ->count();
        })->toArray();
    }

    // Payment Statistics
    private function getTotalPayments(): int
    {
        return Payment::count();
    }

    private function getPaymentsDescription(): string
    {
        $thisMonth = Payment::whereMonth('created_at', now()->month)->count();
        $lastMonth = Payment::whereMonth('created_at', now()->subMonth()->month)->count();

        if ($lastMonth === 0) {
            return $thisMonth > 0 ? "{$thisMonth} new this month" : 'No payments this month';
        }

        $percentage = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
        $trend = $percentage >= 0 ? '+' : '';

        return "{$trend}{$percentage}% from last month";
    }

    private function getPaymentsTrendIcon(): string
    {
        $thisMonth = Payment::whereMonth('created_at', now()->month)->count();
        $lastMonth = Payment::whereMonth('created_at', now()->subMonth()->month)->count();

        return $thisMonth >= $lastMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    private function getPaymentsTrendColor(): string
    {
        $thisMonth = Payment::whereMonth('created_at', now()->month)->count();
        $lastMonth = Payment::whereMonth('created_at', now()->subMonth()->month)->count();

        return $thisMonth >= $lastMonth ? 'success' : 'danger';
    }

    private function getPaymentsChartData(): array
    {
        return collect(range(6, 0))->map(function ($monthsBack) {
            return Payment::whereMonth('created_at', now()->subMonths($monthsBack)->month)
                ->whereYear('created_at', now()->subMonths($monthsBack)->year)
                ->count();
        })->toArray();
    }

    // Revenue Statistics
    private function getTotalRevenue(): float
    {
        return Payment::sum('amount') ?? 0;
    }

    private function getRevenueDescription(): string
    {
        $thisMonth = Payment::whereMonth('created_at', now()->month)->sum('amount') ?? 0;
        $lastMonth = Payment::whereMonth('created_at', now()->subMonth()->month)->sum('amount') ?? 0;

        if ($lastMonth == 0) {
            return $thisMonth > 0 ? $this->formatCurrency($thisMonth) . ' this month' : 'No revenue this month';
        }

        $percentage = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
        $trend = $percentage >= 0 ? '+' : '';

        return "{$trend}{$percentage}% from last month";
    }

    private function getRevenueTrendIcon(): string
    {
        $thisMonth = Payment::whereMonth('created_at', now()->month)->sum('amount') ?? 0;
        $lastMonth = Payment::whereMonth('created_at', now()->subMonth()->month)->sum('amount') ?? 0;

        return $thisMonth >= $lastMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    private function getRevenueTrendColor(): string
    {
        $thisMonth = Payment::whereMonth('created_at', now()->month)->sum('amount') ?? 0;
        $lastMonth = Payment::whereMonth('created_at', now()->subMonth()->month)->sum('amount') ?? 0;

        return $thisMonth >= $lastMonth ? 'success' : 'danger';
    }

    private function getRevenueChartData(): array
    {
        return collect(range(6, 0))->map(function ($monthsBack) {
            return Payment::whereMonth('created_at', now()->subMonths($monthsBack)->month)
                ->whereYear('created_at', now()->subMonths($monthsBack)->year)
                ->sum('amount') ?? 0;
        })->toArray();
    }

    // Additional Statistics
    private function getPendingInvoices(): int
    {
        return Invoice::where('status', '!=', 'paid')
            ->where('amount_due', '>', 0)
            ->count();
    }

    private function getAverageInvoiceAmount(): float
    {
        return Invoice::avg('total_amount') ?? 0;
    }

    // Helper method to format currency
    private function formatCurrency(float $amount): string
    {
        return '₹' . number_format($amount, 2);
    }
}
