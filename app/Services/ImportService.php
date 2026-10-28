<?php

namespace App\Services;

use App\Models\ImportBatch;
use Illuminate\Support\Facades\{DB, Cache, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\XLSX\Reader;

class ImportService
{
    public static function modules(): array
    {
        $out = [];
        foreach (config('bos-modules') as $key => $module) if (! $module['readonly'] && auth()->user()?->can($key.'.write')) $out[$key] = $module['label'];
        return $out;
    }
    public function preview(string $module, $file): array
    {
        abort_unless(auth()->user()?->can('imports.run') && isset(static::modules()[$module]), 403);
        if (! in_array($file->getMimeType(), ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip']) || strtolower($file->getClientOriginalExtension()) !== 'xlsx' || $file->getSize() > config('bos.max_import_kb') * 1024) throw ValidationException::withMessages(['file' => 'Gunakan file XLSX sesuai batas ukuran unggahan.']);
        $path = $file->storeAs('imports', Str::uuid().'.xlsx', 'local');
        try {
            $zip = new \ZipArchive();
            if ($zip->open(Storage::disk('local')->path($path)) !== true) throw ValidationException::withMessages(['file' => 'File XLSX tidak valid.']);
            $expanded = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) $expanded += ($zip->statIndex($i)['size'] ?? 0);
            $zip->close();
            if ($expanded > 40 * 1024 * 1024) throw ValidationException::withMessages(['file' => 'Isi XLSX terlalu besar setelah diekstrak. Pecah file menjadi batch lebih kecil.']);
            $rows = $this->read($module, Storage::disk('local')->path($path));
            $checksum = hash_file('sha256', Storage::disk('local')->path($path));
            if (ImportBatch::where('checksum', $checksum)->where('status', 'berhasil')->exists()) throw ValidationException::withMessages(['file' => 'File ini sudah berhasil diimpor.']);
            $errors = $this->dryRun($module, $rows);
            $token = Str::random(48);
            Cache::put('bos-import:'.$token, ['user_id' => auth()->id(), 'module' => $module, 'path' => $path, 'name' => basename($file->getClientOriginalName()), 'checksum' => $checksum], now()->addHour());
            return ['token' => $token, 'jumlah' => count($rows), 'rows' => array_slice($rows, 0, 10), 'errors' => $errors];
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
    }
    public function read(string $module, string $path): array
    {
        $reader = new Reader(); $rows = []; $headers = null; $expected = array_keys(config('bos-modules.'.$module.'.fields'));
        try {
            $reader->open($path);
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = array_map(fn ($cell) => $cell->getValue(), $row->getCells());
                    if (! $headers) {
                        $headers = array_map(fn ($v) => trim((string) $v), $values);
                        if ($headers !== $expected) throw ValidationException::withMessages(['file' => 'Header harus sama dengan template: '.implode(', ', $expected)]);
                        continue;
                    }
                    if (count(array_filter($values, fn ($v) => $v !== null && $v !== '')) === 0) continue;
                    if (count($rows) >= config('bos.max_import_rows')) throw ValidationException::withMessages(['file' => 'Batas baris per batch tercapai. Pecah file menjadi beberapa batch.']);
                    $values = array_pad(array_slice($values, 0, count($headers)), count($headers), null);
                    $mapped = array_combine($headers, $values);
                    foreach ($mapped as $key => &$value) {
                        if ($value instanceof \DateTimeInterface) $value = $value->format('Y-m-d');
                        $type = rtrim(config('bos-modules.'.$module.'.fields.'.$key), '?');
                        if (in_array($type, ['money', 'signed']) && is_float($value)) throw ValidationException::withMessages(['file' => 'Kolom uang harus berformat Text di Excel agar presisi dua desimal dipertahankan.']);
                        if ($value === '') $value = null;
                    }
                    unset($value); $rows[] = $mapped;
                }
                break;
            }
        } finally { $reader->close(); }
        if (! $rows) throw ValidationException::withMessages(['file' => 'Tidak ada baris data dalam file.']);
        return $rows;
    }
    private function dryRun(string $module, array $rows): array
    {
        $errors = []; DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                try { DB::transaction(fn () => config('bos-modules.'.$module.'.model')::create($row)); }
                catch (ValidationException $e) { $errors[$index + 2] = implode(' ', $e->validator->errors()->all()); }
                catch (\Illuminate\Database\QueryException $e) { $errors[$index + 2] = 'Data bertentangan dengan constraint/duplikasi database.'; }
            }
        } finally { DB::rollBack(); }
        return $errors;
    }
    public function commit(string $token): int
    {
        $stage = Cache::get('bos-import:'.$token);
        abort_unless($stage && $stage['user_id'] === auth()->id() && auth()->user()->can('imports.run') && isset(static::modules()[$stage['module']]), 403);
        $path = Storage::disk('local')->path($stage['path']);
        abort_unless(is_file($path) && hash_equals($stage['checksum'], hash_file('sha256', $path)), 422);
        $rows = $this->read($stage['module'], $path);
        $errors = $this->dryRun($stage['module'], $rows);
        if ($errors) throw ValidationException::withMessages(['file' => 'Perbaiki kesalahan impor: '.json_encode($errors, JSON_UNESCAPED_UNICODE)]);
        DB::transaction(function () use ($stage, $rows) {
            if (ImportBatch::where('checksum', $stage['checksum'])->exists()) throw ValidationException::withMessages(['file' => 'Batch ini sudah pernah diproses.']);
            $batch = ImportBatch::create(['tipe' => $stage['module'], 'nama_file' => $stage['name'], 'checksum' => $stage['checksum'], 'status' => 'diproses', 'jumlah' => 0, 'user_id' => auth()->id()]);
            foreach ($rows as $row) config('bos-modules.'.$stage['module'].'.model')::create($row);
            $batch->update(['status' => 'berhasil', 'jumlah' => count($rows)]);
        });
        Cache::forget('bos-import:'.$token); Storage::disk('local')->delete($stage['path']);
        return count($rows);
    }
}
