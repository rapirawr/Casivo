<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->default('esp32-01');
            $table->float('suhu');
            $table->float('kelembaban');
            $table->float('kualitas_udara')->nullable();
            $table->float('penggunaan_energi')->nullable();
            $table->timestamps();

            $table->index('device_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensors');
    }
};
