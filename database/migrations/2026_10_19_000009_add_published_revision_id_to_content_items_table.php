<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->unsignedBigInteger('published_revision_id')->nullable()->after('id');
            $table->foreign('published_revision_id')->references('id')->on('content_revisions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->dropForeign(['published_revision_id']);
            $table->dropColumn('published_revision_id');
        });
    }
};
