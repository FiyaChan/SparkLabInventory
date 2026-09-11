<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ToyyibPayService
{
    /**
     * Get the base URL for ToyyibPay based on the sandbox configuration.
     */
    public function getBaseUrl(): string
    {
        $isSandbox = config('toyyibpay.sandbox', true);

        return $isSandbox
            ? rtrim(config('toyyibpay.sandbox_url', 'https://dev.toyyibpay.com'), '/')
            : rtrim(config('toyyibpay.production_url', 'https://toyyibpay.com'), '/');
    }

    /**
     * Get user secret key from configuration.
     */
    protected function getUserSecretKey(): string
    {
        return config('toyyibpay.user_secret_key', '');
    }

    /**
     * Get category code from configuration.
     */
    protected function getCategoryCode(): string
    {
        return config('toyyibpay.category_code', '');
    }

    /**
     * Build the payment redirect URL for the customer browser.
     */
    public function getBillPaymentUrl(string $billCode): string
    {
        return "{$this->getBaseUrl()}/{$billCode}";
    }

    /**
     * Create a ToyyibPay bill for the given order and return the BillCode.
     *
     * @throws Exception
     */
    public function createBill(Order $order): string
    {
        $userSecretKey = $this->getUserSecretKey();
        $categoryCode = $this->getCategoryCode();

        if (empty($userSecretKey) || empty($categoryCode)) {
            Log::error('ToyyibPay credentials are missing in configuration/environment.');
            throw new Exception('ToyyibPay credentials are not configured. Please set TOYYIBPAY_USER_SECRET_KEY and TOYYIBPAY_CATEGORY_CODE.');
        }

        // ToyyibPay expects amount in CENTS (e.g. RM 10.50 -> 1050)
        $amountInCents = (int) round($order->total_amount * 100);

        $payload = [
            'userSecretKey'          => $userSecretKey,
            'categoryCode'           => $categoryCode,
            'billName'               => 'Order #' . $order->order_number,
            'billDescription'        => 'Payment for SparkLab Order #' . $order->order_number,
            'billPriceSetting'       => 1,
            'billPayorInfo'          => 1,
            'billAmount'             => $amountInCents,
            'billReturnUrl'          => route('toyyibpay.return'),
            'billCallbackUrl'        => route('toyyibpay.callback'),
            'billExternalReferenceNo'=> $order->order_number,
            'billTo'                 => $order->shipping_name,
            'billEmail'              => $order->user->email ?? 'customer@sparklab.my',
            'billPhone'              => $order->shipping_phone,
            'billSplitPayment'       => 0,
            'billSplitPaymentArgs'   => '',
            'billPaymentChannel'     => (string) config('toyyibpay.payment_channel', '0'),
            'billContentEmail'       => 'Thank you for your order with SparkLab Kids Science!',
            'billChargeToCustomer'   => (int) config('toyyibpay.bill_charge_to_customer', 1),
        ];

        $apiUrl = "{$this->getBaseUrl()}/index.php/api/createBill";

        try {
            $response = Http::asForm()->timeout(15)->post($apiUrl, $payload);
        } catch (Exception $e) {
            Log::error('ToyyibPay API request failed: ' . $e->getMessage(), ['order_id' => $order->id]);
            throw new Exception('Unable to communicate with ToyyibPay payment gateway: ' . $e->getMessage());
        }

        if ($response->failed()) {
            Log::error('ToyyibPay API returned error response', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'order'  => $order->order_number,
            ]);
            throw new Exception('ToyyibPay gateway returned an error (HTTP ' . $response->status() . ').');
        }

        $result = $response->json();

        // ToyyibPay returns an array like [{"BillCode": "xxxx"}]
        $billCode = null;
        if (is_array($result)) {
            if (isset($result[0]['BillCode'])) {
                $billCode = $result[0]['BillCode'];
            } elseif (isset($result['BillCode'])) {
                $billCode = $result['BillCode'];
            } elseif (isset($result['status']) && $result['status'] === 'error') {
                $errorMsg = $result['msg'] ?? 'Unknown error from ToyyibPay';
                Log::error('ToyyibPay bill creation error: ' . $errorMsg, ['order' => $order->order_number]);
                throw new Exception("ToyyibPay error: {$errorMsg}");
            }
        }

        if (empty($billCode)) {
            Log::error('ToyyibPay did not return a valid BillCode', [
                'response' => $response->body(),
                'order'    => $order->order_number,
            ]);
            throw new Exception('Failed to generate ToyyibPay bill. Invalid response from payment gateway.');
        }

        // Store the bill code on the payment record
        if ($order->payment) {
            $order->payment->update([
                'transaction_ref' => $billCode,
            ]);
        }

        ActivityLog::record('payment.bill_created', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'bill_code'    => $billCode,
            'method'       => 'toyyibpay',
        ]);

        return $billCode;
    }

    /**
     * Query ToyyibPay API to verify bill status and transaction history.
     */
    public function getBillTransactions(string $billCode): ?array
    {
        $userSecretKey = $this->getUserSecretKey();
        if (empty($userSecretKey)) {
            return null;
        }

        $apiUrl = "{$this->getBaseUrl()}/index.php/api/getBillTransactions";

        try {
            $response = Http::asForm()->timeout(10)->post($apiUrl, [
                'billCode'      => $billCode,
                'userSecretKey' => $userSecretKey,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (Exception $e) {
            Log::warning('ToyyibPay getBillTransactions check failed: ' . $e->getMessage(), ['billCode' => $billCode]);
        }

        return null;
    }

    /**
     * Process webhook or return data and update payment / order status.
     *
     * status: 1 = Success, 2 = Pending, 3 = Failed / Cancelled
     */
    public function processPaymentUpdate(string $billCode, int|string $statusId, ?string $externalRef = null, ?string $transactionId = null): ?Order
    {
        $payment = Payment::where('transaction_ref', $billCode)
            ->when($externalRef, function ($query, $ref) {
                $query->orWhereHas('order', fn ($q) => $q->where('order_number', $ref));
            })
            ->with('order')
            ->first();

        if (! $payment || ! $payment->order) {
            Log::warning('ToyyibPay update received for non-existent payment/order', [
                'billCode'       => $billCode,
                'status_id'      => $statusId,
                'external_ref'   => $externalRef,
                'transaction_id' => $transactionId,
            ]);
            return null;
        }

        $order = $payment->order;
        $statusId = (int) $statusId;

        if ($statusId === 1) { // Success
            if ($payment->status !== 'paid') {
                $payment->update([
                    'status'          => 'paid',
                    'transaction_ref' => $billCode,
                ]);

                if ($order->status === 'pending') {
                    $order->update(['status' => 'processing']);
                }

                // Automatically generate LHDN e-Invoice upon payment confirmation
                try {
                    $eInvoiceService = app(\App\Services\EInvoiceService::class);
                    $eInvoiceService->generateForOrder($order, [
                        'buyer_tin' => $order->buyer_tin,
                        'buyer_id_type' => $order->buyer_id_type,
                        'buyer_id_value' => $order->buyer_id_number,
                        'buyer_sst_no' => $order->buyer_sst_no,
                        'buyer_name' => $order->shipping_name,
                        'buyer_phone' => $order->shipping_phone,
                        'buyer_email' => $order->user->email ?? 'customer@sparklab.my',
                        'buyer_address' => $order->shipping_address,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Automatic ToyyibPay e-Invoice generation notice: ' . $e->getMessage(), ['order_id' => $order->id]);
                }

                ActivityLog::record('payment.succeeded', [
                    'order_id'       => $order->id,
                    'order_number'   => $order->order_number,
                    'bill_code'      => $billCode,
                    'transaction_id' => $transactionId,
                    'method'         => 'toyyibpay',
                ]);
            }
        } elseif ($statusId === 2) { // Pending
            if ($payment->status !== 'paid') {
                $payment->update(['status' => 'pending']);
            }
        } elseif ($statusId === 3) { // Failed / Cancelled
            if ($payment->status !== 'paid') {
                $payment->update(['status' => 'failed']);

                ActivityLog::record('payment.failed', [
                    'order_id'       => $order->id,
                    'order_number'   => $order->order_number,
                    'bill_code'      => $billCode,
                    'method'         => 'toyyibpay',
                ]);
            }
        }

        return $order->fresh(['payment', 'items.product', 'eInvoice']);
    }
}
