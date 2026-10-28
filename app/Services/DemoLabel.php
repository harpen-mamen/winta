<?php

namespace App\Services;

class DemoLabel
{
    public static function report(array $report): array
    {
        $containsDemo = false;
        foreach ($report['rows'] as $row) foreach ($row as $value) {
            if (is_string($value) && (str_contains($value, '[DEMO]') || str_contains($value, 'DATA DEMO') || str_contains($value, 'DEMO -'))) $containsDemo = true;
        }
        $report['is_demo'] = $containsDemo;
        if ($containsDemo) {
            $report['title'] = '[DEMO] '.$report['title'];
            $report['source'] = 'MENGANDUNG DATA DEMO FIKTIF - BUKAN LAPORAN RESMI. '.$report['source'];
        }
        return $report;
    }
}
