<?php

namespace App\Filament\Support;

use App\Models\{FinancialRecord, RkasDetail};
use App\Services\{FinancialWorkflow, ModuleFields, DocumentService};
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\{Action, EditAction, ViewAction};
use Filament\Forms\Components\{Textarea, FileUpload};
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

abstract class BosResource extends Resource
{
    protected static bool $isDiscovered = false;

    public static function module(): array { return config('bos-modules.'.static::key()); }
    public static function key(): string { return (new (static::getModel()))->getTable(); }
    public static function getModelLabel(): string { return static::module()['label']; }
    public static function getPluralModelLabel(): string { return static::getModelLabel(); }
    public static function getNavigationGroup(): ?string { return static::module()['group']; }
    public static function getNavigationSort(): ?int { return array_search(static::key(), array_keys(config('bos-modules'))); }
    public static function getNavigationIcon(): string | \BackedEnum | \Illuminate\Contracts\Support\Htmlable | null
    {
        return match (static::key()) {
            'sekolahs' => 'heroicon-o-academic-cap', 'tahun_anggarans' => 'heroicon-o-calendar-days', 'data_murids' => 'heroicon-o-user-group',
            'pagu_bos' => 'heroicon-o-banknotes', 'perubahan_pagu' => 'heroicon-o-arrow-path', 'penyaluran_bos' => 'heroicon-o-arrow-up-right',
            'penerimaan_bos' => 'heroicon-o-arrow-down-left', 'rkas' => 'heroicon-o-clipboard-document-list', 'rkas_details' => 'heroicon-o-list-bullet',
            'realisasi_bos' => 'heroicon-o-chart-bar-square', 'saldo_awal_bos' => 'heroicon-o-wallet', 'penyesuaian_saldo' => 'heroicon-o-adjustments-horizontal',
            'saldo_dilaporkans' => 'heroicon-o-document-currency-dollar', 'dokumen_pendukung' => 'heroicon-o-folder-open', 'verifikasis' => 'heroicon-o-check-badge',
            'evaluasis' => 'heroicon-o-chat-bubble-left-right', 'audit_logs' => 'heroicon-o-shield-check', 'import_batches' => 'heroicon-o-clock',
            'kecamatans' => 'heroicon-o-map-pin', 'jenis_bos' => 'heroicon-o-tag', 'komponen_belanja' => 'heroicon-o-squares-2x2',
            default => 'heroicon-o-calendar',
        };
    }
    public static function canViewAny(): bool { return auth()->user()?->can(static::key().'.view') ?? false; }
    public static function canCreate(): bool { return ! static::module()['readonly'] && (auth()->user()?->can(static::key().'.write') ?? false); }
    public static function applyDataScope(Builder $query, ?string $scope): Builder
    {
        if (! in_array($scope, ['resmi', 'demo'])) return $query;
        $demo = $scope === 'demo';
        if (static::key() === 'sekolahs') $query->where('is_demo', $demo);
        elseif (static::key() === 'pagu_bos' || static::key() === 'data_murids') $query->whereHas('sekolah', fn ($q) => $q->where('is_demo', $demo));
        elseif (static::module()['finance']) $query->whereHas('pagu.sekolah', fn ($q) => $q->where('is_demo', $demo));
        elseif (static::key() === 'rkas_details') $query->whereHas('rkas.pagu.sekolah', fn ($q) => $q->where('is_demo', $demo));
        return $query;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (auth()->user()?->hasRole('Pimpinan Dinas')) {
            if (static::module()['finance']) $query->where('status', 'terverifikasi');
            if (static::getModel() === RkasDetail::class) $query->whereHas('rkas', fn ($q) => $q->where('status', 'terverifikasi'));
        }
        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(ModuleFields::form(static::module()['fields']))->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        $fields = static::module()['fields'];
        if (static::module()['finance']) $fields += ['status' => 'text', 'created_by' => 'text'];
        if (static::key() === 'pagu_bos') $fields += ['pagu_berlaku' => 'money'];
        if (static::key() === 'perubahan_pagu') $fields += ['nilai_lama' => 'money'];
        if (static::key() === 'audit_logs') $fields += ['sebelum' => 'json', 'sesudah' => 'json'];
        if (static::key() === 'import_batches') $fields += ['errors' => 'json'];
        $entries = collect($fields)->map(fn ($type, $name) => TextEntry::make($name)->label(ModuleFields::label($name))
            ->formatStateUsing(fn ($state) => ModuleFields::display($state, $type))->placeholder('—'))->all();
        if (static::key() === 'sekolahs') $entries[] = \Filament\Infolists\Components\RepeatableEntry::make('dataMurids')->label('Riwayat Jumlah Murid')->schema([
            TextEntry::make('tahun_pendataan')->label('Tahun Pendataan'), TextEntry::make('jumlah')->label('Jumlah Murid'), TextEntry::make('sumber')->label('Sumber'),
        ])->columns(3)->columnSpanFull();
        if (static::key() === 'pagu_bos') $entries[] = \Filament\Infolists\Components\RepeatableEntry::make('perubahans')->label('Riwayat Perubahan Pagu')->schema([
            TextEntry::make('tanggal')->date('d/m/Y'), TextEntry::make('nilai_lama')->label('Nilai Lama')->formatStateUsing(fn ($state) => \App\Services\Money::idr($state)),
            TextEntry::make('nilai_baru')->label('Nilai Baru')->formatStateUsing(fn ($state) => \App\Services\Money::idr($state)), TextEntry::make('alasan'), TextEntry::make('status'),
        ])->columns(3)->columnSpanFull();
        if (static::key() === 'rkas') $entries[] = \Filament\Infolists\Components\RepeatableEntry::make('details')->label('Rincian Anggaran')->schema([
            TextEntry::make('komponen.nama')->label('Komponen Belanja'), TextEntry::make('nominal')->label('Anggaran')->formatStateUsing(fn ($state) => \App\Services\Money::idr($state)),
        ])->columns(2)->columnSpanFull();
        return $schema->components($entries)->columns(2);
    }

    public static function table(Table $table): Table
    {
        $columns = [TextColumn::make('id')->label('ID')->sortable()]; $filters = [];
        $fields = static::module()['fields'];
        if (static::key() === 'pagu_bos') $fields += ['pagu_berlaku' => 'money'];
        if (static::module()['finance'] || in_array(static::key(), ['sekolahs', 'data_murids', 'rkas_details'])) {
            $filters[] = SelectFilter::make('lingkup')->label('Lingkup Data')->options(['resmi' => 'Data resmi saja', 'demo' => 'Data demo saja'])->query(fn (Builder $query, array $data) => static::applyDataScope($query, $data['value'] ?? null));
        }
        foreach ($fields as $name => $type) {
            $columns[] = TextColumn::make($name)->label(ModuleFields::label($name))
                ->formatStateUsing(fn ($state) => ModuleFields::display($state, $type))
                ->searchable(in_array($name, ['nama', 'npsn', 'kode', 'referensi', 'nomor_dokumen']))
                ->sortable()->wrap()->limit(65)->toggleable(isToggledHiddenByDefault: in_array($name, ['catatan', 'alamat', 'sumber', 'created_at', 'koreksi_atas_id', 'nomor_dokumen', 'periode_awal', 'periode_akhir', 'penyaluran_bos_id', 'rkas_detail_id']));
            if (str_contains($type, ':') && ! str_starts_with($type, 'enum:')) $filters[] = SelectFilter::make($name)->label(ModuleFields::label($name))->options(fn () => ModuleFields::options($type))->searchable();
        }
        if (static::module()['finance']) {
            $columns[] = TextColumn::make('status')->formatStateUsing(fn ($state) => ModuleFields::statuses()[$state] ?? $state)->badge()->color(fn ($state) => match ($state) { 'terverifikasi' => 'success', 'diajukan' => 'warning', 'perlu_perbaikan' => 'danger', default => 'gray' });
            $filters[] = SelectFilter::make('status')->options(ModuleFields::statuses());
        }
        if (static::key() === 'sekolahs') {
            $columns[] = TextColumn::make('data_murid_terakhir')->label('Jumlah Murid Terakhir')->state(function ($record) {
                $data = $record->dataMurids()->orderByDesc('tahun_pendataan')->first();
                return $data ? $data->tahun_pendataan.' · '.$data->jumlah.' murid' : 'Belum ada pendataan';
            });
            $filters[] = SelectFilter::make('status_sekolah')->options(['Negeri' => 'Negeri', 'Swasta' => 'Swasta']);
            $filters[] = SelectFilter::make('aktif')->options(['1' => 'Aktif', '0' => 'Nonaktif']);
        }
        $actions = [ViewAction::make()->label('Detail'), EditAction::make()->label('Ubah')];
        if (static::module()['finance']) {
            $actions[] = Action::make('ajukan')->label('Ajukan')->icon('heroicon-o-paper-airplane')->requiresConfirmation()
                ->visible(fn ($record) => Gate::allows('submit', $record))
                ->action(fn ($record) => app(FinancialWorkflow::class)->transition($record, 'diajukan'));
            $actions[] = Action::make('verifikasi')->label('Verifikasi')->color('success')->icon('heroicon-o-check-badge')->requiresConfirmation()
                ->visible(fn ($record) => Gate::allows('verify', $record))
                ->schema([Textarea::make('catatan')->label('Catatan verifikasi')->maxLength(5000)])
                ->action(fn ($record, array $data) => app(FinancialWorkflow::class)->transition($record, 'terverifikasi', $data['catatan'] ?? null));
            $actions[] = Action::make('perbaikan')->label('Minta Perbaikan')->color('warning')
                ->visible(fn ($record) => Gate::allows('verify', $record))
                ->schema([Textarea::make('catatan')->required()->maxLength(5000)])
                ->action(fn ($record, array $data) => app(FinancialWorkflow::class)->transition($record, 'perlu_perbaikan', $data['catatan']));
            $actions[] = Action::make('unggah')->label('Unggah Bukti')->icon('heroicon-o-paper-clip')
                ->visible(fn ($record) => Gate::allows('update', $record))
                ->schema([FileUpload::make('file')->label('Dokumen pendukung')->storeFiles(false)->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->maxSize(config('bos.max_document_kb'))->required()])
                ->action(function ($record, array $data) { app(DocumentService::class)->store($record, $data['file']); Notification::make()->title('Dokumen tersimpan secara privat')->success()->send(); });
            $actions[] = Action::make('dokumen')->label('Dokumen')->icon('heroicon-o-document-text')
                ->visible(fn ($record) => Gate::allows('download', $record))
                ->modalContent(fn ($record) => view('filament.documents', ['record' => $record]))->modalSubmitAction(false)->modalCancelActionLabel('Tutup');
        }
        if (static::key() === 'dokumen_pendukung') $actions[] = Action::make('download')->label('Unduh')->url(fn ($record) => route('documents.download', $record))->openUrlInNewTab();
        if (static::module()['finance']) $actions = [$actions[0], $actions[1], \Filament\Actions\ActionGroup::make(array_slice($actions, 2))->label('Lainnya')->icon('heroicon-o-ellipsis-vertical')->color('gray')->button()];
        return $table->columns($columns)->filters($filters)->recordActions($actions)->defaultSort('id', 'desc')->striped()
            ->emptyStateHeading('Belum ada '.mb_strtolower(static::getModelLabel()))
            ->emptyStateDescription('Masukkan data berdasarkan sumber yang dapat diverifikasi. Tidak ada data keuangan contoh yang ditampilkan.');
    }
}
