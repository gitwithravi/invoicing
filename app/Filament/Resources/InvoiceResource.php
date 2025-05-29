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
use App\Services\InvoiceCalculationService;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\InvoiceResource\Pages;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    // Constants for better maintainability
    private const INVOICE_TYPE_CUSTOMER = 'customer';
    private const INVOICE_TYPE_CUSTOMER_GROUP = 'customer_group';
    private const CHARGE_TYPE_DISCOUNT = 'discount';
    private const CHARGE_TYPE_EXTRA = 'extra_charge';
    private const STATUS_PAID = 'paid';
    private const STATUS_UNPAID = 'unpaid';
    private const STATUS_PARTIALLY_PAID = 'partially_paid';

    private const FORM_INPUT_CLASS = 'fi-input-sm';
    private const REPEATER_COLUMNS = 8;
    private const EXTRA_CHARGES_COLUMNS = 3;

    public static function getEloquentQuery(): Builder
    {
        if (Auth::user()->user_type !== 'user') {
            return parent::getEloquentQuery();
        }

        $customer = Customer::where('user_id', auth()->id())->first();

        if (!$customer) {
            // Return empty query if customer not found
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()->where('customer_id', $customer->id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            self::getInvoiceInformationSection(),
            self::getBillerAndCustomerSection(),
            self::getItemsSection(),
            self::getExtraChargesSection(),
            self::getTotalSection(),
            self::getPaymentsSection(),
        ]);
    }

    private static function getInvoiceInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Invoice Information')
            ->schema([
                Forms\Components\Grid::make('2')
                    ->schema([
                        Forms\Components\Grid::make('1')
                            ->schema([
                                self::getLedgerSelect(),
                                self::getInvoiceNumberInput(),
                                self::getInvoiceDatePicker(),
                                self::getDueDatePicker(),
                            ])
                            ->columnSpan(1),
                        self::getBillerLogoPlaceholder()
                            ->columnSpan(1),
                    ])
                    ->columnSpan(1),
            ]);
    }

    private static function getBillerAndCustomerSection(): Forms\Components\Grid
    {
        return Forms\Components\Grid::make(2)
            ->schema([
                self::getBilledBySection(),
                self::getBilledToSection(),
            ]);
    }

    private static function getItemsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Items')
            ->schema([
                self::getItemsRepeater(true), // with relationship
                self::getItemsRepeater(false), // without relationship
            ]);
    }

    private static function getExtraChargesSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Extra Charges')
            ->schema([
                self::getExtraChargesRepeater(true), // with relationship
                self::getExtraChargesRepeater(false), // without relationship
            ]);
    }

    private static function getTotalSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Total')
            ->schema([
                self::getTaxDetailsPlaceholder(),
                self::getExtraChargesPlaceholder(),
                self::getFinalTotalPlaceholder(),
                self::getTotalAmountInput(),
            ]);
    }

    private static function getPaymentsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Payments')
            ->schema([
                self::getAmountPaidPlaceholder(),
                self::getAmountDuePlaceholder(),
            ]);
    }

    // Individual component methods
    private static function getLedgerSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('ledger_id')
            ->label('Ledger')
            ->inlineLabel()
            ->relationship('ledger', 'name')
            ->searchable()
            ->preload()
            ->extraAttributes(['class' => self::FORM_INPUT_CLASS]);
    }

    private static function getInvoiceNumberInput(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('invoice_number')
            ->required()
            ->inlineLabel()
            ->maxLength(255)
            ->extraAttributes(['class' => self::FORM_INPUT_CLASS]);
    }

    private static function getInvoiceDatePicker(): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make('invoice_date')
            ->required()
            ->inlineLabel()
            ->extraAttributes(['class' => self::FORM_INPUT_CLASS]);
    }

    private static function getDueDatePicker(): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make('due_date')
            ->required()
            ->inlineLabel()
            ->extraAttributes(['class' => self::FORM_INPUT_CLASS]);
    }

    private static function getBillerLogoPlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('')
            ->content(function (Get $get) {
                $biller = Biller::find($get('biller_id'));
                if (!$biller || !$biller->logo) {
                    return '';
                }

                return new HtmlString(
                    '<div style="display: flex; justify-content: flex-end; width: 100%;">' .
                    '<img src="/storage/' . $biller->logo . '" alt="Biller Logo" style="width: 180px;">' .
                    '</div>'
                );
            })
            ->live()
            ->extraAttributes(['class' => 'flex justify-end']);
    }

    private static function getBilledBySection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Billed By')
            ->schema([
                Forms\Components\Select::make('biller_id')
                    ->label('')
                    ->relationship('biller', 'business_name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('')
                            ->content(function (Get $get) {
                                $biller = Biller::find($get('biller_id'));
                                return $biller ? self::formatBillerDetails($biller) : '';
                            })
                            ->live()
                            ->columnSpan(1),
                    ]),
            ])
            ->columnSpan(1);
    }

    private static function getBilledToSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Billed To')
            ->schema([
                Forms\Components\Select::make('invoice_type')
                    ->label('')
                    ->options([
                        self::INVOICE_TYPE_CUSTOMER => 'Customer',
                        self::INVOICE_TYPE_CUSTOMER_GROUP => 'Customer Group',
                    ])
                    ->required()
                    ->live()
                    ->default(self::INVOICE_TYPE_CUSTOMER)
                    ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
                Forms\Components\Select::make('customer_group_id')
                    ->label('')
                    ->options(CustomerGroup::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $livewire) {
                        // Store the customer group ID in session for access during creation
                        \Log::info('Customer group selection changed:', [
                            'state' => $state,
                            'livewire_class' => get_class($livewire)
                        ]);

                        if ($state) {
                            session(['invoice_customer_group_id' => $state]);
                            \Log::info('Stored customer group ID in session:', ['value' => $state]);
                        } else {
                            session()->forget('invoice_customer_group_id');
                            \Log::info('Cleared customer group ID from session');
                        }
                    })
                    ->extraAttributes(['class' => self::FORM_INPUT_CLASS])
                    ->visible(fn(Get $get): bool => $get('invoice_type') === self::INVOICE_TYPE_CUSTOMER_GROUP),
                Forms\Components\Select::make('customer_id')
                    ->label('')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->extraAttributes(['class' => self::FORM_INPUT_CLASS])
                    ->visible(fn(Get $get): bool => $get('invoice_type') === self::INVOICE_TYPE_CUSTOMER),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('')
                            ->content(function (Get $get) {
                                if ($get('invoice_type') === self::INVOICE_TYPE_CUSTOMER_GROUP) {
                                    $customerGroupId = $get('customer_group_id');
                                    if ($customerGroupId) {
                                        $customerGroup = CustomerGroup::find($customerGroupId);
                                        if ($customerGroup) {
                                            $customersCount = $customerGroup->customers()->count();
                                            return "Invoice for Customer Group: {$customerGroup->name} ({$customersCount} customers)";
                                        }
                                    }
                                    return 'Select a Customer Group';
                                }

                                $customer = Customer::find($get('customer_id'));
                                return $customer ? self::formatCustomerDetails($customer) : '';
                            })
                            ->live()
                            ->columnSpan(1),
                    ]),
            ])
            ->columnSpan(1);
    }

    private static function getItemsRepeater(bool $withRelationship): Forms\Components\Repeater
    {
        $repeater = Forms\Components\Repeater::make('items')
            ->schema(self::getItemFieldsSchema())
            ->columns(self::REPEATER_COLUMNS);

        if ($withRelationship) {
            $repeater = $repeater
                ->relationship('items')
                ->visible(fn(Get $get): bool => $get('invoice_type') !== self::INVOICE_TYPE_CUSTOMER_GROUP);
        } else {
            $repeater = $repeater
                ->visible(fn(Get $get): bool => $get('invoice_type') === self::INVOICE_TYPE_CUSTOMER_GROUP);
        }

        return $repeater;
    }

    private static function getExtraChargesRepeater(bool $withRelationship): Forms\Components\Repeater
    {
        $repeater = Forms\Components\Repeater::make('extra_charges')
            ->schema(self::getExtraChargeFieldsSchema())
            ->columns(self::EXTRA_CHARGES_COLUMNS);

        if ($withRelationship) {
            $repeater = $repeater
                ->relationship('extraCharges')
                ->visible(fn(Get $get): bool => $get('invoice_type') !== self::INVOICE_TYPE_CUSTOMER_GROUP);
        } else {
            $repeater = $repeater
                ->visible(fn(Get $get): bool => $get('invoice_type') === self::INVOICE_TYPE_CUSTOMER_GROUP);
        }

        return $repeater;
    }

    private static function getItemFieldsSchema(): array
    {
        return [
            Forms\Components\TextInput::make('item_name')
                ->required()
                ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
            Forms\Components\TextInput::make('quantity')
                ->required()
                ->numeric()
                ->live()
                ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateItemTotals($get, $set))
                ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
            Forms\Components\TextInput::make('unit_price')
                ->required()
                ->numeric()
                ->live()
                ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateItemTotals($get, $set))
                ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
            Forms\Components\TextInput::make('total_price')
                ->required()
                ->disabled()
                ->numeric()
                ->dehydrated()
                ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
            Forms\Components\TextInput::make('tax_name')
                ->required()
                ->live()
                ->disabled(fn(Get $get): bool => !$get('total_price'))
                ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
            Forms\Components\TextInput::make('tax_rate')
                ->required()
                ->numeric()
                ->live()
                ->disabled(fn(Get $get): bool => !$get('total_price'))
                ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateTaxAmounts($get, $set))
                ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
            Forms\Components\TextInput::make('total_tax_amount')
                ->required()
                ->disabled()
                ->numeric()
                ->dehydrated()
                ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
            Forms\Components\TextInput::make('amount_with_tax')
                ->required()
                ->disabled()
                ->numeric()
                ->dehydrated()
                ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
        ];
    }

    private static function getExtraChargeFieldsSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
            Forms\Components\TextInput::make('amount')
                ->required()
                ->numeric()
                ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
            Forms\Components\Select::make('type')
                ->required()
                ->options([
                    self::CHARGE_TYPE_DISCOUNT => 'Discount',
                    self::CHARGE_TYPE_EXTRA => 'Extra Charge',
                ])
                ->extraAttributes(['class' => self::FORM_INPUT_CLASS]),
        ];
    }

    // Calculation methods
    private static function calculateItemTotals(Get $get, Set $set): void
    {
        $quantity = floatval($get('quantity') ?? 0);
        $unitPrice = floatval($get('unit_price') ?? 0);
        $taxRate = floatval($get('tax_rate') ?? 0);

        $calculations = InvoiceCalculationService::calculateItemTotals($quantity, $unitPrice, $taxRate);

        $set('total_price', $calculations['total_price']);
        $set('total_tax_amount', $calculations['total_tax_amount']);
        $set('amount_with_tax', $calculations['amount_with_tax']);
    }

    private static function calculateTaxAmounts(Get $get, Set $set): void
    {
        $totalPrice = floatval($get('total_price') ?? 0);
        $taxRate = floatval($get('tax_rate') ?? 0);

        $calculations = InvoiceCalculationService::calculateTaxAmounts($totalPrice, $taxRate);

        $set('total_tax_amount', $calculations['total_tax_amount']);
        $set('amount_with_tax', $calculations['amount_with_tax']);
    }

    // Formatting methods
    private static function formatBillerDetails(Biller $biller): HtmlString
    {
        $html = '
            <div>
                <h3>' . e($biller->business_name) . '</h3>
                <p>' . e($biller->address) . '</p>
                <p>' . e($biller->city) . ', ' . e($biller->state) . ', ' . e($biller->zip) . '</p>
                <p>' . e($biller->country) . '</p>
            </div>
        ';

        return new HtmlString($html);
    }

    private static function formatCustomerDetails(Customer $customer): HtmlString
    {
        $html = '
            <div>
                <h3>' . e($customer->name) . '</h3>
                <p>' . e($customer->address) . '</p>
                <p>' . e($customer->city) . ', ' . e($customer->state) . ', ' . e($customer->zip) . '</p>
                <p>' . e($customer->country) . '</p>
            </div>
        ';

        return new HtmlString($html);
    }

    // Placeholder methods
    private static function getTaxDetailsPlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('Tax Details')
            ->content(function (Get $get, Set $set) {
                $items = $get('items') ?? [];
                $breakdown = InvoiceCalculationService::calculateTaxBreakdown($items);

                return self::formatTaxBreakdown($breakdown['breakdown'], $breakdown['total']);
            })
            ->live()
            ->extraAttributes(['class' => self::FORM_INPUT_CLASS]);
    }

    private static function getExtraChargesPlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('Extra Charges')
            ->content(function (Get $get, Set $set) {
                $extraCharges = $get('extra_charges') ?? [];
                return self::formatExtraCharges($extraCharges);
            })
            ->live()
            ->extraAttributes(['class' => self::FORM_INPUT_CLASS]);
    }

    private static function getFinalTotalPlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('Final Total')
            ->content(function (Get $get, Set $set) {
                $items = $get('items') ?? [];
                $extraCharges = $get('extra_charges') ?? [];

                $calculations = InvoiceCalculationService::calculateFinalTotal($items, $extraCharges);
                $set('total_amount', $calculations['final_total']);

                return new HtmlString(sprintf(
                    '<div class="pt-2 border-t mt-2">
                        <div class="flex justify-between font-medium text-lg">
                            <span>Final Total:</span>
                            <span>%.2f</span>
                        </div>
                    </div>',
                    $calculations['final_total']
                ));
            })
            ->live()
            ->extraAttributes(['class' => self::FORM_INPUT_CLASS]);
    }

    private static function getTotalAmountInput(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('total_amount')
            ->required()
            ->disabled()
            ->numeric()
            ->dehydrated()
            ->extraAttributes(['class' => self::FORM_INPUT_CLASS]);
    }

    private static function getAmountPaidPlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('')
            ->content(function (Get $get) {
                $amountPaid = $get('amount_paid') ?? 0;
                return new HtmlString(sprintf(
                    '<div class="pt-2 border-t mt-2">
                        <div class="flex justify-between font-medium text-lg">
                            <span>Amount Paid:</span>
                            <span>%.2f</span>
                        </div>
                    </div>',
                    $amountPaid
                ));
            })
            ->live()
            ->extraAttributes(['class' => self::FORM_INPUT_CLASS]);
    }

    private static function getAmountDuePlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('')
            ->content(function (Get $get) {
                $amountDue = $get('amount_due') ?? 0;
                return new HtmlString(sprintf(
                    '<div class="pt-2 border-t mt-2">
                        <div class="flex justify-between font-medium text-lg">
                            <span>Amount Due:</span>
                            <span>%.2f</span>
                        </div>
                    </div>',
                    $amountDue
                ));
            })
            ->live()
            ->extraAttributes(['class' => self::FORM_INPUT_CLASS]);
    }

    private static function formatTaxBreakdown(array $taxBreakdown, float $totalAmount): HtmlString
    {
        $output = '<div class="space-y-2">';
        $output .= '<div class="font-medium">Tax Breakdown:</div>';

        foreach ($taxBreakdown as $taxName => $amount) {
            $output .= sprintf(
                '<div class="flex justify-between">
                    <span>%s:</span>
                    <span>%.2f</span>
                </div>',
                e($taxName),
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
    }

    private static function formatExtraCharges(array $extraCharges): HtmlString
    {
        $output = '<div class="space-y-2">';
        $output .= '<div class="font-medium">Extra Charges & Discounts:</div>';

        foreach ($extraCharges as $charge) {
            $amount = floatval($charge['amount'] ?? 0);
            $type = $charge['type'] ?? self::CHARGE_TYPE_EXTRA;
            $name = $charge['name'] ?? '';

            if ($type === self::CHARGE_TYPE_DISCOUNT) {
                $amount = -$amount; // Show discount as negative
            }

            $output .= sprintf(
                '<div class="flex justify-between">
                    <span>%s:</span>
                    <span class="%s">%.2f</span>
                </div>',
                e($name),
                $type === self::CHARGE_TYPE_DISCOUNT ? 'text-danger-500' : '',
                $amount
            );
        }

        $output .= '</div>';
        return new HtmlString($output);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        self::INVOICE_TYPE_CUSTOMER => 'success',
                        self::INVOICE_TYPE_CUSTOMER_GROUP => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        self::INVOICE_TYPE_CUSTOMER => 'Customer',
                        self::INVOICE_TYPE_CUSTOMER_GROUP => 'Customer Group',
                        default => ucfirst($state),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->visible(fn ($record) => $record?->invoice_type === self::INVOICE_TYPE_CUSTOMER),
                Tables\Columns\TextColumn::make('ledger.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->date('d-m-Y'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date('d-m-Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_amount')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_due')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(self::getTableFilters())
            ->headerActions([
                self::getExportAllPaymentsAction(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    self::getDownloadInvoiceAction(),
                    self::getDownloadReceiptAction(),
                    self::getExportInvoicePaymentsAction(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    self::getExportPaymentsReportAction(),
                ]),
            ]);
    }

    private static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('invoice_type')
                ->options([
                    self::INVOICE_TYPE_CUSTOMER => 'Customer',
                    self::INVOICE_TYPE_CUSTOMER_GROUP => 'Customer Group',
                ]),
            Tables\Filters\SelectFilter::make('customer_id')
                ->options(Customer::all()->pluck('name', 'id'))
                ->searchable(),
            Tables\Filters\SelectFilter::make('status')
                ->options(Invoice::pluck('status', 'status'))
                ->searchable(),
            Tables\Filters\SelectFilter::make('ledger_id')
                ->options(Ledger::all()->pluck('name', 'id'))
                ->searchable(),
        ];
    }

    private static function getExportAllPaymentsAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('export_all_payments')
            ->label('Export All Payments (Excel)')
            ->icon('heroicon-o-document-chart-bar')
            ->color('warning')
            ->action(function () {
                try {
                    $invoices = Invoice::with(['customer', 'biller', 'payments'])->get();

                    Notification::make()
                        ->title('Excel Export Started')
                        ->body('Generating comprehensive Excel payments report for ' . $invoices->count() . ' invoice(s)...')
                        ->info()
                        ->send();

                    return self::exportPaymentsReport($invoices);
                } catch (\Exception $e) {
                    self::handleExportError('All Payments Excel Export Error', $e);
                    return null;
                }
            });
    }

    private static function getDownloadInvoiceAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('generateInvoicePdf')
            ->label('Download Invoice')
            ->icon('heroicon-o-document-arrow-down')
            ->action(function (Invoice $record) {
                try {
                    return self::generatePdf('invoices.pdf', $record, 'Invoice');
                } catch (\Exception $e) {
                    \Log::error('PDF Generation Error: ' . $e->getMessage());
                    throw $e;
                }
            })
            ->color('primary');
    }

    private static function getDownloadReceiptAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('generateReceiptPdf')
            ->label('Download Receipt')
            ->icon('heroicon-o-document-arrow-down')
            ->action(function (Invoice $record) {
                try {
                    return self::generatePdf('receipts.pdf', $record, 'Receipt');
                } catch (\Exception $e) {
                    \Log::error('PDF Generation Error: ' . $e->getMessage());
                    throw $e;
                }
            })
            ->color('primary')
            ->visible(fn($record): bool => $record->status === self::STATUS_PAID);
    }

    private static function getExportInvoicePaymentsAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('export_invoice_payments')
            ->label('Export Invoice Payments (Excel)')
            ->icon('heroicon-o-table-cells')
            ->color('info')
            ->action(function (Invoice $record) {
                try {
                    Notification::make()
                        ->title('Excel Export Started')
                        ->body('Generating Excel payments report for invoice ' . $record->invoice_number)
                        ->info()
                        ->send();

                    return self::exportSingleInvoicePayments($record);
                } catch (\Exception $e) {
                    self::handleExportError('Single Invoice Payments Excel Export Error', $e);
                    return null;
                }
            });
    }

    private static function getExportPaymentsReportAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('export_payments_report')
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
                    self::handleExportError('Payments Report Excel Export Error', $e);
                    return null;
                }
            })
            ->deselectRecordsAfterCompletion();
    }

    // Helper methods
    private static function generatePdf(string $view, Invoice $record, string $type): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $pdf = Pdf::loadView($view, ['invoice' => $record])
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
            "{$type}-{$record->id}.pdf"
        );
    }

    private static function handleExportError(string $logMessage, \Exception $e): void
    {
        Notification::make()
            ->title('Excel Export Failed')
            ->body('There was an error generating the Excel payments report: ' . $e->getMessage())
            ->danger()
            ->send();

        \Log::error($logMessage . ': ' . $e->getMessage());
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
