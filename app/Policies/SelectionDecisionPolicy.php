<?php

namespace App\Policies;

use App\Models\SelectionDecision;
use App\Models\User;

/**
 * Otorisasi keputusan hasil seleksi.
 *
 * Hanya permission `set_selection_result` yang boleh menetapkan hasil
 * akhir. Petugas pemeriksa dokumen tidak otomatis berhak atas hak ini.
 */
class SelectionDecisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('set_selection_result');
    }

    public function view(User $user, SelectionDecision $decision): bool
    {
        return $user->hasPermission('set_selection_result');
    }

    /**
     * Tetapkan hasil seleksi.
     */
    public function create(User $user, SelectionDecision $decision): bool
    {
        return $user->hasPermission('set_selection_result');
    }
}
