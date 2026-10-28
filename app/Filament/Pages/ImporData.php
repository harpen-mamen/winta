<?php

namespace App\Filament\Pages;

use App\Services\ImportService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\{Select, FileUpload};
use Filament\Notifications\Notification;
use Livewire\Attributes\Locked;

class ImporData extends Page
{
    protected static ?string $title = 'Impor Data Excel';
    protected static string | \UnitEnum | null $navigationGroup = 'PENGATURAN';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected string $view = 'filament.pages.impor';
    #[Locked] public ?array $preview = null;
    public static function canAccess(): bool { return auth()->user()?->can('imports.run') ?? false; }
    protected function getHeaderActions(): array
    {
        return [Action::make('unggah')->label('Unggah dan Validasi XLSX')->schema([
            Select::make('module')->label('Modul Tujuan')->options(ImportService::modules())->required(),
            FileUpload::make('file')->label('File XLSX')->storeFiles(false)->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->maxSize(config('bos.max_import_kb'))->required(),
        ])->action(function (array $data) { $this->preview = app(ImportService::class)->preview($data['module'], $data['file']); }),
        Action::make('commit')->label('Simpan Batch')->color('success')->requiresConfirmation()->visible(fn () => $this->preview && ! $this->preview['errors'])
            ->action(function () { $count = app(ImportService::class)->commit($this->preview['token']); $this->preview = null; Notification::make()->title($count.' baris berhasil diimpor sebagai draft')->success()->send(); }),
        ];
    }
}
