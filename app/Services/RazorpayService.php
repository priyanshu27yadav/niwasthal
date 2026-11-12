<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayService
{
    private ?Api $api = null;
    private ?string $keyId;
    private ?string $keySecret;

    public function __construct()
    {
        $this->keyId     = config('services.razorpay.key_id');
        $this->keySecret = config('services.razorpay.key_secret');

        if ($this->isConfigured()) {
            $this->api = new Api($this->keyId, $this->keySecret);
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->keyId)
            && !empty($this->keySecret)
            && !str_contains((string) $this->keyId, 'your_')
            && !str_contains((string) $this->keySecret, 'your_');
    }

    /**
     * Create a Razorpay order for the given amount (in INR rupees).
     *
     * @param  float   $amount     Amount in rupees (e.g. 5000.00)
     * @param  string  $receiptId  Unique receipt identifier (max 40 chars)
     * @param  array   $notes      Optional key-value notes to attach to the order
     * @return array{order_id: string, amount: int, currency: string}
     *
     * @throws \Exception
     */
    public function createOrder(float $amount, string $receiptId, array $notes = []): array
    {
        if (!$this->api) {
            throw new \RuntimeException('Razorpay credentials are not configured in .env.');
        }

        try {
            // Razorpay requires amount in smallest currency unit (paise for INR)
            $amountInPaise = (int) round($amount * 100);

            $orderData = [
                'amount'          => $amountInPaise,
                'currency'        => 'INR',
                'receipt'         => substr($receiptId, 0, 40),
                'payment_capture' => 1,
                'notes'           => $notes,
            ];

            $order = $this->api->order->create($orderData);

            Log::info('RazorpayService: Order created.', [
                'order_id'   => $order->id,
                'amount'     => $amountInPaise,
                'receipt_id' => $receiptId,
            ]);

            return [
                'order_id' => $order->id,
                'amount'   => $amountInPaise,
                'currency' => 'INR',
            ];
        } catch (\Throwable $e) {
            Log::error('RazorpayService: Failed to create order.', [
                'exception'  => $e->getMessage(),
                'amount'     => $amount,
                'receipt_id' => $receiptId,
            ]);

            throw $e;
        }
    }

    /**
     * Verify a Razorpay payment signature using HMAC SHA256.
     *
     * The signature is computed as:
     *   HMAC-SHA256(razorpay_order_id + "|" + razorpay_payment_id, key_secret)
     *
     * @param  string  $orderId    Razorpay order ID
     * @param  string  $paymentId  Razorpay payment ID
     * @param  string  $signature  Signature returned by Razorpay checkout
     * @return bool
     */
    public function verifyPayment(string $orderId, string $paymentId, string $signature): bool
    {
        if (!$this->api) {
            return false;
        }

        try {
            $attributes = [
                'razorpay_order_id'   => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature'  => $signature,
            ];

            $this->api->utility->verifyPaymentSignature($attributes);

            Log::info('RazorpayService: Payment signature verified successfully.', [
                'order_id'   => $orderId,
                'payment_id' => $paymentId,
            ]);

            return true;
        } catch (SignatureVerificationError $e) {
            Log::warning('RazorpayService: Signature verification failed.', [
                'order_id'   => $orderId,
                'payment_id' => $paymentId,
                'exception'  => $e->getMessage(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('RazorpayService: Unexpected error during signature verification.', [
                'order_id'   => $orderId,
                'payment_id' => $paymentId,
                'exception'  => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Fetch payment details from Razorpay.
     *
     * Returns the payment attributes as an array including:
     * id, entity, amount, currency, status, order_id, method, captured, etc.
     *
     * @param  string  $paymentId  Razorpay payment ID (pay_XXXXXXXXXXXXXXXX)
     * @return array
     *
     * @throws \Exception
     */
    public function getPayment(string $paymentId): array
    {
        try {
            $payment = $this->api->payment->fetch($paymentId);

            Log::info('RazorpayService: Payment fetched.', [
                'payment_id' => $paymentId,
                'status'     => $payment->status ?? null,
            ]);

            // Convert Razorpay entity to plain array
            return $payment->toArray();
        } catch (\Throwable $e) {
            Log::error('RazorpayService: Failed to fetch payment.', [
                'payment_id' => $paymentId,
                'exception'  => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
