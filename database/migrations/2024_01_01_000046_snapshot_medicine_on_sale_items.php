<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'medicine_name')) {
                $table->string('medicine_name')->nullable()->after('total');
            }
            if (! Schema::hasColumn('sale_items', 'medicine_generic_name')) {
                $table->string('medicine_generic_name')->nullable()->after('medicine_name');
            }
            if (! Schema::hasColumn('sale_items', 'medicine_strength')) {
                $table->string('medicine_strength')->nullable()->after('medicine_generic_name');
            }
            if (! Schema::hasColumn('sale_items', 'medicine_manufacturer')) {
                $table->string('medicine_manufacturer')->nullable()->after('medicine_strength');
            }
            if (! Schema::hasColumn('sale_items', 'medicine_dosage_form')) {
                $table->string('medicine_dosage_form')->nullable()->after('medicine_manufacturer');
            }
            $table->index('medicine_name', 'idx_sale_items_medicine_name');
        });

        // Backfill: snapshot current medicine + manufacturer data into existing sale_items
        DB::statement("
            UPDATE sale_items si
            JOIN medicines m ON m.id = si.medicine_id
            LEFT JOIN manufacturers mf ON mf.id = m.manufacturer_id
            SET
                si.medicine_name = m.brand_name,
                si.medicine_generic_name = m.generic_name,
                si.medicine_strength = m.strength,
                si.medicine_manufacturer = mf.name,
                si.medicine_dosage_form = m.dosage_form
            WHERE si.medicine_name IS NULL
        ");

        // Fallback for orphaned sale_items whose medicine was deleted
        DB::statement("
            UPDATE sale_items si
            SET si.medicine_name = CONCAT('Deleted medicine #', si.medicine_id)
            WHERE si.medicine_name IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_sale_items_medicine_name');
            $table->dropColumn([
                'medicine_name',
                'medicine_generic_name',
                'medicine_strength',
                'medicine_manufacturer',
                'medicine_dosage_form',
            ]);
        });
    }
};
