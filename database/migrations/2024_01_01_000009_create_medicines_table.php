<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('generic_name');
            $table->string('brand_name');
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('salt_composition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('medicine_category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('dosage_form', [
                'tablet', 'capsule', 'syrup', 'injection', 'ointment', 'cream',
                'drops', 'inhaler', 'powder', 'gel', 'lotion', 'suspension',
                'solution', 'suppository', 'patch', 'spray', 'other',
            ])->default('tablet');
            $table->string('strength')->nullable();
            $table->enum('unit_type', [
                'strip', 'bottle', 'tube', 'piece', 'box', 'vial', 'sachet', 'roll',
            ])->default('strip');
            $table->unsignedInteger('units_per_pack')->default(1);
            $table->enum('schedule_type', ['h', 'h1', 'x', 'g', 'otc'])->default('otc');
            $table->string('hsn_code')->nullable();
            $table->boolean('is_prescription_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('barcode')->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('storage_conditions')->nullable();
            $table->boolean('is_temperature_sensitive')->default(false);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index('brand_name');
            $table->index('generic_name');
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
