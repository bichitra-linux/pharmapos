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
        // Add piece selling columns to medicines
        Schema::table('medicines', function (Blueprint $table) {
            if (! Schema::hasColumn('medicines', 'allow_piece_selling')) {
                $table->boolean('allow_piece_selling')->default(false)->after('is_temperature_sensitive');
            }
            if (! Schema::hasColumn('medicines', 'piece_unit_label')) {
                $table->string('piece_unit_label', 50)->nullable()->after('allow_piece_selling');
            }
        });

        // Add piece columns to medicine_batches
        Schema::table('medicine_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('medicine_batches', 'quantity_in_pieces')) {
                $table->decimal('quantity_in_pieces', 10, 2)->default(0)->after('quantity_in_stock');
            }
            if (! Schema::hasColumn('medicine_batches', 'received_pieces')) {
                $table->decimal('received_pieces', 10, 2)->default(0)->after('quantity_in_pieces');
            }
        });

        // Add piece columns to sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'units_per_pack')) {
                $table->unsignedInteger('units_per_pack')->default(1)->after('prescription_required');
            }
            if (! Schema::hasColumn('sale_items', 'sell_mode')) {
                $table->string('sell_mode', 10)->default('pack')->after('units_per_pack');
            }
            if (! Schema::hasColumn('sale_items', 'pieces_quantity')) {
                $table->decimal('pieces_quantity', 10, 2)->default(0)->after('sell_mode');
            }
        });

        // Auto-enable piece selling for tablet/capsule dosage forms
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("UPDATE medicines SET allow_piece_selling = 1, piece_unit_label = 'tablet' WHERE dosage_form IN ('tablet', 'capsule')");

            DB::statement("UPDATE medicines SET piece_unit_label = 'capsule' WHERE dosage_form = 'capsule'");

            DB::statement("
                UPDATE medicine_batches mb
                JOIN medicines m ON m.id = mb.medicine_id
                SET mb.quantity_in_pieces = mb.quantity_in_stock * GREATEST(m.units_per_pack, 1)
            ");

            DB::statement("
                UPDATE medicine_batches mb
                JOIN medicines m ON m.id = mb.medicine_id
                SET mb.received_pieces = mb.quantity_in_pieces
            ");
        }
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn(['allow_piece_selling', 'piece_unit_label']);
        });
        Schema::table('medicine_batches', function (Blueprint $table) {
            $table->dropColumn(['quantity_in_pieces', 'received_pieces']);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['units_per_pack', 'sell_mode', 'pieces_quantity']);
        });
    }
};
