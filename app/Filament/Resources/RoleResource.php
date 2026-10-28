<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput, Select, Hidden};
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;

class RoleResource extends Resource
{
    protected static ?string $model = \Spatie\Permission\Models\Role::class;
    protected static ?string $modelLabel = 'Role dan Permission';
    protected static ?string $pluralModelLabel = 'Role dan Permission';
    protected static string | \UnitEnum | null $navigationGroup = 'PENGATURAN';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';
    public static function canViewAny(): bool { return auth()->user()?->can('roles.manage') ?? false; }
    public static function canCreate(): bool { return static::canViewAny(); }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return static::canViewAny() && $record->name !== 'Super Admin'; }
    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->label('Nama Role')->required()->unique(ignoreRecord: true)->disabledOn('edit'), Hidden::make('guard_name')->default('web'),
            Select::make('permissions')->label('Permission')->multiple()->relationship('permissions', 'name')->searchable()->preload()->required()]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->label('Role'), TextColumn::make('permissions_count')->counts('permissions')->label('Jumlah Permission')])->recordActions([EditAction::make()->label('Atur Permission')->after(function ($record) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            \App\Models\AuditLog::create(['target_type' => get_class($record), 'target_id' => $record->id, 'user_id' => auth()->id(), 'aksi' => 'ubah_permission_role', 'sesudah' => ['name' => $record->name, 'permissions' => $record->permissions()->pluck('name')->all()]]);
        })]);
    }
    public static function getPages(): array { return ['index' => RoleResource\Pages\ManageRoles::route('/')]; }
}
