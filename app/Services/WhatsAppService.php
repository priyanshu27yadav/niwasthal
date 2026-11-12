<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send an OTP verification message to the given mobile number.
     */
    public function sendOtp(string $mobile, string $otp): void
    {
        $message = "🔐 *Niwasthal Verification*\n\n"
            . "Your OTP for login is: *{$otp}*\n\n"
            . "This OTP is valid for 10 minutes. Do not share it with anyone.";

        $this->sendMessage($mobile, $message);
    }

    /**
     * Send a payment confirmation message after successful payment.
     */
    public function sendPaymentConfirmation(
        string $mobile,
        string $name,
        float $amount,
        string $receiptNo
    ): void {
        $formattedAmount = number_format($amount, 2);

        $message = "✅ *Payment Received – Niwasthal*\n\n"
            . "Dear *{$name}*,\n\n"
            . "We have successfully received your payment.\n\n"
            . "💰 Amount Paid: ₹{$formattedAmount}\n"
            . "🧾 Receipt No: {$receiptNo}\n"
            . "📅 Date: " . now()->format('d M Y, h:i A') . "\n\n"
            . "Thank you for your timely payment! 🙏";

        $this->sendMessage($mobile, $message);
    }

    /**
     * Send a rent reminder message for the given month/year.
     */
    public function sendRentReminder(
        string $mobile,
        string $name,
        float $amount,
        string $monthYear
    ): void {
        $formattedAmount = number_format($amount, 2);

        $message = "🔔 *Rent Reminder – Niwasthal*\n\n"
            . "Dear *{$name}*,\n\n"
            . "This is a friendly reminder that your rent for *{$monthYear}* is due.\n\n"
            . "💰 Amount Due: ₹{$formattedAmount}\n\n"
            . "Please make the payment at the earliest to avoid any late fees.\n\n"
            . "You can pay online via the Niwasthal app or contact your property manager.";

        $this->sendMessage($mobile, $message);
    }

    /**
     * Send a complaint status update message.
     */
    public function sendComplaintUpdate(
        string $mobile,
        string $complaintTitle,
        string $status
    ): void {
        $statusEmoji = match (strtolower($status)) {
            'open'        => '🟡',
            'in_progress' => '🔵',
            'resolved'    => '✅',
            'closed'      => '⚫',
            default       => '📌',
        };

        $statusLabel = match (strtolower($status)) {
            'open'        => 'Open',
            'in_progress' => 'In Progress',
            'resolved'    => 'Resolved',
            'closed'      => 'Closed',
            default       => ucfirst($status),
        };

        $message = "📋 *Complaint Update – Niwasthal*\n\n"
            . "Your complaint has been updated.\n\n"
            . "📌 Complaint: *{$complaintTitle}*\n"
            . "Status: {$statusEmoji} *{$statusLabel}*\n\n"
            . "We appreciate your patience. If you have further concerns, please raise a new complaint through the app.";

        $this->sendMessage($mobile, $message);
    }

    /**
     * Send a generic free-form text message.
     */
    public function sendMessage(string $mobile, string $message): void
    {
        $apiToken      = config('services.whatsapp.api_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        if (empty($apiToken) || empty($phoneNumberId)) {
            Log::warning('WhatsAppService: API credentials not configured. Message not sent.', [
                'mobile'  => $mobile,
                'message' => substr($message, 0, 100),
            ]);
            return;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->formatMobile($mobile),
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $message,
            ],
        ];

        $this->send($payload);
    }

    /**
     * Normalize a mobile number by prepending the Indian country code (91).
     */
    private function formatMobile(string $mobile): string
    {
        // Remove all non-digit characters
        $mobile = preg_replace('/\D/', '', $mobile);

        // Strip leading zeros
        $mobile = ltrim($mobile, '0');

        // If already has country code (91 prefix and 12 digits total), return as-is
        if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
            return $mobile;
        }

        // Prepend India country code
        return '91' . $mobile;
    }

    /**
     * Execute the HTTP POST request to the WhatsApp Cloud API.
     *
     * @param  array  $payload
     * @return array
     */
    private function send(array $payload): array
    {
        $apiToken      = config('services.whatsapp.api_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $apiVersion    = config('services.whatsapp.api_version', 'v18.0');

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        try {
            $response = Http::withToken($apiToken)
                ->timeout(15)
                ->post($url, $payload);

            if ($response->failed()) {
                Log::error('WhatsAppService: API request failed.', [
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                    'payload' => $payload,
                ]);
            } else {
                Log::info('WhatsAppService: Message sent successfully.', [
                    'to'       => $payload['to'] ?? null,
                    'response' => $response->json(),
                ]);
            }

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('WhatsAppService: Exception while sending message.', [
                'exception' => $e->getMessage(),
                'payload'   => $payload,
            ]);

            return [];
        }
    }
}
