<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Otorisasi pembayaran pendaftaran.
 *
 * Verifikasi manual hanya boleh dilakukan petugas yang memiliki permission
 * `verify_ppdb_payment_manual`. Pemeriksa dokumen tidak otomatis mendapat
 * hak ini.
 */
class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(['verify_ppdb_payment_manual', 'verify_payment', 'manage_periods']);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasPermission(['verify_ppdb_payment_manual', 'verify_payment', 'manage_periods']);
    }

    /**
     * Konfirmasi pembayaran manual.
     */
    public function verify(User $user, Payment $payment): bool
    {
        return $user->hasPermission(['verify_ppdb_payment_manual', 'verify_payment']);
    }
}
