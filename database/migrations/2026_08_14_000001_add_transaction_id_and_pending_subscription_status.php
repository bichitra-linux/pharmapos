<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('payment_status');
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('active')->change();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('status', ['draft', 'partial', 'received', 'cancelled'])->default('draft')->change();
        });

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (! Schema::hasColumn('companies', 'phone_country_code')) {
                $table->string('phone_country_code', 10)->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active')->change();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('status', ['draft', 'received', 'cancelled'])->default('draft')->change();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['city', 'phone_country_code']);
        });
    }
};
