<?php

namespace App\Services;

use App\Models\{FinancialRecord, DokumenPendukung};
use Illuminate\Support\Facades\{Gate, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    public function store(FinancialRecord $record, $file): DokumenPendukung
    {
        Gate::authorize('update', $record);
        $allowed = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'];
        $mime = $file->getMimeType();
        if (! isset($allowed[$mime]) || $file->getSize() > config('bos.max_document_kb') * 1024) throw ValidationException::withMessages(['file' => 'Gunakan PDF, XLSX, JPG, PNG sesuai batas ukuran unggahan.']);
        $path = $file->storeAs('dokumen', Str::uuid().'.'.$allowed[$mime], 'local');
        try {
            return DokumenPendukung::create(['target_type' => $record->getMorphClass(), 'target_id' => $record->id, 'nama' => basename($file->getClientOriginalName()), 'path' => $path, 'mime' => $mime, 'ukuran' => $file->getSize(), 'checksum' => hash_file('sha256', Storage::disk('local')->path($path)), 'uploaded_by' => auth()->id()]);
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
    }
}
