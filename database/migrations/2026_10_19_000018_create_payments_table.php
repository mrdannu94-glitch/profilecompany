<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('registration_id');
            $table->string('provider');
            $table->string('provider_reference')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('IDR');
            $table->string('status');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->string('verification_method');
            $table->text('verification_note')->nullable();
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('registrations')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
