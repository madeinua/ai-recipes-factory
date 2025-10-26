<?php

namespace App\Domain\Recipe\Support;

final class IngredientsHelper
{
    /**
     * @param string $csv
     * @return string
     */
    public static function canonicalizeCsv(string $csv): string
    {
        $items = array_filter(array_map(
            static fn($s) => mb_strtolower(trim($s)),
            explode(',', $csv)
        ), static fn($s) => $s !== '');

        sort($items, SORT_NATURAL | SORT_FLAG_CASE);

        return implode(', ', $items);
    }

    /**
     * @param string $csv
     * @return string
     */
    public static function hash(string $csv): string
    {
        return hash('sha256', self::canonicalizeCsv($csv));
    }
}
