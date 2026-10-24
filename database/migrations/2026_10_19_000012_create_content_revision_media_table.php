<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_revision_media', function (Blueprint $table) {
            $table->unsignedBigInteger('content_revision_id');
            $table->unsignedBigInteger('media_id');
            $table->string('usage_type')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at');

            $table->primary(['content_revision_id', 'media_id']);
            $table->foreign('content_revision_id')->references('id')->on('content_revisions')->onDelete('cascade');
            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revision_media');
    }
};
