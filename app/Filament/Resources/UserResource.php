<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Services\UserAdministration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput, Select, Toggle};
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Actions\EditAction;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Pengguna';
    protected static string | \UnitEnum | null $navigationGroup = 'PENGATURAN';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    public static function canViewAny(): bool { return auth()->user()?->can('users.manage') ?? false; }
    public static function canCreate(): bool { return static::canViewAny(); }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return static::canViewAny(); }
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(255), TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('password')->label('Kata Sandi')->password()->revealable()->minLength(12)->required(fn (string $operation) => $operation === 'create')->helperText('Minimal 12 karakter. Saat mengubah, kosongkan untuk mempertahankan kata sandi.'),
            Select::make('role_ids')->label('Role')->multiple()->options(fn () => Role::pluck('name', 'id'))->required(), Toggle::make('aktif')->label('Akun Aktif')->default(true),
        ])->columns(2);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->label('Nama')->searchable(), TextColumn::make('email')->searchable(), TextColumn::make('roles.name')->label('Role')->badge(), IconColumn::make('aktif')->boolean(), TextColumn::make('created_at')->label('Dibuat')->dateTime('d/m/Y H:i')])
            ->recordActions([EditAction::make()->label('Ubah')->fillForm(fn (User $record) => ['name' => $record->name, 'email' => $record->email, 'password' => null, 'aktif' => (bool) $record->aktif, 'role_ids' => $record->roles->pluck('id')->all()])->action(fn (User $record, array $data) => app(UserAdministration::class)->save($data, $record))]);
    }
    public static function getPages(): array { return ['index' => UserResource\Pages\ManageUsers::route('/')]; }
}
