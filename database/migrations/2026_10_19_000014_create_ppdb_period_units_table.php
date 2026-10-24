<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppdb_period_units', function (Blueprint $table) {
            $table->unsignedBigInteger('ppdb_period_id');
            $table->unsignedBigInteger('school_unit_id');
            $table->timestamps();

            $table->primary(['ppdb_period_id', 'school_unit_id']);
            $table->foreign('ppdb_period_id')->references('id')->on('ppdb_periods')->onDelete('cascade');
            $table->foreign('school_unit_id')->references('id')->on('school_units')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppdb_period_units');
    }
};
