<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penguatan integritas data PPDB hasil audit integrasi 8 model PPDB.
 *
 * Perubahan yang dilakukan:
 * - `registrations.access_code_hash` menjadi nullable (kode terbit setelah
 *   pendaftaran dibuat, lihat ERD 4.15).
 * - `registrations.identity_fingerprint` diindeks untuk mendukung deteksi
 *   pendaftaran ganda di level aplikasi.
 * - `ppdb_requirements.accepted_mime_types` menjadi JSON.
 * - `ppdb_requirements.program_scope_id` sebagai sentinel scope program
 *   (0 = persyaratan umum) untuk unique index gabungan.
 * - `payments` mendapat `attempt_number`, `is_active`, `failure_reason`, dan
 *   sentinel `active_registration_id` untuk mendukung percobaan ulang.
 * - `selection_decisions` mendapat `previous_status` dan sentinel
 *   `current_registration_id` agar hanya satu keputusan berlaku per
 *   pendaftaran.
 * - Index pendukung query periode, persyaratan, pembayaran, notifikasi, dan
 *   audit log.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->normaliseRequirementMimeTypes();
        $this->relaxRegistrationColumns();
        $this->hardenRequirements();
        $this->hardenPayments();
        $this->hardenSelectionDecisions();
        $this->addSupportingIndexes();
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_entity_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_read_idx');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropIndex('programs_unit_active_idx');
        });

        Schema::table('selection_decisions', function (Blueprint $table) {
            $table->dropIndex('selection_registration_decided_idx');
            $table->dropIndex('selection_current_registration_unique');
            $table->dropColumn(['previous_status', 'current_registration_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_registration_status_idx');
            $table->dropIndex('payments_active_registration_unique');
            $table->dropColumn(['attempt_number', 'is_active', 'failure_reason', 'active_registration_id']);
        });

        Schema::table('ppdb_requirements', function (Blueprint $table) {
            $table->dropIndex('req_period_unit_program_idx');
            $table->dropIndex('req_scope_unique');
            $table->dropColumn('program_scope_id');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex('reg_period_unit_status_idx');
            $table->dropIndex('reg_identity_fp_idx');
        });

        Schema::table('ppdb_periods', function (Blueprint $table) {
            $table->dropIndex('ppdb_periods_status_window_idx');
        });
    }

    /**
     * Ubah daftar MIME yang masih berupa CSV menjadi JSON.
     */
    private function normaliseRequirementMimeTypes(): void
    {
        if (DB::table('ppdb_requirements')->count() === 0) {
            return;
        }

        DB::table('ppdb_requirements')->select('id', 'accepted_mime_types')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $value = (string) $row->accepted_mime_types;

                if (str_starts_with(trim($value), '[')) {
                    continue;
                }

                $mimeTypes = array_values(array_filter(array_map(
                    'trim',
                    explode(',', $value)
                )));

                DB::table('ppdb_requirements')
                    ->where('id', $row->id)
                    ->update(['accepted_mime_types' => json_encode($mimeTypes)]);
            }
        });

        Schema::table('ppdb_requirements', function (Blueprint $table) {
            $table->json('accepted_mime_types')->change();
        });
    }

    /**
     * Kode akses baru terbit setelah pendaftaran dibuat.
     */
    private function relaxRegistrationColumns(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('access_code_hash')->nullable()->change();
        });
    }

    /**
     * Persyaratan tidak boleh ganda pada scope yang sama.
     */
    private function hardenRequirements(): void
    {
        Schema::table('ppdb_requirements', function (Blueprint $table) {
            $table->unsignedBigInteger('program_scope_id')->default(0);
        });

        Schema::table('ppdb_requirements', function (Blueprint $table) {
            $table->unique(
                ['ppdb_period_id', 'school_unit_id', 'program_scope_id', 'requirement_code'],
                'req_scope_unique'
            );
        });
    }

    /**
     * Pembayaran mendukung percobaan ulang dengan satu percobaan aktif.
     */
    private function hardenPayments(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('attempt_number')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('failure_reason')->nullable();
            $table->unsignedBigInteger('active_registration_id')->nullable();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unique('active_registration_id', 'payments_active_registration_unique');
        });
    }

    /**
     * Hanya satu keputusan hasil yang berlaku untuk satu pendaftaran.
     */
    private function hardenSelectionDecisions(): void
    {
        Schema::table('selection_decisions', function (Blueprint $table) {
            $table->string('previous_status')->nullable();
            $table->unsignedBigInteger('current_registration_id')->nullable();
        });

        Schema::table('selection_decisions', function (Blueprint $table) {
            $table->unique('current_registration_id', 'selection_current_registration_unique');
        });
    }

    /**
     * Index pendukung query yang paling sering dipakai portal dan PPDB.
     */
    private function addSupportingIndexes(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->index(['ppdb_period_id', 'identity_fingerprint'], 'reg_identity_fp_idx');
            $table->index(['ppdb_period_id', 'school_unit_id', 'status'], 'reg_period_unit_status_idx');
        });

        Schema::table('ppdb_requirements', function (Blueprint $table) {
            $table->index(['ppdb_period_id', 'school_unit_id', 'program_id'], 'req_period_unit_program_idx');
        });

        Schema::table('ppdb_periods', function (Blueprint $table) {
            $table->index(['status', 'registration_start', 'registration_end'], 'ppdb_periods_status_window_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['registration_id', 'status'], 'payments_registration_status_idx');
        });

        Schema::table('selection_decisions', function (Blueprint $table) {
            $table->index(['registration_id', 'decided_at'], 'selection_registration_decided_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('entity_id')->nullable()->change();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['entity_type', 'entity_id', 'occurred_at'], 'audit_logs_entity_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at', 'created_at'], 'notifications_user_read_idx');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->index(['school_unit_id', 'is_active'], 'programs_unit_active_idx');
        });
    }
};
