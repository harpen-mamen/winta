<?php

namespace App\Services;

class Money
{
    public static function add(string ...$amounts): string { return array_reduce($amounts, fn ($sum, $amount) => bcadd($sum, $amount, 2), '0.00'); }
    public static function sub(string $a, string $b): string { return bcsub($a, $b, 2); }
    public static function percent(string $amount, string $budget): ?string { return bccomp($budget, '0', 2) === 0 ? null : bcdiv(bcmul($amount, '100', 6), $budget, 2); }
    public static function idr(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount), 2, '00');
        return 'Rp '.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole).','.str_pad($fraction, 2, '0');
    }
}
