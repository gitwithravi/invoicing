<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\CustomerGroup;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // If invoice type is customer_group, create invoices for all customers in the group
        if ($data['invoice_type'] === 'customer_group') {
            $customerGroup = CustomerGroup::find($data['customer_group_id']);
            $customers = $customerGroup->customers;
            $firstInvoice = null;

            // Create an invoice for each customer in the group
            foreach ($customers as $customer) {
                $invoiceData = [
                    'biller_id' => $data['biller_id'],
                    'customer_id' => $customer->id,
                    'invoice_number' => $data['invoice_number'] . '/' . $customer->id,
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'],
                    'total_amount' => $data['total_amount'],
                    'status' => $data['status'] ?? 'created',
                    'payment_details' => $data['payment_details'] ?? null,
                    'terms' => $data['terms'] ?? null,
                ];

                // Create the invoice
                $invoice = Invoice::create($invoiceData);

                // Create invoice items
                if (isset($data['items']) && is_array($data['items'])) {
                    foreach ($data['items'] as $item) {
                        $invoice->items()->create([
                            'item_name' => $item['item_name'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'total_price' => $item['total_price'],
                            'tax_name' => $item['tax_name'],
                            'tax_rate' => $item['tax_rate'],
                            'total_tax_amount' => $item['total_tax_amount'],
                            'amount_with_tax' => $item['amount_with_tax'],
                        ]);
                    }
                }

                // Create extra charges
                if (isset($data['extra_charges']) && is_array($data['extra_charges'])) {
                    foreach ($data['extra_charges'] as $charge) {
                        $invoice->extraCharges()->create([
                            'name' => $charge['name'],
                            'amount' => $charge['amount'],
                            'type' => $charge['type'],
                        ]);
                    }
                }

                // Store the first invoice for return
                if ($firstInvoice === null) {
                    $firstInvoice = $invoice;
                }
            }

            // Return the first invoice to satisfy the create record requirement
            return $firstInvoice;
        }

        // For single customer invoice, remove the form-only fields and create normally
        unset($data['invoice_type'], $data['customer_group_id']);
        return parent::handleRecordCreation($data);
    }
}
