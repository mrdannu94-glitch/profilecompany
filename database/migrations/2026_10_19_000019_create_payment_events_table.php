<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_id');
            $table->string('provider');
            $table->string('provider_event_id');
            $table->string('event_type');
            $table->string('processing_status');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('payload_reference')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_event_id']);
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
