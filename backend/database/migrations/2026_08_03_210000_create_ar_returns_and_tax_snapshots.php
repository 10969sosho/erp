<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoice_tax_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignUuid('tax_code_id')->constrained('tax_codes')->restrictOnDelete();
            $table->decimal('taxable_amount', 20, 4);
            $table->decimal('rate', 8, 4);
            $table->decimal('tax_amount', 20, 4);
            $table->timestamps();
        });

        Schema::create('customer_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignUuid('customer_id')->constrained('parties')->restrictOnDelete();
            $table->string('number', 80);
            $table->string('method', 30)->default('bank');
            $table->date('receipt_date');
            $table->decimal('amount', 20, 4);
            $table->string('status', 30)->default('posted')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('customer_receipt_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_receipt_id')->constrained('customer_receipts')->cascadeOnDelete();
            $table->foreignUuid('sales_invoice_id')->constrained('sales_invoices')->restrictOnDelete();
            $table->decimal('amount', 20, 4);
            $table->timestamps();
            $table->unique(['customer_receipt_id', 'sales_invoice_id'], 'customer_receipt_invoice_unique');
        });

        Schema::create('sales_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUuid('sales_order_id')->constrained('sales_orders')->restrictOnDelete();
            $table->foreignUuid('sales_invoice_id')->nullable()->constrained('sales_invoices')->restrictOnDelete();
            $table->string('number', 80);
            $table->date('return_date');
            $table->string('status', 30)->default('posted')->index();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('sales_return_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
            $table->foreignUuid('sales_order_line_id')->constrained('sales_order_lines')->restrictOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('line_total', 20, 4);
            $table->timestamps();
            $table->unique(['sales_return_id', 'sales_order_line_id'], 'sales_return_lines_return_order_unique');
        });

        Schema::create('credit_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignUuid('customer_id')->constrained('parties')->restrictOnDelete();
            $table->foreignUuid('sales_invoice_id')->constrained('sales_invoices')->restrictOnDelete();
            $table->foreignUuid('sales_return_id')->nullable()->constrained('sales_returns')->restrictOnDelete();
            $table->string('number', 80);
            $table->date('credit_date');
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_total', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);
            $table->string('status', 30)->default('posted')->index();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('sales_return_lines');
        Schema::dropIfExists('sales_returns');
        Schema::dropIfExists('customer_receipt_allocations');
        Schema::dropIfExists('customer_receipts');
        Schema::dropIfExists('sales_invoice_tax_lines');
    }
};
