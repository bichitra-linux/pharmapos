<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('medicine_batches', 'reorder_level')) {
                $table->integer('reorder_level')->nullable()->default(10)->after('is_active');
            }
            if (!Schema::hasColumn('medicine_batches', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('reorder_level');
            }
            if (!Schema::hasColumn('medicine_batches', 'purchase_id')) {
                $table->foreignId('purchase_id')->nullable()->after('supplier_id');
            }
        });

        Schema::table('customer_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_returns', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('outlet_id');
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'country')) {
                $table->string('country')->nullable()->default('Nepal')->after('address');
            }
            if (!Schema::hasColumn('companies', 'state')) {
                $table->string('state')->nullable()->after('country');
            }
            if (!Schema::hasColumn('companies', 'local_level')) {
                $table->string('local_level')->nullable()->after('state');
            }
            if (!Schema::hasColumn('companies', 'registration_number')) {
                $table->string('registration_number')->nullable()->after('pan_number');
            }
            if (!Schema::hasColumn('companies', 'google_maps_link')) {
                $table->string('google_maps_link')->nullable()->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medicine_batches', function (Blueprint $table) {
            $table->dropColumn(['reorder_level', 'supplier_id', 'purchase_id']);
        });
        Schema::table('customer_returns', function (Blueprint $table) {
            $table->dropColumn(['customer_id']);
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['country', 'state', 'local_level', 'registration_number', 'google_maps_link']);
        });
    }
};
