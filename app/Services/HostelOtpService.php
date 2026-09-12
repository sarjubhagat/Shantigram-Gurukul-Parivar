<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HostelOtpService
{
    /**
     * Send OTP through hostel MSG91 proxy.
     * Do not call the hostel URL from the browser — only from this service.
     *
     * @return array{success:bool,message:string,data?:array}
     */
    public function sendOtp(string $number, string $otp): array
    {
        $url  = (string) config('parivar.otp.send_url');
        $hash = (string) config('parivar.otp.hash');

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'number' => $number,
                    'otp'    => $otp,
                    'hash'   => $hash,
                ]);

            $body = $response->json() ?? [];

            if (! $response->successful() || empty($body['success'])) {
                Log::warning('Hostel OTP send failed', [
                    'number' => $number,
                    'status' => $response->status(),
                    'body'   => $body,
                ]);

                return [
                    'success' => false,
                    'message' => $body['message'] ?? 'Failed to send OTP',
                    'data'    => $body['data'] ?? [],
                ];
            }

            return [
                'success' => true,
                'message' => $body['message'] ?? 'OTP sent successfully',
                'data'    => $body['data'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('Hostel OTP send exception: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while sending OTP',
                'data'    => [],
            ];
        }
    }
}
