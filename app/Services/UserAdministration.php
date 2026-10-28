<?php

namespace App\Services;

use App\Models\{User, AuditLog};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};
use Spatie\Permission\Models\Role;

class UserAdministration
{
    public function save(array $data, ?User $user = null): User
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);
        $values = validator($data, [
            'name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:12', 'max:255'],
            'role_ids' => ['required', 'array', 'min:1'], 'role_ids.*' => ['integer', Rule::exists('roles', 'id')], 'aktif' => ['required', 'boolean'],
        ])->validate();
        return DB::transaction(function () use ($values, $user) {
            $roles = Role::whereIn('id', $values['role_ids'])->get();
            $super = $roles->contains('name', 'Super Admin');
            if ($user && $user->id === auth()->id() && (! $super || ! $values['aktif'])) throw ValidationException::withMessages(['role_ids' => 'Akun yang sedang digunakan harus tetap aktif sebagai Super Admin.']);
            if ($user && $user->hasRole('Super Admin') && (! $super || ! $values['aktif'])) {
                $admins = User::role('Super Admin')->where('aktif', true)->lockForUpdate()->get();
                if ($admins->count() <= 1) throw ValidationException::withMessages(['role_ids' => 'Minimal satu Super Admin aktif harus dipertahankan.']);
            }
            $before = $user ? ['name' => $user->name, 'email' => $user->email, 'roles' => $user->getRoleNames()->all(), 'aktif' => $user->aktif] : null;
            $user ??= new User();
            $user->forceFill(['name' => $values['name'], 'email' => $values['email'], 'aktif' => $values['aktif']]);
            if (filled($values['password'] ?? null)) $user->password = $values['password'];
            $user->save(); $user->syncRoles($roles);
            AuditLog::create(['target_type' => User::class, 'target_id' => $user->id, 'user_id' => auth()->id(), 'aksi' => 'kelola_pengguna', 'sebelum' => $before, 'sesudah' => ['name' => $user->name, 'email' => $user->email, 'roles' => $user->getRoleNames()->all(), 'aktif' => $user->aktif]]);
            return $user;
        });
    }
}
