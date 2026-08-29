<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class IcardTraySettings
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = DB::table('icard_settings')->where('key', $key)->value('value');

        if ($row === null) {
            return $default ?? (config('icard.defaults.' . $key));
        }

        return $row;
    }

    public static function all(): array
    {
        $defaults = config('icard.defaults', []);
        $stored   = DB::table('icard_settings')->pluck('value', 'key')->all();

        $out = [];
        foreach ($defaults as $key => $value) {
            $out[$key] = array_key_exists($key, $stored) ? $stored[$key] : $value;
        }

        return $out;
    }

    public static function save(array $values): void
    {
        foreach ($values as $key => $value) {
            DB::table('icard_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value === null ? null : (string) $value]
            );
        }
    }

    public static function slotOffset(int $startRow, int $startColumn): int
    {
        $startRow    = max(1, min(5, $startRow));
        $startColumn = max(1, min(2, $startColumn));

        return ($startRow - 1) * 2 + ($startColumn - 1);
    }

    public static function availableSlots(int $startRow, int $startColumn): int
    {
        return 10 - static::slotOffset($startRow, $startColumn);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cards
     * @return array<int, array<string, mixed>|null>
     */
    public static function mapCardsToSlots(array $cards, int $startRow, int $startColumn): array
    {
        $offset = static::slotOffset($startRow, $startColumn);
        $slots  = array_fill(0, 10, null);
        $i      = 0;

        foreach ($cards as $card) {
            $idx = $offset + $i;
            if ($idx > 9) {
                break;
            }
            $slots[$idx] = $card;
            $i++;
        }

        return $slots;
    }

    public static function resolveForPrint(): array
    {
        $s = static::all();

        $marginTop  = (float) $s['margin_top'] + (float) $s['cal_top'];
        $marginLeft = (float) $s['margin_left'] + (float) $s['cal_left'];

        return [
            'academic_year'   => (string) ($s['academic_year'] ?? ''),
            'margin_top'      => $marginTop,
            'margin_left'     => $marginLeft,
            'target_top'      => (float) $s['margin_top'],
            'target_left'     => (float) $s['margin_left'],
            'cal_top'         => (float) $s['cal_top'],
            'cal_left'        => (float) $s['cal_left'],
            'page_shift_top'  => (float) $s['page_shift_top'],
            'page_shift_left' => (float) $s['page_shift_left'],
            'start_row'       => (int) $s['start_row'],
            'start_column'    => (int) $s['start_column'],
        ];
    }
}
