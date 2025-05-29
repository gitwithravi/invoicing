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
        // Debug: Log the received data to understand what's available
        \Log::info('Invoice creation data received:', [
            'invoice_type' => $data['invoice_type'] ?? 'NOT_SET',
            'customer_group_id_from_data' => $data['customer_group_id'] ?? 'NOT_SET',
            'session_customer_group_id' => session('invoice_customer_group_id'),
            'all_keys' => array_keys($data)
        ]);

        // If invoice type is customer_group, create invoices for all customers in the group
        if ($data['invoice_type'] === 'customer_group') {
            // Try to get customer group ID from session first, then from data
            $customerGroupId = session('invoice_customer_group_id') ?? $data['customer_group_id'] ?? null;

            if (!$customerGroupId) {
                \Log::error('Customer Group ID missing from both session and data:', [
                    'session_value' => session('invoice_customer_group_id'),
                    'data_value' => $data['customer_group_id'] ?? 'NOT_SET',
                    'all_session' => session()->all(),
                    'data' => $data
                ]);
                throw new \Exception('Customer Group ID is required for customer group invoices. Please select a customer group and try again.');
            }

            $customerGroup = CustomerGroup::with('customers')->find($customerGroupId);

            if (!$customerGroup) {
                \Log::error('Customer Group not found:', ['id' => $customerGroupId]);
                throw new \Exception('Customer Group not found. Please select a valid customer group.');
            }

            $customers = $customerGroup->customers;

            if ($customers->isEmpty()) {
                \Log::warning('No customers in group:', ['group_id' => $customerGroupId, 'group_name' => $customerGroup->name]);
                throw new \Exception("No customers found in the selected customer group '{$customerGroup->name}'. Please add customers to this group first.");
            }

            \Log::info('Creating invoices for customer group:', [
                'group_id' => $customerGroupId,
                'group_name' => $customerGroup->name,
                'customer_count' => $customers->count()
            ]);

            $firstInvoice = null;

            // Create an invoice for each customer in the group
            foreach ($customers as $customer) {
                $invoiceData = [
                    'biller_id' => $data['biller_id'],
                    'customer_id' => $customer->id,
                    'invoice_type' => $data['invoice_type'],
                    'ledger_id' => $data['ledger_id'] ?? null,
                    'invoice_number' => $data['invoice_number'] . '/' . $customer->id,
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'],
                    'total_amount' => $data['total_amount'],
                    'amount_paid' => $data['amount_paid'] ?? 0,
                    'amount_due' => $data['amount_due'] ?? $data['total_amount'],
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

            \Log::info('Successfully created invoices for customer group', [
                'group_name' => $customerGroup->name,
                'invoices_created' => $customers->count(),
                'first_invoice_id' => $firstInvoice->id
            ]);

            // Clear the session after successful creation
            session()->forget('invoice_customer_group_id');

            // Return the first invoice to satisfy the create record requirement
            return $firstInvoice;
        }

        // For single customer invoice, remove customer_group_id and create normally
        unset($data['customer_group_id']);
        $data['invoice_type'] = $data['invoice_type'] ?? 'customer';

        // Clear session if it exists (in case user switched from customer group to customer)
        session()->forget('invoice_customer_group_id');

        return parent::handleRecordCreation($data);
    }
}
