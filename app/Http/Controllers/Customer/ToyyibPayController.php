<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ToyyibPayService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ToyyibPayController extends Controller
{
    public function __construct(protected ToyyibPayService $toyyibPayService) {}

    /**
     * Handle the customer return redirect from ToyyibPay payment gateway.
     * ToyyibPay sends: status_id, billcode, order_id, msg, transaction_id
     */
    public function handleReturn(Request $request)
    {
        $statusId = $request->query('status_id');
        $billCode = $request->query('billcode');
        $orderNumber = $request->query('order_id');
        $transactionId = $request->query('transaction_id');

        Log::info('ToyyibPay return received', [
            'status_id'      => $statusId,
            'billcode'       => $billCode,
            'order_id'       => $orderNumber,
            'transaction_id' => $transactionId,
        ]);

        if (empty($billCode)) {
            return redirect()->route('orders.index')->with('status', 'Payment redirect received without a bill reference.');
        }

        // Check verification from ToyyibPay API if possible
        $verifiedStatus = $statusId;
        $billTransactions = $this->toyyibPayService->getBillTransactions($billCode);

        if (is_array($billTransactions) && !empty($billTransactions)) {
            $latest = is_array($billTransactions[0] ?? null) ? $billTransactions[0] : $billTransactions;
            if (isset($latest['billpaymentStatus'])) {
                $verifiedStatus = (int) $latest['billpaymentStatus'];
            }
            if (isset($latest['billpaymentInvoiceNo']) && empty($transactionId)) {
                $transactionId = $latest['billpaymentInvoiceNo'];
            }
        }

        $order = $this->toyyibPayService->processPaymentUpdate(
            (string) $billCode,
            (int) $verifiedStatus,
            $orderNumber,
            $transactionId
        );

        if (!$order) {
            $order = Order::where('order_number', $orderNumber)
                ->orWhereHas('payment', fn ($q) => $q->where('transaction_ref', $billCode))
                ->first();
        }

        if (!$order) {
            return redirect()->route('orders.index')->with('status', 'Payment completed. Please check your order history.');
        }

        if ((int) $verifiedStatus === 1) {
            return redirect()->route('orders.show', $order)
                ->with('status', 'Payment successful via FPX! Your science kits are now being prepared for fulfillment.');
        } elseif ((int) $verifiedStatus === 2) {
            return redirect()->route('orders.show', $order)
                ->with('status', 'Your payment is pending confirmation from the bank.');
        } else {
            return redirect()->route('orders.show', $order)
                ->with('status', 'Payment was not completed or was cancelled. You can retry payment anytime below.');
        }
    }

    /**
     * Handle asynchronous server-to-server webhook callback from ToyyibPay.
     * ToyyibPay POSTs: refno, status, reason, billcode, order_id, amount, transaction_time
     */
    public function handleCallback(Request $request)
    {
        $refNo = $request->input('refno');
        $status = $request->input('status');
        $billCode = $request->input('billcode');
        $orderNumber = $request->input('order_id');

        Log::info('ToyyibPay webhook callback received', $request->all());

        if (empty($billCode) || empty($status)) {
            return response('Missing billcode or status', 400);
        }

        $this->toyyibPayService->processPaymentUpdate(
            (string) $billCode,
            (int) $status,
            $orderNumber,
            $refNo
        );

        return response('OK', 200);
    }

    /**
     * Retry payment for an order with pending/failed ToyyibPay payment.
     */
    public function retryPayment(Order $order)
    {
        // Enforce ownership
        if ($order->user_id !== Auth::id() && !Auth::user()->hasAnyRole(['admin', 'superadmin', 'staff'])) {
            abort(403, 'Unauthorized access to this order.');
        }

        if ($order->payment && $order->payment->status === 'paid') {
            return redirect()->route('orders.show', $order)->with('status', 'This order is already paid.');
        }

        try {
            $billCode = $this->toyyibPayService->createBill($order);
            $paymentUrl = $this->toyyibPayService->getBillPaymentUrl($billCode);

            return redirect()->away($paymentUrl);
        } catch (Exception $e) {
            Log::error('ToyyibPay retry payment failed: ' . $e->getMessage(), ['order_id' => $order->id]);

            return redirect()->route('orders.show', $order)
                ->with('status', 'Unable to connect to ToyyibPay gateway: ' . $e->getMessage());
        }
    }
}
