<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $result = DB::selectOne(
                "SELECT COUNT(*) as cnt FROM sqlite_master WHERE type = 'index' AND name = ?",
                [$indexName]
            );
            return $result->cnt > 0;
        }

        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            "SELECT COUNT(*) as cnt FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$database, $table, $indexName]
        );
        return $result->cnt > 0;
    }

    private function addIndexIfNotExists(Blueprint $table, string $tableName, string|array $columns, ?string $indexName = null): void
    {
        if (is_array($columns)) {
            $name = $indexName ?? ($tableName . '_' . implode('_', (array) $columns) . '_index');
        } else {
            $name = $indexName ?? ($tableName . '_' . $columns . '_index');
        }
        if (!$this->indexExists($tableName, $name)) {
            $table->index($columns, $name);
        }
    }

    private function addUniqueIfNotExists(Blueprint $table, string $tableName, array $columns, ?string $indexName = null): void
    {
        $name = $indexName ?? ($tableName . '_' . implode('_', $columns) . '_unique');
        if (!$this->indexExists($tableName, $name)) {
            $table->unique($columns, $name);
        }
    }

    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'companies', 'subscription_plan_id');
        });

        Schema::table('outlets', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'outlets', 'company_id');
        });

        Schema::table('manufacturers', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'manufacturers', 'company_id');
        });

        Schema::table('medicine_categories', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'medicine_categories', 'company_id');
        });

        Schema::table('medicines', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'medicines', 'manufacturer_id');
            $this->addIndexIfNotExists($table, 'medicines', 'salt_composition_id');
            $this->addIndexIfNotExists($table, 'medicines', 'medicine_category_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'customers', ['company_id', 'is_active']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'suppliers', ['company_id', 'is_active']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'sales', 'outlet_id');
            $this->addIndexIfNotExists($table, 'sales', 'customer_id');
            $this->addIndexIfNotExists($table, 'sales', 'dispensed_by');
            $this->addIndexIfNotExists($table, 'sales', ['company_id', 'outlet_id', 'created_at']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'sale_items', 'sale_id');
            $this->addIndexIfNotExists($table, 'sale_items', 'medicine_id');
            $this->addIndexIfNotExists($table, 'sale_items', 'batch_id');
        });

        Schema::table('sale_payments', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'sale_payments', 'sale_id');
            $this->addIndexIfNotExists($table, 'sale_payments', 'payment_method_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'purchases', 'outlet_id');
            $this->addIndexIfNotExists($table, 'purchases', 'supplier_id');
            $this->addIndexIfNotExists($table, 'purchases', ['company_id', 'outlet_id', 'created_at']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'purchase_items', 'purchase_id');
            $this->addIndexIfNotExists($table, 'purchase_items', 'medicine_id');
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'supplier_payments', 'company_id');
            $this->addIndexIfNotExists($table, 'supplier_payments', 'supplier_id');
            $this->addIndexIfNotExists($table, 'supplier_payments', 'purchase_id');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'prescriptions', 'company_id');
            $this->addIndexIfNotExists($table, 'prescriptions', 'customer_id');
        });

        Schema::table('prescription_items', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'prescription_items', 'prescription_id');
            $this->addIndexIfNotExists($table, 'prescription_items', 'medicine_id');
        });

        Schema::table('customer_returns', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'customer_returns', 'company_id');
            $this->addIndexIfNotExists($table, 'customer_returns', 'outlet_id');
            $this->addIndexIfNotExists($table, 'customer_returns', 'sale_id');
        });

        Schema::table('customer_return_items', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'customer_return_items', 'return_id');
        });

        Schema::table('supplier_returns', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'supplier_returns', 'company_id');
            $this->addIndexIfNotExists($table, 'supplier_returns', 'supplier_id');
            $this->addUniqueIfNotExists($table, 'supplier_returns', ['company_id', 'return_number']);
        });

        Schema::table('supplier_return_items', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'supplier_return_items', 'supplier_return_id');
        });

        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'inventory_adjustments', 'company_id');
            $this->addIndexIfNotExists($table, 'inventory_adjustments', 'outlet_id');
        });

        Schema::table('adjustment_items', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'adjustment_items', 'adjustment_id');
        });

        Schema::table('narcotics_register', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'narcotics_register', 'company_id');
            $this->addIndexIfNotExists($table, 'narcotics_register', 'outlet_id');
            $this->addIndexIfNotExists($table, 'narcotics_register', 'medicine_id');
        });

        Schema::table('registers', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'registers', 'company_id');
            $this->addIndexIfNotExists($table, 'registers', 'outlet_id');
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'subscription_payments', 'company_id');
            $this->addIndexIfNotExists($table, 'subscription_payments', 'plan_id');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'audit_logs', 'user_id');
        });

        Schema::table('medicine_batches', function (Blueprint $table) {
            $this->addIndexIfNotExists($table, 'medicine_batches', ['medicine_id', 'outlet_id', 'expiry_date']);
            $this->addIndexIfNotExists($table, 'medicine_batches', ['outlet_id', 'quantity_in_stock']);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['subscription_plan_id']);
        });
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
        });
        Schema::table('manufacturers', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
        });
        Schema::table('medicine_categories', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
        });
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropIndex(['manufacturer_id']);
            $table->dropIndex(['salt_composition_id']);
            $table->dropIndex(['medicine_category_id']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'is_active']);
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'is_active']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['outlet_id']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['dispensed_by']);
            $table->dropIndex(['company_id', 'outlet_id', 'created_at']);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex(['sale_id']);
            $table->dropIndex(['medicine_id']);
            $table->dropIndex(['batch_id']);
        });
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropIndex(['sale_id']);
            $table->dropIndex(['payment_method_id']);
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['outlet_id']);
            $table->dropIndex(['supplier_id']);
            $table->dropIndex(['company_id', 'outlet_id', 'created_at']);
        });
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropIndex(['purchase_id']);
            $table->dropIndex(['medicine_id']);
        });
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['supplier_id']);
            $table->dropIndex(['purchase_id']);
        });
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['customer_id']);
        });
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropIndex(['prescription_id']);
            $table->dropIndex(['medicine_id']);
        });
        Schema::table('customer_returns', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['outlet_id']);
            $table->dropIndex(['sale_id']);
        });
        Schema::table('customer_return_items', function (Blueprint $table) {
            $table->dropIndex(['return_id']);
        });
        Schema::table('supplier_returns', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['supplier_id']);
            $table->dropUnique(['company_id', 'return_number']);
        });
        Schema::table('supplier_return_items', function (Blueprint $table) {
            $table->dropIndex(['supplier_return_id']);
        });
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['outlet_id']);
        });
        Schema::table('adjustment_items', function (Blueprint $table) {
            $table->dropIndex(['adjustment_id']);
        });
        Schema::table('narcotics_register', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['outlet_id']);
            $table->dropIndex(['medicine_id']);
        });
        Schema::table('registers', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['outlet_id']);
        });
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['plan_id']);
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
        Schema::table('medicine_batches', function (Blueprint $table) {
            $table->dropIndex(['medicine_id', 'outlet_id', 'expiry_date']);
            $table->dropIndex(['outlet_id', 'quantity_in_stock']);
        });
    }
};
