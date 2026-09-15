<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HostelWhatsappService
{
    /**
     * Send a WhatsApp text message through the hostel API.
     * Do not call the hostel URL from the browser — only from this service.
     *
     * @return array{success:bool,message:string,data?:array}
     */
    public function sendMessage(string $number, string $message): array
    {
        $url  = (string) config('parivar.whatsapp.send_url');
        $code = (string) config('parivar.whatsapp.api_code');

        $digits = preg_replace('/\D+/', '', $number) ?: '';
        if (strlen($digits) > 10 && str_starts_with($digits, '91')) {
            $digits = substr($digits, -10);
        }

        if (strlen($digits) < 10 || $message === '' || $code === '') {
            return [
                'success' => false,
                'message' => 'Invalid WhatsApp number, message, or API code.',
                'data'    => [],
            ];
        }

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'number'  => $digits,
                    'message' => $message,
                    'code'    => $code,
                ]);

            $body = $response->json() ?? [];

            if (! $response->successful() || empty($body['success'])) {
                Log::warning('Hostel WhatsApp send failed', [
                    'number' => $digits,
                    'status' => $response->status(),
                    'body'   => $body,
                ]);

                return [
                    'success' => false,
                    'message' => $body['message'] ?? 'Failed to send WhatsApp message',
                    'data'    => $body['data'] ?? [],
                ];
            }

            return [
                'success' => true,
                'message' => $body['message'] ?? 'WhatsApp message sent successfully',
                'data'    => $body['data'] ?? ['number' => $digits],
            ];
        } catch (\Throwable $e) {
            Log::error('Hostel WhatsApp send exception: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while sending WhatsApp message',
                'data'    => [],
            ];
        }
    }
}
