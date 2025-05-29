<?php
namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Biller;
use App\Models\Ledger;
use App\Models\Invoice;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CustomerGroup;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentsReportExport;
use App\Exports\SingleInvoicePaymentsExport;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\InvoiceResource\Pages;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getEloquentQuery(): Builder
    {
        if(Auth::user()->user_type == 'user') {
            $customer = Customer::where('user_id', auth()->user()->id)->first();
            return parent::getEloquentQuery()->where('customer_id', $customer->id);
        }
        return parent::getEloquentQuery();

    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\Grid::make('2')
                            ->schema([
                                Forms\Components\Grid::make('1')
                                    ->schema([
                                        Forms\Components\Select::make('ledger_id')
                                            ->label('Ledger')
                                            ->inlineLabel()
                                            ->relationship('ledger', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->extraAttributes(['class' => 'fi-input-sm']),
                                        Forms\Components\TextInput::make('invoice_number')
                                            ->required()
                                            ->inlineLabel()
                                            ->maxLength(255)
                                            ->extraAttributes(['class' => 'fi-input-sm']),
                                        Forms\Components\DatePicker::make('invoice_date')
                                            ->required()
                                            ->inlineLabel()
                                            ->extraAttributes(['class' => 'fi-input-sm']),
                                        Forms\Components\DatePicker::make('due_date')
                                            ->required()
                                            ->inlineLabel()
                                            ->extraAttributes(['class' => 'fi-input-sm']),
                                    ])
                                    ->columnSpan(1),
                                Forms\Components\Placeholder::make('')
                                    ->content(function (Get $get) {
                                        $biller = $get('biller_id');
                                        $biller = Biller::find($biller);
                                        if ($biller) {
                                            return new HtmlString('<div style="display: flex; justify-content: flex-end; width: 100%;"><img src="/storage/' . $biller->logo . '" alt="Biller Logo" style="width: 180px;"></div>');
                                        }
                                    })
                                    ->live()
                                    ->extraAttributes(['class' => 'flex justify-end'])
                                    ->columnSpan(1),
                            ])
                            ->columnSpan(1),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Section::make('Billed By')
                            ->schema([
                                Forms\Components\Select::make('biller_id')
                                    ->label('')
                                    ->relationship('biller', 'business_name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('')
                                            ->content(function (Get $get) {
                                                $biller = $get('biller_id');
                                                $biller = Biller::find($biller);
                                                if (! $biller) {
                                                    return '';
                                                }
                                                //return $biller ? $biller->business_name : 'No biller selected';
                                                $billerDetails = '
                                            <div>
                                                <h3>' . $biller->business_name . '</h3>
                                                <p>' . $biller->address . '</p>
                                                <p>' . $biller->city . ',' . $biller->state . ',' . $biller->zip . '</p>
                                                <p>' . $biller->country . '</p>

                                            </div>
                                            ';
                                                return new HtmlString($billerDetails);
                                            })
                                            ->live()
                                            ->columnSpan(1),
                                    ]),

                            ])
                            ->columnSpan(1),
                        Forms\Components\Section::make('Billed To')
                            ->schema([
                                Forms\Components\Select::make('invoice_type')
                                    ->label('')
                                    ->options([
                                        'customer'       => 'Customer',
                                        'customer_group' => 'Customer Group',
                                    ])
                                    ->required()
                                    ->live()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\Select::make('customer_group_id')
                                    ->label('')
                                    ->options(CustomerGroup::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->extraAttributes(['class' => 'fi-input-sm'])
                                    ->visible(fn(Get $get): bool => $get('invoice_type') === 'customer_group'),
                                Forms\Components\Select::make('customer_id')
                                    ->label('')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->extraAttributes(['class' => 'fi-input-sm'])
                                    ->visible(fn(Get $get): bool => $get('invoice_type') === 'customer'),
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('')
                                            ->content(function (Get $get) {
                                                if ($get('invoice_type') === 'customer_group') {
                                                    return 'Invoice for Customer Group';
                                                } else {
                                                    $customer = $get('customer_id');
                                                }
                                                $customer = Customer::find($customer);
                                                if (! $customer) {
                                                    return '';
                                                }
                                                $customerDetails = '
                                                <div>
                                                    <h3>' . $customer->name . '</h3>
                                                    <p>' . $customer->address . '</p>
                                                    <p>' . $customer->city . ',' . $customer->state . ',' . $customer->zip . '</p>
                                                    <p>' . $customer->country . '</p>
                                                </div>
                                                ';
                                                return new HtmlString($customerDetails);
                                            })
                                            ->live()
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ]),
                Forms\Components\Section::make('Items')
                    ->schema([
                        // Repeater for single customer invoices (with relationship)
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\TextInput::make('item_name')
                                    ->required()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $quantity  = floatval($get('quantity') ?? 0);
                                        $unitPrice = floatval($get('unit_price') ?? 0);
                                        $set('total_price', $quantity * $unitPrice);

                                        // Recalculate tax amounts
                                        $totalPrice     = $quantity * $unitPrice;
                                        $taxRate        = floatval($get('tax_rate') ?? 0);
                                        $totalTaxAmount = $totalPrice * ($taxRate / 100);
                                        $set('total_tax_amount', $totalTaxAmount);
                                        $set('amount_with_tax', $totalPrice + $totalTaxAmount);
                                    })
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('unit_price')
                                    ->required()
                                    ->numeric()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $quantity  = floatval($get('quantity') ?? 0);
                                        $unitPrice = floatval($get('unit_price') ?? 0);
                                        $set('total_price', $quantity * $unitPrice);

                                        // Recalculate tax amounts
                                        $totalPrice     = $quantity * $unitPrice;
                                        $taxRate        = floatval($get('tax_rate') ?? 0);
                                        $totalTaxAmount = $totalPrice * ($taxRate / 100);
                                        $set('total_tax_amount', $totalTaxAmount);
                                        $set('amount_with_tax', $totalPrice + $totalTaxAmount);
                                    })
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('total_price')
                                    ->required()
                                    ->disabled()
                                    ->numeric()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('tax_name')
                                    ->required()
                                    ->live()
                                    ->disabled(fn(Get $get): bool => ! $get('total_price'))
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('tax_rate')
                                    ->required()
                                    ->numeric()
                                    ->live()
                                    ->disabled(fn(Get $get): bool => ! $get('total_price'))
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $totalPrice     = floatval($get('total_price') ?? 0);
                                        $taxRate        = floatval($get('tax_rate') ?? 0);
                                        $totalTaxAmount = $totalPrice * ($taxRate / 100);
                                        $set('total_tax_amount', $totalTaxAmount);
                                        $set('amount_with_tax', $totalPrice + $totalTaxAmount);
                                    })
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('total_tax_amount')
                                    ->required()
                                    ->disabled()
                                    ->numeric()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('amount_with_tax')
                                    ->required()
                                    ->disabled()
                                    ->numeric()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                            ])
                            ->columns(8)
                            ->visible(fn(Get $get): bool => $get('invoice_type') !== 'customer_group'),

                        // Repeater for customer group invoices (without relationship)
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                Forms\Components\TextInput::make('item_name')
                                    ->required()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $quantity  = floatval($get('quantity') ?? 0);
                                        $unitPrice = floatval($get('unit_price') ?? 0);
                                        $set('total_price', $quantity * $unitPrice);

                                        // Recalculate tax amounts
                                        $totalPrice     = $quantity * $unitPrice;
                                        $taxRate        = floatval($get('tax_rate') ?? 0);
                                        $totalTaxAmount = $totalPrice * ($taxRate / 100);
                                        $set('total_tax_amount', $totalTaxAmount);
                                        $set('amount_with_tax', $totalPrice + $totalTaxAmount);
                                    })
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('unit_price')
                                    ->required()
                                    ->numeric()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $quantity  = floatval($get('quantity') ?? 0);
                                        $unitPrice = floatval($get('unit_price') ?? 0);
                                        $set('total_price', $quantity * $unitPrice);

                                        // Recalculate tax amounts
                                        $totalPrice     = $quantity * $unitPrice;
                                        $taxRate        = floatval($get('tax_rate') ?? 0);
                                        $totalTaxAmount = $totalPrice * ($taxRate / 100);
                                        $set('total_tax_amount', $totalTaxAmount);
                                        $set('amount_with_tax', $totalPrice + $totalTaxAmount);
                                    })
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('total_price')
                                    ->required()
                                    ->disabled()
                                    ->numeric()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('tax_name')
                                    ->required()
                                    ->live()
                                    ->disabled(fn(Get $get): bool => ! $get('total_price'))
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('tax_rate')
                                    ->required()
                                    ->numeric()
                                    ->live()
                                    ->disabled(fn(Get $get): bool => ! $get('total_price'))
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $totalPrice     = floatval($get('total_price') ?? 0);
                                        $taxRate        = floatval($get('tax_rate') ?? 0);
                                        $totalTaxAmount = $totalPrice * ($taxRate / 100);
                                        $set('total_tax_amount', $totalTaxAmount);
                                        $set('amount_with_tax', $totalPrice + $totalTaxAmount);
                                    })
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('total_tax_amount')
                                    ->required()
                                    ->disabled()
                                    ->numeric()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('amount_with_tax')
                                    ->required()
                                    ->disabled()
                                    ->numeric()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                            ])
                            ->columns(8)
                            ->visible(fn(Get $get): bool => $get('invoice_type') === 'customer_group'),

                    ]),
                Forms\Components\Section::make('Extra Charges')
                    ->schema([
                        // Repeater for single customer invoices (with relationship)
                        Forms\Components\Repeater::make('extra_charges')
                            ->relationship('extraCharges')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('amount')
                                    ->required()
                                    ->numeric()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\Select::make('type')
                                    ->required()
                                    ->options([
                                        'discount'     => 'Discount',
                                        'extra_charge' => 'Extra Charge',
                                    ])
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                            ])
                            ->columns(3)
                            ->visible(fn(Get $get): bool => $get('invoice_type') !== 'customer_group'),

                        // Repeater for customer group invoices (without relationship)
                        Forms\Components\Repeater::make('extra_charges')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('amount')
                                    ->required()
                                    ->numeric()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\Select::make('type')
                                    ->required()
                                    ->options([
                                        'discount'     => 'Discount',
                                        'extra_charge' => 'Extra Charge',
                                    ])
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                            ])
                            ->columns(3)
                            ->visible(fn(Get $get): bool => $get('invoice_type') === 'customer_group'),
                    ]),
                Forms\Components\Section::make('Total')
                    ->schema([
                        Forms\Components\Placeholder::make('Tax Details')
                            ->content(function (Get $get, Set $set) {
                                $items        = $get('items') ?? [];
                                $taxBreakdown = [];
                                $totalAmount  = 0;

                                // Group items by tax_name and calculate totals
                                foreach ($items as $item) {
                                    $taxName   = $item['tax_name'] ?? 'No Tax';
                                    $taxAmount = floatval($item['total_tax_amount'] ?? 0);
                                    $totalAmount += floatval($item['amount_with_tax'] ?? 0);

                                    if (! isset($taxBreakdown[$taxName])) {
                                        $taxBreakdown[$taxName] = 0;
                                    }
                                    $taxBreakdown[$taxName] += $taxAmount;
                                }

                                // Build the HTML output
                                $output = '<div class="space-y-2">';
                                $output .= '<div class="font-medium">Tax Breakdown:</div>';

                                foreach ($taxBreakdown as $taxName => $amount) {
                                    $output .= sprintf(
                                        '<div class="flex justify-between">
                                        <span>%s:</span>
                                        <span>%.2f</span>
                                    </div>',
                                        htmlspecialchars($taxName),
                                        $amount
                                    );
                                }

                                $output .= sprintf(
                                    '<div class="pt-2 border-t mt-2">
                                    <div class="flex justify-between font-medium">
                                        <span>Subtotal (Inc. Tax):</span>
                                        <span>%.2f</span>
                                    </div>
                                </div>',
                                    $totalAmount
                                );

                                $output .= '</div>';

                                return new HtmlString($output);
                            })
                            ->live()
                            ->extraAttributes(['class' => 'fi-input-sm']),
                        Forms\Components\Placeholder::make('Extra Charges')
                            ->content(function (Get $get, Set $set) {
                                $extraCharges     = $get('extra_charges') ?? [];
                                $totalDiscount    = 0;
                                $totalExtraCharge = 0;

                                // Build the HTML output
                                $output = '<div class="space-y-2">';
                                $output .= '<div class="font-medium">Extra Charges & Discounts:</div>';

                                foreach ($extraCharges as $charge) {
                                    $amount = floatval($charge['amount'] ?? 0);
                                    $type   = $charge['type'] ?? 'extra_charge';
                                    $name   = $charge['name'] ?? '';

                                    if ($type === 'discount') {
                                        $totalDiscount += $amount;
                                        $amount = -$amount; // Show discount as negative
                                    } else {
                                        $totalExtraCharge += $amount;
                                    }

                                    $output .= sprintf(
                                        '<div class="flex justify-between">
                                            <span>%s:</span>
                                            <span class="%s">%.2f</span>
                                        </div>',
                                        htmlspecialchars($name),
                                        $type === 'discount' ? 'text-danger-500' : '',
                                        $amount
                                    );
                                }

                                $output .= '</div>';

                                return new HtmlString($output);
                            })
                            ->live()
                            ->extraAttributes(['class' => 'fi-input-sm']),
                        Forms\Components\Placeholder::make('Final Total')
                            ->content(function (Get $get, Set $set) {
                                // Calculate subtotal from items
                                $items    = $get('items') ?? [];
                                $subtotal = 0;
                                foreach ($items as $item) {
                                    $subtotal += floatval($item['amount_with_tax'] ?? 0);
                                }

                                // Calculate extra charges and discounts
                                $extraCharges     = $get('extra_charges') ?? [];
                                $totalAdjustments = 0;
                                foreach ($extraCharges as $charge) {
                                    $amount = floatval($charge['amount'] ?? 0);
                                    if ($charge['type'] === 'discount') {
                                        $totalAdjustments -= $amount;
                                    } else {
                                        $totalAdjustments += $amount;
                                    }
                                }

                                // Calculate final total
                                $finalTotal = $subtotal + $totalAdjustments;

                                $output = sprintf(
                                    '<div class="pt-2 border-t mt-2">
                                        <div class="flex justify-between font-medium text-lg">
                                            <span>Final Total:</span>
                                            <span>%.2f</span>
                                        </div>
                                    </div>',
                                    $finalTotal
                                );

                                // Set the total_amount field
                                $set('total_amount', $finalTotal);

                                return new HtmlString($output);
                            })
                            ->live()
                            ->extraAttributes(['class' => 'fi-input-sm']),
                        Forms\Components\TextInput::make('total_amount')
                            ->required()
                            ->disabled()
                            ->numeric()
                            ->dehydrated()
                            ->extraAttributes(['class' => 'fi-input-sm']),
                    ]),
                    Forms\Components\Section::make('Payments')
                        ->schema([
                            Forms\Components\Placeholder::make('')
                                ->content(function (Get $get, Set $set) {
                                    $amount_paid = $get('amount_paid') ?? 0;
                                    $amount_due = $get('amount_due') ?? 0;
                                    $total_amount = $get('total_amount') ?? 0;
                                    $output = sprintf(
                                        '<div class="pt-2 border-t mt-2">
                                        <div class="flex justify-between font-medium text-lg">
                                            <span>Amount Paid:</span>
                                            <span>%.2f</span>
                                        </div>
                                    </div>',
                                        $amount_paid
                                    );
                                    return new HtmlString($output);
                                })
                                ->live()
                                ->extraAttributes(['class' => 'fi-input-sm']),
                            Forms\Components\Placeholder::make('')
                                ->content(function (Get $get, Set $set) {
                                    $amount_due = $get('amount_due') ?? 0;
                                    $total_amount = $get('total_amount') ?? 0;
                                    $output = sprintf(
                                        '<div class="pt-2 border-t mt-2">
                                        <div class="flex justify-between font-medium text-lg">
                                            <span>Amount Due:</span>
                                            <span>%.2f</span>
                                        </div>
                                    </div>',
                                        $amount_due
                                    );
                                    return new HtmlString($output);
                                })
                        ])

            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ledger.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')->date('d-m-Y'),
                Tables\Columns\TextColumn::make('due_date')->date('d-m-Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_amount')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_due')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->options(Customer::all()->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(Invoice::pluck('status', 'status'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('ledger_id')
                    ->options(Ledger::all()->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_all_payments')
                    ->label('Export All Payments (Excel)')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('warning')
                    ->action(function () {
                        try {
                            // Get all invoices with their relationships
                            $invoices = Invoice::with(['customer', 'biller', 'payments'])->get();

                            Notification::make()
                                ->title('Excel Export Started')
                                ->body('Generating comprehensive Excel payments report for ' . $invoices->count() . ' invoice(s)...')
                                ->info()
                                ->send();

                            return self::exportPaymentsReport($invoices);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Excel Export Failed')
                                ->body('There was an error generating the Excel payments report: ' . $e->getMessage())
                                ->danger()
                                ->send();

                            \Log::error('All Payments Excel Export Error: ' . $e->getMessage());
                            return null;
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('generateInvoicePdf')
                        ->label('Download Invoice')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (\App\Models\Invoice $record) {
                            try {
                                $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $record])
                                    ->setPaper('a4', 'portrait')
                                    ->setOptions([
                                        'isPhpEnabled'    => true,
                                        'isRemoteEnabled' => true,
                                        'margin_bottom'   => 20,
                                        'defaultFont'     => 'DejaVu Sans',
                                        'chroot'          => storage_path('app/public'),
                                        'enable_remote'   => true,
                                        'log_output_file' => storage_path('logs/dompdf.html'),
                                    ]);

                                return response()->streamDownload(
                                    fn() => print($pdf->output()),
                                    "Invoice-{$record->id}.pdf"
                                );
                            } catch (\Exception $e) {
                                \Log::error('PDF Generation Error: ' . $e->getMessage());
                                throw $e;
                            }
                        })
                        ->color('primary'),
                        Tables\Actions\Action::make('generateReceiptPdf')
                        ->label('Download Receipt')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (\App\Models\Invoice $record) {
                            try {
                                $pdf = Pdf::loadView('receipts.pdf', ['invoice' => $record])
                                    ->setPaper('a4', 'portrait')
                                    ->setOptions([
                                        'isPhpEnabled'    => true,
                                        'isRemoteEnabled' => true,
                                        'margin_bottom'   => 20,
                                        'defaultFont'     => 'DejaVu Sans',
                                        'chroot'          => storage_path('app/public'),
                                        'enable_remote'   => true,
                                        'log_output_file' => storage_path('logs/dompdf.html'),
                                    ]);

                                return response()->streamDownload(
                                    fn() => print($pdf->output()),
                                    "Invoice-{$record->id}.pdf"
                                );
                            } catch (\Exception $e) {
                                \Log::error('PDF Generation Error: ' . $e->getMessage());
                                throw $e;
                            }
                        })
                        ->color('primary')
                        ->visible(fn($record): bool => $record->status === 'paid'),
                    Tables\Actions\Action::make('export_invoice_payments')
                        ->label('Export Invoice Payments (Excel)')
                        ->icon('heroicon-o-table-cells')
                        ->color('info')
                        ->action(function (\App\Models\Invoice $record) {
                            try {
                                Notification::make()
                                    ->title('Excel Export Started')
                                    ->body('Generating Excel payments report for invoice ' . $record->invoice_number)
                                    ->info()
                                    ->send();

                                return self::exportSingleInvoicePayments($record);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Excel Export Failed')
                                    ->body('There was an error generating the Excel invoice payments report: ' . $e->getMessage())
                                    ->danger()
                                    ->send();

                                \Log::error('Single Invoice Payments Excel Export Error: ' . $e->getMessage());
                                return null;
                            }
                        }),
                ]),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\Action::make('export_payments_report')
                        ->label('Export Payments Report (Excel)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function ($records) {
                            try {
                                Notification::make()
                                    ->title('Excel Export Started')
                                    ->body('Generating Excel payments report for ' . $records->count() . ' invoice(s)...')
                                    ->info()
                                    ->send();

                                return self::exportPaymentsReport($records);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Excel Export Failed')
                                    ->body('There was an error generating the Excel payments report: ' . $e->getMessage())
                                    ->danger()
                                    ->send();

                                \Log::error('Payments Report Excel Export Error: ' . $e->getMessage());
                                return null;
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    protected static function exportPaymentsReport($records)
    {
        // Ensure we have a collection
        if (!$records instanceof \Illuminate\Support\Collection) {
            $records = collect($records);
        }

        // Generate filename with current date (already safe, but adding extra safety)
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = 'payments_report_' . $timestamp . '.xlsx';

        // Use the beautiful Excel export
        return Excel::download(new PaymentsReportExport($records), $filename);
    }

    protected static function exportSingleInvoicePayments($invoice)
    {
        // Sanitize invoice number for filename (remove/replace invalid characters)
        $sanitizedInvoiceNumber = preg_replace('/[\/\\\\:*?"<>|]/', '_', $invoice->invoice_number);

        // Generate filename
        $filename = 'invoice_' . $sanitizedInvoiceNumber . '_payments_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Use the beautiful Excel export
        return Excel::download(new SingleInvoicePaymentsExport($invoice), $filename);
    }

    public static function getRelations(): array
    {
        return [
            InvoiceResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
