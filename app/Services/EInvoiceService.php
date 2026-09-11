<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\EInvoice;
use App\Models\EInvoiceSetting;
use App\Models\Order;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EInvoiceService
{
    /**
     * Generate an e-Invoice for a given Order (from POS or E-Commerce).
     */
    public function generateForOrder(Order $order, array $customBuyerDetails = []): EInvoice
    {
        // Avoid duplicate active e-invoices for the same order
        if ($order->eInvoice && $order->eInvoice->status !== 'cancelled') {
            return $order->eInvoice;
        }

        $settings = EInvoiceSetting::current();
        $order->loadMissing(['items.product', 'user', 'payment']);

        $isPos = $order->source === 'pos';
        $prefix = $isPos ? 'POS-INV' : 'INV';
        $year = now()->format('Y');
        $seq = str_pad((string) (EInvoice::whereYear('created_at', now()->year)->count() + 1), 6, '0', STR_PAD_LEFT);
        $invoiceNumber = "{$prefix}-{$year}-{$seq}";

        // Generate standard IRBM (LHDN) Unique UUID (format: IRBM-YYYYMMDD-HEX)
        $datePart = now()->format('Ymd');
        $hexPart = strtoupper(Str::random(12));
        $irbmUniqueId = "IRBM-{$datePart}-{$hexPart}";
        $submissionUid = 'SUB-' . strtoupper(Str::random(16));

        // Buyer Information resolution
        $buyerName = $customBuyerDetails['buyer_name']
            ?? $order->shipping_name
            ?? ($order->user ? $order->user->name : 'Walk-in Customer');

        $buyerTin = $customBuyerDetails['buyer_tin']
            ?? $order->buyer_tin
            ?? ($order->user && $order->user->tin ? $order->user->tin : 'EI00000000020'); // Default general public TIN

        $buyerIdType = $customBuyerDetails['buyer_id_type']
            ?? $order->buyer_id_type
            ?? ($order->user && $order->user->id_type ? $order->user->id_type : ($buyerTin === 'EI00000000020' ? 'GENERAL_PUBLIC' : 'NRIC'));

        $buyerIdVal = $customBuyerDetails['buyer_id_value']
            ?? $order->buyer_id_number
            ?? ($order->user && $order->user->id_number ? $order->user->id_number : '000000000000');

        $buyerSst = $customBuyerDetails['buyer_sst_no']
            ?? $order->buyer_sst_no
            ?? ($order->user ? $order->user->sst_number : null);

        $buyerEmail = $customBuyerDetails['buyer_email']
            ?? ($order->user ? $order->user->email : 'customer@pos.local');

        $buyerPhone = $customBuyerDetails['buyer_phone']
            ?? $order->shipping_phone
            ?? ($order->user && $order->user->phone ? $order->user->phone : 'N/A');

        $buyerAddress = $customBuyerDetails['buyer_address']
            ?? $order->shipping_address
            ?? 'Malaysia';

        // Calculation of lines, tax & totals
        $subtotal = 0.00;
        $itemsPayload = [];
        $taxRate = (float) $settings->default_tax_rate;
        $taxTypeCode = $settings->default_tax_type_code ?? '06'; // 06: Tax Exempt / 0%

        foreach ($order->items as $index => $item) {
            $lineSubtotal = round((float) $item->subtotal, 2);
            $subtotal += $lineSubtotal;

            $itemsPayload[] = [
                'line_id' => $index + 1,
                'product_name' => $item->product ? $item->product->name : 'Item #' . ($index + 1),
                'product_sku' => ($item->variation && $item->variation->sku) ? $item->variation->sku : ($item->product->sku ?? 'SKU-GEN'),
                'classification_code' => '022', // Standard classification code (LHDN e-commerce/retail goods)
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'tax_rate' => $taxRate,
                'tax_type' => $taxTypeCode,
                'tax_amount' => round(($lineSubtotal * $taxRate) / 100, 2),
                'subtotal' => $lineSubtotal,
            ];
        }

        $subtotal = round($subtotal, 2);
        $grandTotal = (float) $order->total_amount;
        $discount = max(0.00, round($subtotal - $grandTotal, 2));
        $taxAmount = round((($grandTotal) * $taxRate) / 100, 2);

        // Build standard LHDN UBL 2.1 JSON structure
        $ublPayload = $this->buildUbl21Json(
            $invoiceNumber,
            $irbmUniqueId,
            $settings,
            [
                'tin' => $buyerTin,
                'name' => $buyerName,
                'id_type' => $buyerIdType,
                'id_val' => $buyerIdVal,
                'sst' => $buyerSst,
                'email' => $buyerEmail,
                'phone' => $buyerPhone,
                'address' => $buyerAddress,
            ],
            $itemsPayload,
            $subtotal,
            $discount,
            $taxAmount,
            $grandTotal,
            $order->order_number
        );

        $documentHash = hash('sha256', json_encode($ublPayload, JSON_UNESCAPED_SLASHES));

        // Public verification URL (when QR is scanned)
        $qrCodeUrl = route('einvoice.verify', ['uuid' => $irbmUniqueId]);

        $eInvoice = EInvoice::create([
            'order_id' => $order->id,
            'invoice_number' => $invoiceNumber,
            'irbm_unique_id' => $irbmUniqueId,
            'submission_uid' => $submissionUid,
            'invoice_type' => '01',
            'source' => $isPos ? 'pos' : 'ecommerce',
            'status' => 'valid', // Simulation engine defaults to validated status
            'supplier_tin' => $settings->company_tin,
            'supplier_name' => $settings->company_name,
            'supplier_id_type' => 'BRN',
            'supplier_id_value' => $settings->company_reg_no,
            'supplier_msic_code' => $settings->msic_code,
            'supplier_msic_desc' => $settings->msic_description,
            'supplier_sst_no' => $settings->company_sst_no,
            'supplier_email' => $settings->contact_email,
            'supplier_phone' => $settings->contact_phone,
            'supplier_address' => "{$settings->address_line1}, {$settings->postal_code} {$settings->city}, {$settings->state}, {$settings->country}",
            'buyer_tin' => $buyerTin,
            'buyer_name' => $buyerName,
            'buyer_id_type' => $buyerIdType,
            'buyer_id_value' => $buyerIdVal,
            'buyer_sst_no' => $buyerSst,
            'buyer_email' => $buyerEmail,
            'buyer_phone' => $buyerPhone,
            'buyer_address' => $buyerAddress,
            'currency_code' => 'MYR',
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'tax_rate' => $taxRate,
            'tax_type_code' => $taxTypeCode,
            'tax_amount' => $taxAmount,
            'total_payable_amount' => $grandTotal,
            'document_hash' => $documentHash,
            'qr_code_url' => $qrCodeUrl,
            'ubl_payload' => $ublPayload,
            'lhdn_response' => [
                'status' => 'Valid',
                'statusCode' => '200',
                'uuid' => $irbmUniqueId,
                'submissionUid' => $submissionUid,
                'dateTimeValidated' => now()->toIso8601String(),
                'validationResults' => [
                    'status' => 'Valid',
                    'warnings' => [],
                    'errors' => [],
                ],
                'mode' => $settings->mode,
                'environment' => 'LHDN MyInvois Simulation & Compliance Engine',
            ],
            'issued_at' => now(),
            'validated_at' => now(),
        ]);

        ActivityLog::record('einvoice.generated', [
            'order_id' => $order->id,
            'invoice_number' => $invoiceNumber,
            'irbm_unique_id' => $irbmUniqueId,
            'source' => $eInvoice->source,
            'total' => $grandTotal,
        ]);

        return $eInvoice;
    }

    /**
     * Generate a Consolidated e-Invoice (Invois Disatukan) for a date period.
     */
    public function generateConsolidatedInvoice(Carbon $startDate, Carbon $endDate): EInvoice
    {
        $settings = EInvoiceSetting::current();

        // Get completed orders in period that don't have an individual e-invoice yet (or POS walk-in orders)
        $orders = Order::whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->where('status', 'completed')
            ->whereDoesntHave('eInvoice', function ($q) {
                $q->where('invoice_type', '01');
            })
            ->with(['items.product'])
            ->get();

        if ($orders->isEmpty()) {
            throw new Exception('No eligible completed orders found in the selected date range for Consolidated e-Invoice.');
        }

        $year = now()->format('Y');
        $month = $startDate->format('m');
        $seq = str_pad((string) (EInvoice::where('invoice_type', 'consolidated')->count() + 1), 4, '0', STR_PAD_LEFT);
        $invoiceNumber = "CONSOLIDATED-INV-{$year}{$month}-{$seq}";

        $datePart = now()->format('Ymd');
        $hexPart = strtoupper(Str::random(12));
        $irbmUniqueId = "IRBM-CONSOL-{$datePart}-{$hexPart}";
        $submissionUid = 'SUB-CONSOL-' . strtoupper(Str::random(16));

        $totalSubtotal = 0.00;
        $totalPayable = 0.00;
        $consolidatedLines = [];
        $lineIndex = 1;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $lineSubtotal = round((float) $item->subtotal, 2);
                $totalSubtotal += $lineSubtotal;

                $consolidatedLines[] = [
                    'line_id' => $lineIndex++,
                    'order_number' => $order->order_number,
                    'product_name' => $item->product ? $item->product->name : 'Science Specimen',
                    'product_sku' => $item->product->sku ?? 'SKU-GEN',
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => $lineSubtotal,
                ];
            }
            $totalPayable += (float) $order->total_amount;
        }

        $totalDiscount = max(0.00, round($totalSubtotal - $totalPayable, 2));
        $qrCodeUrl = route('einvoice.verify', ['uuid' => $irbmUniqueId]);

        $ublPayload = [
            '_D' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            'ID' => $invoiceNumber,
            'IssueDate' => now()->format('Y-m-d'),
            'IssueTime' => now()->format('H:i:s\Z'),
            'InvoiceTypeCode' => ['_value' => '01', 'listVersionID' => '1.0', 'name' => 'Consolidated e-Invoice'],
            'DocumentCurrencyCode' => 'MYR',
            'InvoicePeriod' => [
                'StartDate' => $startDate->format('Y-m-d'),
                'EndDate' => $endDate->format('Y-m-d'),
                'Description' => 'Consolidated Monthly Retail Sales',
            ],
            'AccountingSupplierParty' => [
                'PartyTaxScheme' => ['CompanyID' => $settings->company_tin],
                'PartyLegalEntity' => ['RegistrationName' => $settings->company_name],
            ],
            'AccountingCustomerParty' => [
                'PartyTaxScheme' => ['CompanyID' => 'EI00000000020'],
                'PartyLegalEntity' => ['RegistrationName' => 'General Public (B2C Retail Summary)'],
            ],
            'LegalMonetaryTotal' => [
                'LineExtensionAmount' => $totalSubtotal,
                'AllowanceTotalAmount' => $totalDiscount,
                'TaxExclusiveAmount' => $totalPayable,
                'TaxInclusiveAmount' => $totalPayable,
                'PayableAmount' => $totalPayable,
            ],
            'ConsolidatedOrdersCount' => $orders->count(),
            'InvoiceLines' => $consolidatedLines,
        ];

        $eInvoice = EInvoice::create([
            'order_id' => null,
            'invoice_number' => $invoiceNumber,
            'irbm_unique_id' => $irbmUniqueId,
            'submission_uid' => $submissionUid,
            'invoice_type' => 'consolidated',
            'source' => 'manual',
            'status' => 'valid',
            'supplier_tin' => $settings->company_tin,
            'supplier_name' => $settings->company_name,
            'supplier_id_type' => 'BRN',
            'supplier_id_value' => $settings->company_reg_no,
            'supplier_msic_code' => $settings->msic_code,
            'supplier_msic_desc' => $settings->msic_description,
            'supplier_sst_no' => $settings->company_sst_no,
            'supplier_email' => $settings->contact_email,
            'supplier_phone' => $settings->contact_phone,
            'supplier_address' => "{$settings->address_line1}, {$settings->city}, {$settings->country}",
            'buyer_tin' => 'EI00000000020',
            'buyer_name' => 'General Public (Consolidated Summary)',
            'buyer_id_type' => 'GENERAL_PUBLIC',
            'buyer_id_value' => '000000000000',
            'currency_code' => 'MYR',
            'subtotal_amount' => $totalSubtotal,
            'discount_amount' => $totalDiscount,
            'tax_rate' => 0.00,
            'tax_type_code' => '06',
            'tax_amount' => 0.00,
            'total_payable_amount' => $totalPayable,
            'document_hash' => hash('sha256', json_encode($ublPayload)),
            'qr_code_url' => $qrCodeUrl,
            'ubl_payload' => $ublPayload,
            'lhdn_response' => [
                'status' => 'Valid',
                'statusCode' => '200',
                'uuid' => $irbmUniqueId,
                'submissionUid' => $submissionUid,
                'consolidatedCount' => $orders->count(),
                'dateTimeValidated' => now()->toIso8601String(),
                'mode' => $settings->mode,
            ],
            'issued_at' => now(),
            'validated_at' => now(),
        ]);

        ActivityLog::record('einvoice.consolidated_generated', [
            'invoice_number' => $invoiceNumber,
            'irbm_unique_id' => $irbmUniqueId,
            'orders_count' => $orders->count(),
            'total' => $totalPayable,
        ]);

        return $eInvoice;
    }

    /**
     * Build exact LHDN UBL 2.1 JSON Schema array.
     */
    protected function buildUbl21Json(
        string $invoiceNumber,
        string $uuid,
        EInvoiceSetting $settings,
        array $buyer,
        array $lines,
        float $subtotal,
        float $discount,
        float $taxAmount,
        float $grandTotal,
        string $internalOrderRef
    ): array {
        return [
            '_D' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            '_A' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
            '_B' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
            'ID' => [
                '_value' => $invoiceNumber,
            ],
            'IssueDate' => [
                '_value' => now()->format('Y-m-d'),
            ],
            'IssueTime' => [
                '_value' => now()->format('H:i:s\Z'),
            ],
            'InvoiceTypeCode' => [
                '_value' => '01',
                'listVersionID' => '1.0',
            ],
            'DocumentCurrencyCode' => [
                '_value' => 'MYR',
            ],
            'TaxCurrencyCode' => [
                '_value' => 'MYR',
            ],
            'BillingReference' => [
                'AdditionalDocumentReference' => [
                    'ID' => [
                        '_value' => $internalOrderRef,
                    ],
                ],
            ],
            'AccountingSupplierParty' => [
                'Party' => [
                    'IndustryClassificationCode' => [
                        '_value' => $settings->msic_code,
                        'name' => $settings->msic_description,
                    ],
                    'PartyIdentification' => [
                        [
                            'ID' => [
                                '_value' => $settings->company_reg_no,
                                'schemeID' => 'BRN',
                            ],
                        ],
                    ],
                    'PostalAddress' => [
                        'AddressLine' => [
                            ['Line' => ['_value' => $settings->address_line1]],
                            ['Line' => ['_value' => $settings->address_line2 ?? '']],
                        ],
                        'CityName' => ['_value' => $settings->city],
                        'PostalZone' => ['_value' => $settings->postal_code],
                        'CountrySubentityCode' => ['_value' => '14'], // KL / Putrajaya code
                        'Country' => [
                            'IdentificationCode' => [
                                '_value' => $settings->country,
                                'listID' => 'ISO3166-1',
                                'listAgencyID' => '6',
                            ],
                        ],
                    ],
                    'PartyLegalEntity' => [
                        'RegistrationName' => [
                            '_value' => $settings->company_name,
                        ],
                    ],
                    'PartyTaxScheme' => [
                        'CompanyID' => [
                            '_value' => $settings->company_tin,
                        ],
                        'TaxScheme' => [
                            'ID' => [
                                '_value' => 'OTH',
                                'schemeID' => 'UN/ECE 5153',
                                'schemeAgencyID' => '6',
                            ],
                        ],
                    ],
                    'Contact' => [
                        'Telephone' => ['_value' => $settings->contact_phone],
                        'ElectronicMail' => ['_value' => $settings->contact_email],
                    ],
                ],
            ],
            'AccountingCustomerParty' => [
                'Party' => [
                    'PartyIdentification' => [
                        [
                            'ID' => [
                                '_value' => $buyer['id_val'],
                                'schemeID' => $buyer['id_type'],
                            ],
                        ],
                    ],
                    'PostalAddress' => [
                        'AddressLine' => [
                            ['Line' => ['_value' => $buyer['address']]],
                        ],
                        'Country' => [
                            'IdentificationCode' => [
                                '_value' => 'MYS',
                            ],
                        ],
                    ],
                    'PartyLegalEntity' => [
                        'RegistrationName' => [
                            '_value' => $buyer['name'],
                        ],
                    ],
                    'PartyTaxScheme' => [
                        'CompanyID' => [
                            '_value' => $buyer['tin'],
                        ],
                        'TaxScheme' => [
                            'ID' => [
                                '_value' => 'OTH',
                            ],
                        ],
                    ],
                    'Contact' => [
                        'Telephone' => ['_value' => $buyer['phone']],
                        'ElectronicMail' => ['_value' => $buyer['email']],
                    ],
                ],
            ],
            'TaxTotal' => [
                [
                    'TaxAmount' => [
                        '_value' => $taxAmount,
                        'currencyID' => 'MYR',
                    ],
                    'TaxSubtotal' => [
                        [
                            'TaxableAmount' => [
                                '_value' => $grandTotal,
                                'currencyID' => 'MYR',
                            ],
                            'TaxAmount' => [
                                '_value' => $taxAmount,
                                'currencyID' => 'MYR',
                            ],
                            'TaxCategory' => [
                                'ID' => [
                                    '_value' => $settings->default_tax_type_code ?? '06',
                                ],
                                'Percent' => [
                                    '_value' => (float) $settings->default_tax_rate,
                                ],
                                'TaxScheme' => [
                                    'ID' => [
                                        '_value' => 'OTH',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'LegalMonetaryTotal' => [
                'LineExtensionAmount' => [
                    '_value' => $subtotal,
                    'currencyID' => 'MYR',
                ],
                'TaxExclusiveAmount' => [
                    '_value' => $grandTotal,
                    'currencyID' => 'MYR',
                ],
                'TaxInclusiveAmount' => [
                    '_value' => $grandTotal,
                    'currencyID' => 'MYR',
                ],
                'AllowanceTotalAmount' => [
                    '_value' => $discount,
                    'currencyID' => 'MYR',
                ],
                'PayableAmount' => [
                    '_value' => $grandTotal,
                    'currencyID' => 'MYR',
                ],
            ],
            'InvoiceLine' => array_map(function ($line) {
                return [
                    'ID' => ['_value' => (string) $line['line_id']],
                    'InvoicedQuantity' => [
                        '_value' => $line['quantity'],
                        'unitCode' => 'H87', // Piece
                    ],
                    'LineExtensionAmount' => [
                        '_value' => $line['subtotal'],
                        'currencyID' => 'MYR',
                    ],
                    'Item' => [
                        'Description' => ['_value' => $line['product_name']],
                        'CommodityClassification' => [
                            [
                                'ItemClassificationCode' => [
                                    '_value' => $line['classification_code'],
                                    'listID' => 'CLASS',
                                ],
                            ],
                        ],
                    ],
                    'Price' => [
                        'PriceAmount' => [
                            '_value' => $line['unit_price'],
                            'currencyID' => 'MYR',
                        ],
                    ],
                ];
            }, $lines),
        ];
    }
}
