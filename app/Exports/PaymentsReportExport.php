<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithConditionalFormatting;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PaymentsReportExport implements WithMultipleSheets
{
    protected $invoices;
    protected $reportType;

    public function __construct(Collection $invoices, string $reportType = 'payments')
    {
        $this->invoices = $invoices;
        $this->reportType = $reportType;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Summary Sheet
        $sheets[] = new PaymentsSummarySheet($this->invoices);

        // Detailed Payments Sheet
        $sheets[] = new PaymentsDetailSheet($this->invoices);

        // Invoice Overview Sheet
        $sheets[] = new InvoicesOverviewSheet($this->invoices);

        // Analytics Sheet
        $sheets[] = new PaymentsAnalyticsSheet($this->invoices);

        return $sheets;
    }
}

// Summary Sheet
class PaymentsSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithCustomStartCell, WithEvents, WithColumnFormatting
{
    protected $invoices;

    public function __construct(Collection $invoices)
    {
        $this->invoices = $invoices;
    }

    public function collection()
    {
        // Calculate summary data
        $totalInvoices = $this->invoices->count();
        $totalInvoiceAmount = $this->invoices->sum('total_amount');
        $totalPaidAmount = $this->invoices->sum('amount_paid') ?? 0;
        $totalDueAmount = $this->invoices->sum('amount_due') ?? 0;

        $paidInvoices = $this->invoices->where('status', 'paid')->count();
        $partiallyPaidInvoices = $this->invoices->where('status', 'partially_paid')->count();
        $unpaidInvoices = $this->invoices->where('status', 'unpaid')->count();

        $totalPayments = $this->invoices->sum(function($invoice) {
            return $invoice->payments->count();
        });

        // Group by payment methods
        $paymentMethods = [];
        foreach ($this->invoices as $invoice) {
            foreach ($invoice->payments as $payment) {
                $method = $payment->payment_method;
                if (!isset($paymentMethods[$method])) {
                    $paymentMethods[$method] = ['count' => 0, 'amount' => 0];
                }
                $paymentMethods[$method]['count']++;
                $paymentMethods[$method]['amount'] += $payment->amount;
            }
        }

        return collect([
            ['', ''],
            ['PAYMENTS REPORT SUMMARY', ''],
            ['Generated on:', now()->format('Y-m-d H:i:s')],
            ['', ''],
            ['INVOICE STATISTICS', ''],
            ['Total Invoices', $totalInvoices],
            ['Paid Invoices', $paidInvoices],
            ['Partially Paid Invoices', $partiallyPaidInvoices],
            ['Unpaid Invoices', $unpaidInvoices],
            ['', ''],
            ['FINANCIAL SUMMARY', ''],
            ['Total Invoice Amount', $totalInvoiceAmount],
            ['Total Amount Paid', $totalPaidAmount],
            ['Total Amount Due', $totalDueAmount],
            ['Collection Rate (%)', $totalInvoiceAmount > 0 ? round(($totalPaidAmount / $totalInvoiceAmount) * 100, 2) : 0],
            ['', ''],
            ['PAYMENT STATISTICS', ''],
            ['Total Number of Payments', $totalPayments],
            ['Average Payment Amount', $totalPayments > 0 ? round($totalPaidAmount / $totalPayments, 2) : 0],
            ['', ''],
            ['PAYMENT METHODS BREAKDOWN', ''],
            ['Method', 'Count | Amount'],
            ...collect($paymentMethods)->map(function($data, $method) {
                return [$method, $data['count'] . ' payments | $' . number_format($data['amount'], 2)];
            })
        ]);
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 30,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B6:B9' => NumberFormat::FORMAT_NUMBER,
            'B12:B14' => '₹#,##0.00',
            'B15' => NumberFormat::FORMAT_PERCENTAGE,
            'B18:B19' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Style the header
                $sheet->getStyle('A2:B2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1f2937']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ]
                ]);

                // Style section headers
                $sectionHeaders = ['A5', 'A11', 'A17', 'A21'];
                foreach ($sectionHeaders as $cell) {
                    $sheet->getStyle($cell)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                            'color' => ['rgb' => 'FFFFFF']
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '3b82f6']
                        ]
                    ]);
                }

                // Style data rows
                $sheet->getStyle('A6:B9')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'f3f4f6']
                    ]
                ]);

                $sheet->getStyle('A12:B15')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ecfdf5']
                    ]
                ]);

                // Add borders
                $sheet->getStyle('A2:B' . $sheet->getHighestRow())->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'd1d5db']
                        ]
                    ]
                ]);
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

// Detailed Payments Sheet
class PaymentsDetailSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents, WithColumnFormatting
{
    protected $invoices;

    public function __construct(Collection $invoices)
    {
        $this->invoices = $invoices;
    }

    public function collection()
    {
        $data = collect();

        foreach ($this->invoices as $invoice) {
            if ($invoice->payments->count() > 0) {
                foreach ($invoice->payments as $payment) {
                    $data->push([
                        $invoice->invoice_number,
                        $invoice->customer->name ?? 'N/A',
                        $invoice->biller->business_name ?? 'N/A',
                        $invoice->invoice_date,
                        $invoice->due_date,
                        $invoice->total_amount,
                        ucfirst($invoice->status),
                        $payment->amount,
                        $payment->payment_method,
                        $payment->payment_date,
                        $payment->payment_reference ?? 'N/A',
                        ucfirst($payment->payment_status),
                        $payment->payment_note ?? 'N/A'
                    ]);
                }
            } else {
                $data->push([
                    $invoice->invoice_number,
                    $invoice->customer->name ?? 'N/A',
                    $invoice->biller->business_name ?? 'N/A',
                    $invoice->invoice_date,
                    $invoice->due_date,
                    $invoice->total_amount,
                    ucfirst($invoice->status),
                    0,
                    'No Payments',
                    'N/A',
                    'N/A',
                    'N/A',
                    'N/A'
                ]);
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Customer Name',
            'Biller Name',
            'Invoice Date',
            'Due Date',
            'Invoice Total',
            'Invoice Status',
            'Payment Amount',
            'Payment Method',
            'Payment Date',
            'Payment Reference',
            'Payment Status',
            'Payment Notes'
        ];
    }

    public function title(): string
    {
        return 'Payment Details';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 20,
            'C' => 20,
            'D' => 12,
            'E' => 12,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 12,
            'K' => 20,
            'L' => 15,
            'M' => 25
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'E' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'F' => '₹#,##0.00',
            'H' => '₹#,##0.00',
            'J' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Style the header
                $sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1f2937']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ]
                ]);

                // Add alternating row colors
                $lastRow = $sheet->getHighestRow();
                for ($i = 2; $i <= $lastRow; $i++) {
                    if ($i % 2 == 0) {
                        $sheet->getStyle("A{$i}:M{$i}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'f9fafb']
                            ]
                        ]);
                    }
                }

                // Add borders
                $sheet->getStyle('A1:M' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'd1d5db']
                        ]
                    ]
                ]);

                // Auto-filter
                $sheet->setAutoFilter('A1:M1');

                // Freeze header row
                $sheet->freezePane('A2');
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

// Invoice Overview Sheet
class InvoicesOverviewSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents, WithColumnFormatting
{
    protected $invoices;

    public function __construct(Collection $invoices)
    {
        $this->invoices = $invoices;
    }

    public function collection()
    {
        return $this->invoices->map(function($invoice) {
            return [
                $invoice->invoice_number,
                $invoice->customer->name ?? 'N/A',
                $invoice->biller->business_name ?? 'N/A',
                $invoice->invoice_date,
                $invoice->due_date,
                $invoice->total_amount,
                $invoice->amount_paid ?? 0,
                $invoice->amount_due ?? $invoice->total_amount,
                ucfirst($invoice->status),
                $invoice->payments->count(),
                $invoice->payments->count() > 0 ? $invoice->payments->avg('amount') : 0,
                $invoice->payments->count() > 0 ? $invoice->payments->first()->payment_date : 'N/A',
                $invoice->payments->count() > 0 ? $invoice->payments->last()->payment_date : 'N/A'
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Customer Name',
            'Biller Name',
            'Invoice Date',
            'Due Date',
            'Total Amount',
            'Amount Paid',
            'Amount Due',
            'Status',
            'Payment Count',
            'Avg Payment',
            'First Payment',
            'Last Payment'
        ];
    }

    public function title(): string
    {
        return 'Invoice Overview';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 20,
            'C' => 20,
            'D' => 12,
            'E' => 12,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 12,
            'K' => 15,
            'L' => 12,
            'M' => 12
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'E' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'F' => '₹#,##0.00',
            'G' => '₹#,##0.00',
            'H' => '₹#,##0.00',
            'K' => '₹#,##0.00',
            'L' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
            'M' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Style the header
                $sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '059669']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ]
                ]);

                // Conditional formatting for status
                $lastRow = $sheet->getHighestRow();
                for ($i = 2; $i <= $lastRow; $i++) {
                    $status = $sheet->getCell("I{$i}")->getValue();
                    $fillColor = 'f3f4f6'; // default gray

                    switch (strtolower($status)) {
                        case 'paid':
                            $fillColor = 'd1fae5'; // green
                            break;
                        case 'partially_paid':
                            $fillColor = 'fef3c7'; // yellow
                            break;
                        case 'unpaid':
                            $fillColor = 'fee2e2'; // red
                            break;
                    }

                    $sheet->getStyle("A{$i}:M{$i}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $fillColor]
                        ]
                    ]);
                }

                // Add borders
                $sheet->getStyle('A1:M' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'd1d5db']
                        ]
                    ]
                ]);

                // Auto-filter
                $sheet->setAutoFilter('A1:M1');

                // Freeze header row
                $sheet->freezePane('A2');
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

// Analytics Sheet
class PaymentsAnalyticsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents, WithColumnFormatting
{
    protected $invoices;

    public function __construct(Collection $invoices)
    {
        $this->invoices = $invoices;
    }

    public function collection()
    {
        // Monthly payment analysis
        $monthlyData = [];
        $customerData = [];
        $billerData = [];

        foreach ($this->invoices as $invoice) {
            // Customer analysis
            $customerName = $invoice->customer->name ?? 'N/A';
            if (!isset($customerData[$customerName])) {
                $customerData[$customerName] = [
                    'invoices' => 0,
                    'total_amount' => 0,
                    'paid_amount' => 0,
                    'payments_count' => 0
                ];
            }
            $customerData[$customerName]['invoices']++;
            $customerData[$customerName]['total_amount'] += $invoice->total_amount;
            $customerData[$customerName]['paid_amount'] += ($invoice->amount_paid ?? 0);
            $customerData[$customerName]['payments_count'] += $invoice->payments->count();

            // Biller analysis
            $billerName = $invoice->biller->business_name ?? 'N/A';
            if (!isset($billerData[$billerName])) {
                $billerData[$billerName] = [
                    'invoices' => 0,
                    'total_amount' => 0,
                    'paid_amount' => 0,
                    'payments_count' => 0
                ];
            }
            $billerData[$billerName]['invoices']++;
            $billerData[$billerName]['total_amount'] += $invoice->total_amount;
            $billerData[$billerName]['paid_amount'] += ($invoice->amount_paid ?? 0);
            $billerData[$billerName]['payments_count'] += $invoice->payments->count();

            // Monthly analysis
            foreach ($invoice->payments as $payment) {
                $month = date('Y-m', strtotime($payment->payment_date));
                if (!isset($monthlyData[$month])) {
                    $monthlyData[$month] = [
                        'payments_count' => 0,
                        'total_amount' => 0
                    ];
                }
                $monthlyData[$month]['payments_count']++;
                $monthlyData[$month]['total_amount'] += $payment->amount;
            }
        }

        $data = collect();

        // Monthly data section
        $data->push(['MONTHLY PAYMENT ANALYSIS', '', '', '']);
        $data->push(['Month', 'Payment Count', 'Total Amount', 'Average Payment']);
        ksort($monthlyData);
        foreach ($monthlyData as $month => $data_month) {
            $avgPayment = $data_month['payments_count'] > 0 ? $data_month['total_amount'] / $data_month['payments_count'] : 0;
            $data->push([$month, $data_month['payments_count'], $data_month['total_amount'], $avgPayment]);
        }

        $data->push(['', '', '', '']);

        // Customer analysis section
        $data->push(['CUSTOMER ANALYSIS', '', '', '']);
        $data->push(['Customer', 'Invoices', 'Total Amount', 'Paid Amount', 'Payment Rate %']);
        uasort($customerData, function($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });
        foreach ($customerData as $customer => $customer_data) {
            $paymentRate = $customer_data['total_amount'] > 0 ? ($customer_data['paid_amount'] / $customer_data['total_amount']) * 100 : 0;
            $data->push([$customer, $customer_data['invoices'], $customer_data['total_amount'], $customer_data['paid_amount'], $paymentRate]);
        }

        return $data;
    }

    public function headings(): array
    {
        return ['Metric', 'Value 1', 'Value 2', 'Value 3', 'Value 4'];
    }

    public function title(): string
    {
        return 'Analytics';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => '₹#,##0.00',
            'D' => '₹#,##0.00',
            'E' => NumberFormat::FORMAT_PERCENTAGE,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Style section headers
                $sheet->getStyle('A1:E1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '7c3aed']
                    ]
                ]);

                // Find and style other section headers
                $lastRow = $sheet->getHighestRow();
                for ($i = 1; $i <= $lastRow; $i++) {
                    $cellValue = $sheet->getCell("A{$i}")->getValue();
                    if (strpos($cellValue, 'ANALYSIS') !== false && $i > 1) {
                        $sheet->getStyle("A{$i}:E{$i}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => 'FFFFFF']
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'dc2626']
                            ]
                        ]);
                    }
                }

                // Add borders
                $sheet->getStyle('A1:E' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'd1d5db']
                        ]
                    ]
                ]);
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
