<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        /* Base styles */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.2;
            margin-bottom: 50px;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        .no-border td {
            border: none;
        }

        /* Typography */
        .heading {
            font-weight: bold;
            margin-top: 20px;
        }

        .right {
            text-align: right;
            padding: 5px 10px;
        }

        /* Total table styles */
        .total-table {
            width: 100%;
            margin-top: 20px;
        }

        .total-table td {
            border: none;
            padding: 4px;
        }

        .border-top td {
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }

        /* Signature */
        .signature {
            width: 150px;
            margin-top: 40px;
        }

        /* Page number styles */
        @page {
            margin: 0.5cm;
        }

        .page-number {
            text-align: center;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 10px;
            padding: 10px 0;
        }

        /* Image styles */
        img {
            max-width: 100%;
            height: auto;
        }

        /* Watermark styles */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-weight: bold;
            color: rgba(200, 200, 200, 0.3);
            z-index: -1;
            white-space: nowrap;
            pointer-events: none;
            text-transform: uppercase;
            letter-spacing: 10px;
        }

        /* Different colors for different statuses */
        .watermark.paid {
            color: rgba(34, 197, 94, 0.2);
        }

        .watermark.pending {
            color: rgba(251, 191, 36, 0.3);
        }

        .watermark.overdue {
            color: rgba(239, 68, 68, 0.3);
        }

        .watermark.draft {
            color: rgba(156, 163, 175, 0.3);
        }

        .watermark.cancelled {
            color: rgba(239, 68, 68, 0.4);
        }
    </style>
</head>
<body>

    <!-- Watermark -->
    <div class="watermark {{ strtolower($invoice->status) }}">
        {{ $invoice->status }}
    </div>

    <!-- Header Section -->
    <table class="no-border">
        <tr>
            <td>
                <h2>{{ $invoice->biller->business_name }}</h2>
                <p>
                    {{ $invoice->biller->address }}, {{ $invoice->biller->city }},<br>
                    {{ $invoice->biller->state }}, {{ $invoice->biller->country }}, {{ $invoice->biller->zip }}
                </p>
            </td>
            <td style="text-align: right;">
                @if($invoice->biller->logo)
                    @php
                        $logoPath = storage_path('app/public/' . $invoice->biller->logo);
                        $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
                        $logoData = base64_encode(file_get_contents($logoPath));
                    @endphp
                    <img src="data:image/{{ $logoType }};base64,{{ $logoData }}" alt="Logo" style="width: 140px;">
                @endif
            </td>
        </tr>
    </table>

    <hr>

    <!-- Billing Information -->
    <table class="no-border">
        <tr>
            <td>
                <strong>Bill To</strong><br>
                <strong>{{ $invoice->customer->name }}</strong><br>
                {{ $invoice->customer->address }}, {{ $invoice->customer->city }}, {{ $invoice->customer->state }},
                {{ $invoice->customer->country }}, {{ $invoice->customer->zip }}<br>
                @php
                    if ($invoice->customer->phone) {
                        echo '<strong>Phone:</strong> ' . $invoice->customer->phone . '<br>';
                    }
                    if ($invoice->customer->email) {
                        echo '<strong>Email:</strong> ' . $invoice->customer->email . '<br>';
                    }
                    if ($invoice->customer->tax_identifier_number) {
                        echo '<strong>'.$invoice->customer->tax_identifier_name.':</strong> ' . $invoice->customer->tax_identifier_number . '<br>';
                    }
                @endphp
            </td>
            <td class="right">
                <strong>Invoice #:</strong> IN-{{ $invoice->id }}<br>
                <strong>Invoice Date:</strong> {{ $invoice->created_at->format('d/m/Y') }}<br>
                <strong>Due Date:</strong> {{ $invoice->due_date }}
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table>
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total Price</th>
                <th>Tax</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->unit_price }}</td>
                    <td>{{ $item->total_price }}</td>
                    @if($item->tax_rate > 0)
                        <td>{{ $item->tax_name }} @ {{ $item->tax_rate }}%</td>
                    @else
                        <td></td>
                    @endif
                    <td>{{ $item->amount_with_tax }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Section -->
    <table class="total-table">
        <!-- Tax Breakdown -->
        <tr>
            <td colspan="2" style="padding-bottom: 10px;">
                <strong>Tax Breakdown:</strong>
            </td>
        </tr>

        @php
            $taxBreakdown = [];
            $subtotal = 0;

            // Calculate tax breakdown
            foreach($invoice->items as $item) {
                $taxName = $item->tax_name ?? 'No Tax';
                $taxAmount = (float)$item->total_tax_amount;
                $subtotal += (float)$item->amount_with_tax;

                if(!isset($taxBreakdown[$taxName])) {
                    $taxBreakdown[$taxName] = 0;
                }
                $taxBreakdown[$taxName] += $taxAmount;
            }
        @endphp

        @foreach($taxBreakdown as $taxName => $amount)
            <tr>
                <td class="right">{{ $taxName }}:</td>
                <td class="right">₹{{ number_format($amount, 2) }}</td>
            </tr>
        @endforeach

        <tr class="border-top">
            <td class="right"><strong>Subtotal (Inc. Tax):</strong></td>
            <td class="right"><strong>₹{{ number_format($subtotal, 2) }}</strong></td>
        </tr>

        <!-- Extra Charges -->
        @php
            $totalAdjustments = 0;
        @endphp

        @if($invoice->extraCharges->count() > 0)
            <tr>
                <td colspan="2" style="padding: 10px 0;">
                    <strong>Extra Charges & Discounts:</strong>
                </td>
            </tr>

            @foreach($invoice->extraCharges as $charge)
                @php
                    $amount = (float)$charge->amount;
                    if($charge->type === 'discount') {
                        $amount = -$amount;
                        $totalAdjustments -= (float)$charge->amount;
                    } else {
                        $totalAdjustments += (float)$charge->amount;
                    }
                @endphp
                <tr>
                    <td class="right">{{ $charge->name }}:</td>
                    <td class="right" @if($charge->type === 'discount') style="color: #dc2626;" @endif>
                        ₹{{ number_format($amount, 2) }}
                    </td>
                </tr>
            @endforeach
        @endif

        <!-- Final Total -->
        <tr class="border-top">
            <td class="right"><strong>FINAL TOTAL:</strong></td>
            <td class="right"><strong>₹{{ number_format($subtotal + $totalAdjustments, 2) }}</strong></td>
        </tr>
    </table>

    <!-- Signature Section -->
    <table class="no-border">
        <tr>
            <td>&nbsp;</td>
            <td style="text-align: right;">
                @if($invoice->biller->signature_image)
                    @php
                        $sigPath = storage_path('app/public/' . $invoice->biller->signature_image);
                        $sigType = pathinfo($sigPath, PATHINFO_EXTENSION);
                        $sigData = base64_encode(file_get_contents($sigPath));
                    @endphp
                    <img src="data:image/{{ $sigType }};base64,{{ $sigData }}" class="signature" alt="Signature"><br>
                @endif
                <strong>{{ $invoice->biller->signature_name }}</strong>
            </td>
        </tr>
    </table>
    <table>
        @if($invoice->payment_details)
        <tr>
            <td>
                <p class="heading">Payment Details</p>
                <p>{!! $invoice->payment_details !!}</p>
            </td>

        </tr>
        @endif
        @if($invoice->terms)
        <tr>
            <td>
                <p class="heading">Terms & Conditions</p>
                <p>{!! $invoice->terms !!}</p>
            </td>
        </tr>
        @endif
    </table>

    <!-- Page Numbers Script -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} / {PAGE_COUNT}";
            $size = 10;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 35;
            $pdf->page_text($x, $y, $text, $font, $size, array(0,0,0));
        }
    </script>
</body>
</html>


