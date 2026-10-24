<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_unit_id');
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('school_unit_id')->references('id')->on('school_units')->onDelete('cascade');
            $table->unique(['school_unit_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
