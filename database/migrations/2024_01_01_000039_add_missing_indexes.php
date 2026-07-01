<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->index('sale_id');
            $table->index('medicine_id');
            $table->index('batch_id');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->index('purchase_id');
            $table->index('medicine_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex(['sale_id']);
            $table->dropIndex(['medicine_id']);
            $table->dropIndex(['batch_id']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropIndex(['purchase_id']);
            $table->dropIndex(['medicine_id']);
        });
    }
};
