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
        Schema::table('sensors', function (Blueprint $table) {
            $table->integer('wifi_rssi')->nullable();
            $table->float('internal_temp')->nullable();
            $table->integer('uptime')->nullable();
            $table->integer('free_ram')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropColumn(['wifi_rssi', 'internal_temp', 'uptime', 'free_ram']);
        });
    }
};
