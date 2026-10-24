<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('action');
            $table->string('entity_type');
            $table->string('entity_id');
            $table->string('outcome');
            $table->string('request_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('actor_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
