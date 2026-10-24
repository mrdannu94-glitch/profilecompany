<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('registration_id');
            $table->unsignedBigInteger('ppdb_requirement_id');
            $table->unsignedBigInteger('media_id');
            $table->integer('version_number');
            $table->string('status');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->unique(['registration_id', 'ppdb_requirement_id', 'version_number'], 'reg_doc_unique');
            $table->foreign('registration_id')->references('id')->on('registrations')->onDelete('cascade');
            $table->foreign('ppdb_requirement_id')->references('id')->on('ppdb_requirements')->onDelete('cascade');
            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_documents');
    }
};
