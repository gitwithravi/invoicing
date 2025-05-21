<?php
namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Biller;
use App\Models\Customer;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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
                                Forms\Components\Select::make('customer_id')
                                    ->label('')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('')
                                            ->content(function (Get $get) {
                                                $customer = $get('customer_id');
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
                                        $quantity = floatval($get('quantity') ?? 0);
                                        $unitPrice = floatval($get('unit_price') ?? 0);
                                        $set('total_price', $quantity * $unitPrice);

                                        // Recalculate tax amounts
                                        $totalPrice = $quantity * $unitPrice;
                                        $taxRate = floatval($get('tax_rate') ?? 0);
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
                                        $quantity = floatval($get('quantity') ?? 0);
                                        $unitPrice = floatval($get('unit_price') ?? 0);
                                        $set('total_price', $quantity * $unitPrice);

                                        // Recalculate tax amounts
                                        $totalPrice = $quantity * $unitPrice;
                                        $taxRate = floatval($get('tax_rate') ?? 0);
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
                                    ->disabled(fn (Get $get): bool => !$get('total_price'))
                                    ->extraAttributes(['class' => 'fi-input-sm']),
                                Forms\Components\TextInput::make('tax_rate')
                                    ->required()
                                    ->numeric()
                                    ->live()
                                    ->disabled(fn (Get $get): bool => !$get('total_price'))
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $totalPrice = floatval($get('total_price') ?? 0);
                                        $taxRate = floatval($get('tax_rate') ?? 0);
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
                            ->columns(8),
                        Forms\Components\Placeholder::make('Tax Details')
                            ->content(function (Get $get, Set $set) {
                                $items = $get('items') ?? [];
                                $taxBreakdown = [];
                                $totalAmount = 0;

                                // Group items by tax_name and calculate totals
                                foreach ($items as $item) {
                                    $taxName = $item['tax_name'] ?? 'No Tax';
                                    $taxAmount = floatval($item['total_tax_amount'] ?? 0);
                                    $totalAmount += floatval($item['amount_with_tax'] ?? 0);

                                    if (!isset($taxBreakdown[$taxName])) {
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
                                            <span>Total Amount (Inc. Tax):</span>
                                            <span>%.2f</span>
                                        </div>
                                    </div>',
                                    $totalAmount
                                );

                                $output .= '</div>';

                                // Set the total_amount field
                                $set('total_amount', $totalAmount);

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
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
