<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('prescription_number');
            $table->string('doctor_name')->nullable();
            $table->string('hospital_name')->nullable();
            $table->date('prescription_date')->nullable();
            $table->string('image_path')->nullable();
            $table->enum('status', ['pending', 'dispensed', 'partial', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'prescription_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
