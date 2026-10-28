<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('auth')->group(function () {
    Route::get('/template/{module}', [\App\Http\Controllers\BosDownloadController::class, 'template'])->name('import.template');
    Route::get('/dokumen/{document}', [\App\Http\Controllers\BosDownloadController::class, 'document'])->name('documents.download');
    Route::get('/laporan/{format}', [\App\Http\Controllers\BosDownloadController::class, 'report'])->name('reports.download');
    Route::get('/ekspor/{module}/{format}', [\App\Http\Controllers\BosDownloadController::class, 'module'])->name('module.export');
});
