<?php
namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Biller;
use App\Models\Invoice;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CustomerGroup;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use App\Filament\Resources\InvoiceResource\Pages;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                                        'customer' => 'Customer',
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
                                $extraCharges = $get('extra_charges') ?? [];
                                $totalDiscount = 0;
                                $totalExtraCharge = 0;

                                // Build the HTML output
                                $output = '<div class="space-y-2">';
                                $output .= '<div class="font-medium">Extra Charges & Discounts:</div>';

                                foreach ($extraCharges as $charge) {
                                    $amount = floatval($charge['amount'] ?? 0);
                                    $type = $charge['type'] ?? 'extra_charge';
                                    $name = $charge['name'] ?? '';

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
                                $items = $get('items') ?? [];
                                $subtotal = 0;
                                foreach ($items as $item) {
                                    $subtotal += floatval($item['amount_with_tax'] ?? 0);
                                }

                                // Calculate extra charges and discounts
                                $extraCharges = $get('extra_charges') ?? [];
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
                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\RichEditor::make('payment_details')
                            ->extraAttributes(['class' => 'fi-input-sm']),
                        Forms\Components\RichEditor::make('terms')
                            ->extraAttributes(['class' => 'fi-input-sm']),
                    ]),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number'),
                Tables\Columns\TextColumn::make('customer.name'),
                Tables\Columns\TextColumn::make('total_amount'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (\App\Models\Invoice $record) {
                    try {
                        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $record])
                            ->setPaper('a4', 'portrait')
                            ->setOptions([
                                'isPhpEnabled' => true,
                                'isRemoteEnabled' => true,
                                'margin_bottom' => 20,
                                'defaultFont' => 'DejaVu Sans',
                                'chroot' => storage_path('app/public'),
                                'enable_remote' => true,
                                'log_output_file' => storage_path('logs/dompdf.html')
                            ]);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            "Invoice-{$record->id}.pdf"
                        );
                    } catch (\Exception $e) {
                        \Log::error('PDF Generation Error: ' . $e->getMessage());
                        throw $e;
                    }
                })
                ->color('primary'),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
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
