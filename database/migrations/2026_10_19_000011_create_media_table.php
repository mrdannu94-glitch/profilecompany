<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('disk');
            $table->string('file_path')->unique();
            $table->string('original_name')->nullable();
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->string('storage_visibility');
            $table->string('checksum')->nullable();
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
