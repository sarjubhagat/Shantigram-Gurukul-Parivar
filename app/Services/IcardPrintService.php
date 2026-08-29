<?php

namespace App\Services;

use App\Models\Parivar\IcardImage;
use App\Models\Parivar\IcardQrToken;
use App\Models\Parivar\PersonalInfo;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class IcardPrintService
{
    /**
     * @param  array<int, int>  $memberIds
     * @return array<int, array<string, mixed>>
     */
    public function buildCards(array $memberIds): array
    {
        $ids = collect($memberIds)->map(fn ($id) => (int) $id)->unique()->take(10)->values();

        $members = PersonalInfo::whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $front = IcardImage::where('image_key', 'FRONT')->first();
        $back  = IcardImage::where('image_key', 'BACK')->first();
        $ttl   = (int) config('icard.storage.signed_url_ttl', 180);

        $colors = array_merge(
            config('icard.colors', []),
            is_array($front?->color_codes) ? $front->color_codes : []
        );
        $colors = $this->sanitizeColors($colors);

        $frontBg = $front?->signedUrl($ttl);
        $backBg  = $back?->signedUrl($ttl);

        $cards = [];
        foreach ($ids as $id) {
            $member = $members->get($id);
            if (! $member) {
                continue;
            }
            $cards[] = $this->buildCard($member, $frontBg, $backBg, $colors, $ttl);
        }

        return $cards;
    }

    /**
     * @param  array<string, string>  $colors
     * @return array<string, mixed>
     */
    public function buildCard(
        PersonalInfo $member,
        ?string $frontBg,
        ?string $backBg,
        array $colors,
        int $ttl
    ): array {
        $token = $this->tokenForMember($member);

        return [
            'id'            => $member->id,
            'unique_id'     => (string) $member->unique_id,
            'name'          => $this->cardName($member),
            'photo_url'     => $member->photoUrl($ttl),
            'qr_code'       => $this->qrDataUri($token),
            'qr_token'      => $token,
            'mobile'        => $member->primaryPhone() ?: '',
            'address'       => trim((string) $member->current_address),
            'front_bg'      => $frontBg,
            'back_bg'       => $backBg,
            'color_codes'   => $colors,
        ];
    }

    public function tokenForMember(PersonalInfo $member): string
    {
        $row = IcardQrToken::firstOrCreate(
            ['unique_id' => $member->unique_id],
            ['qr_token' => $this->newToken($member->unique_id)]
        );

        return $row->qr_token;
    }

    private function newToken(int $uniqueId): string
    {
        $raw = hash_hmac('sha256', (string) $uniqueId . microtime(true), (string) config('app.key'), true);
        $tok = substr(strtr(base64_encode($raw), '+/', 'AB'), 0, 10);

        while (IcardQrToken::where('qr_token', $tok)->exists()) {
            $tok = Str::upper(Str::random(10));
        }

        return $tok;
    }

    private function cardName(PersonalInfo $member): string
    {
        $middle = trim((string) $member->en_middle_name);
        $name   = mb_strtoupper(trim(collect([
            $member->en_last_name,
            $member->en_first_name,
            $middle,
        ])->filter()->implode(' ')));

        if ($middle !== '' && ! preg_match('/\bBHAI\s*$/iu', $name)) {
            $name .= 'BHAI';
        }

        return $name;
    }

    private function qrDataUri(string $token): string
    {
        $svg = QrCode::format('svg')
            ->size(400)
            ->errorCorrection('H')
            ->margin(1)
            ->generate($token);

        return 'data:image/svg+xml;base64,' . base64_encode((string) $svg);
    }

    /**
     * @param  array<string, mixed>  $colors
     * @return array<string, string>
     */
    public function sanitizeColors(array $colors): array
    {
        $out = [];
        foreach (config('icard.colors', []) as $field => $default) {
            $val = $colors[$field] ?? $default;
            $out[$field] = $this->hexColor((string) $val, $default);
        }

        return $out;
    }

    private function hexColor(string $value, string $fallback): string
    {
        $value = trim($value);
        if (preg_match('/^#?[0-9a-fA-F]{6}$/', $value)) {
            return '#' . ltrim($value, '#');
        }

        return $fallback;
    }
}
