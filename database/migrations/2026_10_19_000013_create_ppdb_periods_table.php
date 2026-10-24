<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppdb_periods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->date('registration_start');
            $table->date('registration_end');
            $table->date('announcement_date')->nullable();
            $table->string('status');
            $table->decimal('registration_fee', 15, 2)->nullable();
            $table->string('currency')->default('IDR');
            $table->json('payment_policy')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppdb_periods');
    }
};
