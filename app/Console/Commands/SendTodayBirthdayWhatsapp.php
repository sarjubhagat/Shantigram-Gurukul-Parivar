<?php

namespace App\Console\Commands;

use App\Models\Parivar\PersonalInfo;
use App\Services\HostelWhatsappService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTodayBirthdayWhatsapp extends Command
{
    protected $signature = 'parivar:send-today-birthday-message';

    protected $description = 'Send today\'s Shantigram Parivar birthday list on WhatsApp';

    public function handle(HostelWhatsappService $whatsapp): int
    {
        $date = Carbon::parse($this->option('date') ?: now()->toDateString())
            ->timezone(config('app.timezone', 'Asia/Kolkata'))
            ->startOfDay();

        $recipients = collect(config('parivar.whatsapp.birthday_recipients', []))
            ->map(fn ($n) => preg_replace('/\D+/', '', (string) $n) ?: '')
            ->filter(fn ($n) => strlen($n) >= 10)
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            $this->error('No WhatsApp recipients configured (PARIVAR_BIRTHDAY_WHATSAPP_NUMBERS).');

            return self::FAILURE;
        }

        $members = PersonalInfo::query()
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', (int) $date->month)
            ->whereDay('date_of_birth', (int) $date->day)
            ->orderBy('en_last_name')
            ->orderBy('en_first_name')
            ->get();

        $message = $this->buildMessage($date, $members);

        $this->info('Date: ' . $date->format('d-m-Y'));
        $this->info('Birthdays: ' . $members->count());
        $this->line($message);
        $this->newLine();

        if ($members->isEmpty()) {
            $this->warn('No birthdays today — message not sent.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run — WhatsApp not sent.');

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;

        foreach ($recipients as $number) {
            $result = $whatsapp->sendMessage($number, $message);
            if ($result['success']) {
                $ok++;
                $this->info('Sent to ' . $number);
            } else {
                $fail++;
                $this->error('Failed ' . $number . ': ' . ($result['message'] ?? 'unknown error'));
            }
        }


        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function buildMessage(Carbon $date, $members): string
    {
        $lines = [
            'આજના જન્મદિવસ: (' . $date->format('d-m-Y') . ') શાંતિગ્રામ પરિવાર',
        ];

        foreach ($members as $member) {
            $name = trim((string) ($member->display_name ?? ''));
            if ($name === '') {
                $name = trim(collect([
                    $member->en_first_name,
                    $member->en_last_name,
                ])->filter()->implode(' '));
            }
            if ($name === '') {
                $name = 'Member #' . $member->unique_id;
            }

            $phone = preg_replace('/\D+/', '', (string) ($member->phone_1 ?: $member->phone_2)) ?: '';
            if (strlen($phone) > 10 && str_starts_with($phone, '91')) {
                $phone = substr($phone, -10);
            }
            if ($phone === '') {
                $phone = '-';
            }

            $lines[] = $name . ' | ' . $phone;
        }

        if ($members->isEmpty()) {
            $lines[] = 'કોઈ જન્મદિવસ નથી.';
        }

        return implode("\n", $lines);
    }
}
