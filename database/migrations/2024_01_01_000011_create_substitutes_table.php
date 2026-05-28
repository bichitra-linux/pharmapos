<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('substitutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id_1')->constrained('medicines')->cascadeOnDelete();
            $table->foreignId('medicine_id_2')->constrained('medicines')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['medicine_id_1', 'medicine_id_2']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substitutes');
    }
};
