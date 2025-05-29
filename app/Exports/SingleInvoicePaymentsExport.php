<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SingleInvoicePaymentsExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents, WithColumnFormatting
{
    protected $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function collection()
    {
        // Load relationships
        $this->invoice->load(['customer', 'biller', 'payments', 'items']);

        $data = collect();

        // Invoice header information
        $data->push(['INVOICE DETAILS', '', '', '', '', '', '']);
        $data->push(['Invoice Number', $this->invoice->invoice_number, '', '', '', '', '']);
        $data->push(['Customer', $this->invoice->customer->name ?? 'N/A', '', '', '', '', '']);
        $data->push(['Biller', $this->invoice->biller->business_name ?? 'N/A', '', '', '', '', '']);
        $data->push(['Invoice Date', $this->invoice->invoice_date, '', '', '', '', '']);
        $data->push(['Due Date', $this->invoice->due_date, '', '', '', '', '']);
        $data->push(['Total Amount', $this->invoice->total_amount, '', '', '', '', '']);
        $data->push(['Amount Paid', $this->invoice->amount_paid ?? 0, '', '', '', '', '']);
        $data->push(['Amount Due', $this->invoice->amount_due ?? $this->invoice->total_amount, '', '', '', '', '']);
        $data->push(['Status', ucfirst($this->invoice->status), '', '', '', '', '']);
        $data->push(['', '', '', '', '', '', '']); // Empty row

        // Payment details header
        $data->push(['PAYMENT DETAILS', '', '', '', '', '', '']);
        $data->push(['Payment Date', 'Amount', 'Method', 'Reference', 'Status', 'Notes', 'Running Balance']);

        if ($this->invoice->payments->count() > 0) {
            $runningBalance = 0;
            foreach ($this->invoice->payments->sortBy('payment_date') as $payment) {
                $runningBalance += $payment->amount;
                $data->push([
                    $payment->payment_date,
                    $payment->amount,
                    $payment->payment_method,
                    $payment->payment_reference ?? 'N/A',
                    ucfirst($payment->payment_status),
                    $payment->payment_note ?? 'N/A',
                    $runningBalance
                ]);
            }

            // Payment summary
            $data->push(['', '', '', '', '', '', '']); // Empty row
            $data->push(['PAYMENT SUMMARY', '', '', '', '', '', '']);
            $data->push(['Total Payments', $this->invoice->payments->count(), '', '', '', '', '']);
            $data->push(['Total Paid Amount', $this->invoice->payments->sum('amount'), '', '', '', '', '']);
            $data->push(['Average Payment', $this->invoice->payments->avg('amount'), '', '', '', '', '']);
            $data->push(['First Payment Date', $this->invoice->payments->min('payment_date'), '', '', '', '', '']);
            $data->push(['Last Payment Date', $this->invoice->payments->max('payment_date'), '', '', '', '', '']);

        } else {
            $data->push(['No payments recorded for this invoice', '', '', '', '', '', '']);
        }

        return $data;
    }

    public function headings(): array
    {
        return ['Field', 'Value', 'Extra 1', 'Extra 2', 'Extra 3', 'Extra 4', 'Extra 5'];
    }

    public function title(): string
    {
        return 'Invoice ' . $this->invoice->invoice_number;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 15,
            'D' => 20,
            'E' => 15,
            'F' => 25,
            'G' => 15
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B2' => NumberFormat::FORMAT_TEXT, // Invoice number
            'B5:B6' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Dates
            'B7:B9' => '₹#,##0.00', // Amounts
            'A13:A100' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Payment dates
            'B13:B100' => '₹#,##0.00', // Payment amounts
            'G13:G100' => '₹#,##0.00', // Running balance
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Style invoice details header
                $sheet->getStyle('A1:G1')->applyFromArray([
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

                // Style invoice details section
                $sheet->getStyle('A2:B10')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'f3f4f6']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'd1d5db']
                        ]
                    ]
                ]);

                // Find payment details header row
                $lastRow = $sheet->getHighestRow();
                for ($i = 1; $i <= $lastRow; $i++) {
                    $cellValue = $sheet->getCell("A{$i}")->getValue();
                    if ($cellValue === 'PAYMENT DETAILS') {
                        // Style payment details header
                        $sheet->getStyle("A{$i}:G{$i}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'size' => 14,
                                'color' => ['rgb' => 'FFFFFF']
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '3b82f6']
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                            ]
                        ]);

                        // Style payment table header
                        $headerRow = $i + 1;
                        $sheet->getStyle("A{$headerRow}:G{$headerRow}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => 'FFFFFF']
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '6b7280']
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                            ]
                        ]);
                        break;
                    }

                    if ($cellValue === 'PAYMENT SUMMARY') {
                        // Style payment summary header
                        $sheet->getStyle("A{$i}:G{$i}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'size' => 14,
                                'color' => ['rgb' => 'FFFFFF']
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '059669']
                            ]
                        ]);

                        // Style summary data rows
                        for ($j = $i + 1; $j <= $lastRow; $j++) {
                            $sheet->getStyle("A{$j}:B{$j}")->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'ecfdf5']
                                ]
                            ]);
                        }
                        break;
                    }
                }

                // Add alternating row colors for payment data
                $paymentStartRow = 14; // Approximate start of payment data
                for ($i = $paymentStartRow; $i <= $lastRow; $i++) {
                    $cellValue = $sheet->getCell("A{$i}")->getValue();
                    if (!empty($cellValue) && $cellValue !== 'PAYMENT SUMMARY' && !str_contains($cellValue, 'SUMMARY')) {
                        if (($i - $paymentStartRow) % 2 == 0) {
                            $sheet->getStyle("A{$i}:G{$i}")->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'f9fafb']
                                ]
                            ]);
                        }
                    }
                }

                // Add borders to the entire sheet
                $sheet->getStyle('A1:G' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'd1d5db']
                        ]
                    ]
                ]);

                // Auto-fit columns
                foreach(range('A','G') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }

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