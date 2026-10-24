<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppdb_requirements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ppdb_period_id');
            $table->unsignedBigInteger('school_unit_id');
            $table->unsignedBigInteger('program_id')->nullable();
            $table->string('requirement_code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('accepted_mime_types');
            $table->bigInteger('max_file_size');
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('ppdb_period_id')->references('id')->on('ppdb_periods')->onDelete('cascade');
            $table->foreign('school_unit_id')->references('id')->on('school_units')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppdb_requirements');
    }
};
