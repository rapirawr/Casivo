<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // light, fan, ac, tv, speaker, etc.
            $table->string('icon');
            $table->string('room')->default('Living Room');
            $table->boolean('is_on')->default(false);
            $table->integer('brightness')->nullable(); // for lights (0-100)
            $table->integer('speed')->nullable(); // for fans (1-5)
            $table->float('temperature_setting')->nullable(); // for AC
            $table->string('color')->default('#3b82f6'); // accent color
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
