<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ppdb_period_id');
            $table->unsignedBigInteger('school_unit_id');
            $table->unsignedBigInteger('program_id')->nullable();
            $table->string('registration_number')->unique();
            $table->string('candidate_name');
            $table->date('date_of_birth')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();
            $table->string('parent_email')->nullable();
            $table->string('access_code_hash');
            $table->timestamp('access_code_expires_at')->nullable();
            $table->timestamp('access_code_revoked_at')->nullable();
            $table->string('identity_fingerprint')->nullable();
            $table->timestamp('data_consent_at')->nullable();
            $table->string('privacy_notice_version')->nullable();
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('ppdb_period_id')->references('id')->on('ppdb_periods')->onDelete('cascade');
            $table->foreign('school_unit_id')->references('id')->on('school_units')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
