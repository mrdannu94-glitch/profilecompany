<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('selection_decisions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('registration_id');
            $table->unsignedBigInteger('decided_by');
            $table->string('decision_status');
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at');
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('registrations')->onDelete('cascade');
            $table->foreign('decided_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('selection_decisions');
    }
};
