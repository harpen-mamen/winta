<?php

namespace App\Policies;

use App\Models\{User, BosModel, FinancialRecord, RkasDetail};

class BosPolicy
{
    public function viewAny(User $user): bool { return $user->can('monitoring.view'); }
    public function view(User $user, BosModel $record): bool
    {
        if (! $user->can($record->getTable().'.view')) return false;
        if ($user->hasRole('Pimpinan Dinas')) {
            if ($record instanceof FinancialRecord) return $record->status === 'terverifikasi';
            if ($record instanceof RkasDetail) return $record->rkas->status === 'terverifikasi';
        }
        return true;
    }
    public function create(User $user): bool { return $user->can('data.write'); }
    public function update(User $user, BosModel $record): bool
    {
        return $user->can($record->getTable().'.write') && (! $record instanceof FinancialRecord || in_array($record->status, ['draft', 'perlu_perbaikan']))
            && (! $record instanceof RkasDetail || in_array($record->rkas->status, ['draft', 'perlu_perbaikan']));
    }
    public function delete(User $user, BosModel $record): bool { return false; }
    public function deleteAny(User $user): bool { return false; }
    public function submit(User $user, FinancialRecord $record): bool { return $user->can($record->getTable().'.write') && in_array($record->status, ['draft', 'perlu_perbaikan']); }
    public function verify(User $user, FinancialRecord $record): bool { return $user->can('finance.verify') && $record->status === 'diajukan' && $record->created_by !== $user->id; }
    public function download(User $user, BosModel $record): bool { return $user->can('documents.download') && $this->view($user, $record); }
}
