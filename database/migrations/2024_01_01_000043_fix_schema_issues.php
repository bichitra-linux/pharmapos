<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasForeignKey(string $table, string $column): bool
    {
        // SQLite does not have information_schema — skip FK introspection
        if (DB::connection()->getDriverName() === 'sqlite') {
            return false;
        }

        $conn = Schema::getConnection();
        $schemaName = $conn->getDatabaseName();
        $rows = $conn->select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
             AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$schemaName, $table, $column]
        );
        return count($rows) > 0;
    }

    public function up(): void
    {
        // 1. Add received_quantity to purchase_items (C9 fix)
        if (!Schema::hasColumn('purchase_items', 'received_quantity')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                $table->decimal('received_quantity', 10, 2)->default(0)->after('quantity');
            });
        }

        // 2. Fix cascade deletes on legally required records (C12-C15)
        $cascadeFixes = [
            ['table' => 'narcotics_register', 'column' => 'dispensed_by'],
            ['table' => 'customer_returns', 'column' => 'processed_by'],
            ['table' => 'inventory_adjustments', 'column' => 'adjusted_by'],
            ['table' => 'registers', 'column' => 'user_id'],
        ];

        foreach ($cascadeFixes as $fix) {
            $tableName = $fix['table'];
            $columnName = $fix['column'];

            // Make column nullable
            Schema::table($tableName, function (Blueprint $table) use ($columnName) {
                $table->unsignedBigInteger($columnName)->nullable()->change();
            });

            // Drop existing FK if it exists, then add nullOnDelete
            if ($this->hasForeignKey($tableName, $columnName)) {
                Schema::table($tableName, function (Blueprint $table) use ($columnName) {
                    $table->dropForeign([$columnName]);
                });
            }

            Schema::table($tableName, function (Blueprint $table) use ($columnName) {
                $table->foreign($columnName)->references('id')->on('users')->nullOnDelete();
            });
        }

        // 3. Add missing columns (H1-H3)
        if (!Schema::hasColumn('medicine_categories', 'slug')) {
            Schema::table('medicine_categories', function (Blueprint $table) {
                $table->string('slug')->nullable()->unique()->after('name');
            });
        }

        if (!Schema::hasColumn('prescription_items', 'dosage_instructions')) {
            Schema::table('prescription_items', function (Blueprint $table) {
                $table->text('dosage_instructions')->nullable()->after('notes');
            });
        }

        if (!Schema::hasColumn('supplier_returns', 'notes')) {
            Schema::table('supplier_returns', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('reason');
            });
        }

        // 4. Add unique constraints (H18-H19)
        try {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->unique(['company_id', 'name'], 'suppliers_unique_name_per_company');
            });
        } catch (\Exception $e) {
            // Index may already exist
        }

        try {
            Schema::table('manufacturers', function (Blueprint $table) {
                $table->unique(['company_id', 'name'], 'manufacturers_unique_name_per_company');
            });
        } catch (\Exception $e) {
        }

        try {
            Schema::table('medicine_categories', function (Blueprint $table) {
                $table->unique(['company_id', 'name'], 'medicine_categories_unique_name_per_company');
            });
        } catch (\Exception $e) {
        }

        try {
            Schema::table('medicine_batches', function (Blueprint $table) {
                $table->unique(['medicine_id', 'outlet_id', 'batch_number'], 'batches_unique_per_outlet');
            });
        } catch (\Exception $e) {
        }

        // 5. Add missing FK constraints for columns added in 000042 (H6-H7)
        if (Schema::hasColumn('medicine_batches', 'supplier_id') && Schema::hasColumn('medicine_batches', 'purchase_id')) {
            if (! $this->hasForeignKey('medicine_batches', 'supplier_id')) {
                try {
                    Schema::table('medicine_batches', function (Blueprint $table) {
                        $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
                    });
                } catch (\Exception $e) {
                }
            }
            if (! $this->hasForeignKey('medicine_batches', 'purchase_id')) {
                try {
                    Schema::table('medicine_batches', function (Blueprint $table) {
                        $table->foreign('purchase_id')->references('id')->on('purchases')->nullOnDelete();
                    });
                } catch (\Exception $e) {
                }
            }
        }

        if (! $this->hasForeignKey('customer_returns', 'customer_id')) {
            try {
                Schema::table('customer_returns', function (Blueprint $table) {
                    $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
                });
            } catch (\Exception $e) {
            }
        }

        // 6. Generate slugs for existing medicine_categories
        if (Schema::hasColumn('medicine_categories', 'slug')) {
            $categories = DB::table('medicine_categories')->whereNull('slug')->get();
            foreach ($categories as $category) {
                $slug = str($category->name)->slug().'-'.$category->id;
                DB::table('medicine_categories')->where('id', $category->id)->update(['slug' => $slug]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_items', 'received_quantity')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                $table->dropColumn('received_quantity');
            });
        }

        $cascadeFixes = [
            ['table' => 'narcotics_register', 'column' => 'dispensed_by'],
            ['table' => 'customer_returns', 'column' => 'processed_by'],
            ['table' => 'inventory_adjustments', 'column' => 'adjusted_by'],
            ['table' => 'registers', 'column' => 'user_id'],
        ];

        foreach ($cascadeFixes as $fix) {
            $tableName = $fix['table'];
            $columnName = $fix['column'];
            if ($this->hasForeignKey($tableName, $columnName)) {
                Schema::table($tableName, function (Blueprint $table) use ($columnName) {
                    $table->dropForeign([$columnName]);
                    $table->foreign($columnName)->references('id')->on('users')->cascadeOnDelete();
                });
            }
        }

        if (Schema::hasColumn('medicine_categories', 'slug')) {
            Schema::table('medicine_categories', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }

        if (Schema::hasColumn('prescription_items', 'dosage_instructions')) {
            Schema::table('prescription_items', function (Blueprint $table) {
                $table->dropColumn('dosage_instructions');
            });
        }

        if (Schema::hasColumn('supplier_returns', 'notes')) {
            Schema::table('supplier_returns', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }

        try {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropIndex('suppliers_unique_name_per_company');
            });
        } catch (\Exception $e) {
        }

        try {
            Schema::table('manufacturers', function (Blueprint $table) {
                $table->dropIndex('manufacturers_unique_name_per_company');
            });
        } catch (\Exception $e) {
        }

        if ($this->hasForeignKey('medicine_batches', 'supplier_id')) {
            Schema::table('medicine_batches', function (Blueprint $table) {
                $table->dropForeign(['supplier_id']);
            });
        }
        if ($this->hasForeignKey('medicine_batches', 'purchase_id')) {
            Schema::table('medicine_batches', function (Blueprint $table) {
                $table->dropForeign(['purchase_id']);
            });
        }
        if ($this->hasForeignKey('customer_returns', 'customer_id')) {
            Schema::table('customer_returns', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });
        }

        try {
            Schema::table('medicine_batches', function (Blueprint $table) {
                $table->dropIndex('batches_unique_per_outlet');
            });
        } catch (\Exception $e) {
        }

        try {
            Schema::table('medicine_categories', function (Blueprint $table) {
                $table->dropIndex('medicine_categories_unique_name_per_company');
            });
        } catch (\Exception $e) {
        }
    }
};
