<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('purchase_request_id')->nullable()->constrained('purchase_requests')->restrictOnDelete();
            $table->string('number', 80);
            $table->date('request_date');
            $table->date('quotation_deadline')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('rfq_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUuid('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['rfq_id', 'item_id']);
        });

        Schema::create('rfq_suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained('parties')->restrictOnDelete();
            $table->string('status', 30)->default('invited');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['rfq_id', 'supplier_id']);
        });

        Schema::create('supplier_quotations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignUuid('rfq_id')->constrained('rfqs')->restrictOnDelete();
            $table->foreignUuid('supplier_id')->constrained('parties')->restrictOnDelete();
            $table->string('number', 80);
            $table->string('currency', 3)->default('IDR');
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->unsignedInteger('payment_days')->default(0);
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_total', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);
            $table->string('status', 30)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
            $table->unique(['rfq_id', 'supplier_id']);
        });

        Schema::create('supplier_quotation_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_quotation_id')->constrained('supplier_quotations')->cascadeOnDelete();
            $table->foreignUuid('rfq_line_id')->constrained('rfq_lines')->restrictOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('line_total', 20, 4);
            $table->date('promised_date')->nullable();
            $table->timestamps();
            $table->unique(['supplier_quotation_id', 'rfq_line_id'], 'sq_lines_quote_rfq_unique');
        });

        Schema::create('quotation_comparisons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignUuid('rfq_id')->constrained('rfqs')->restrictOnDelete();
            $table->string('number', 80);
            $table->string('status', 30)->default('draft')->index();
            $table->foreignUuid('selected_quotation_id')->nullable()->constrained('supplier_quotations')->nullOnDelete();
            $table->text('decision_notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('quotation_comparison_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quotation_comparison_id')->constrained('quotation_comparisons')->cascadeOnDelete();
            $table->foreignUuid('supplier_quotation_id')->constrained('supplier_quotations')->restrictOnDelete();
            $table->decimal('evaluated_total', 20, 4);
            $table->decimal('score', 8, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['quotation_comparison_id', 'supplier_quotation_id'], 'cmp_lines_cmp_quote_unique');
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('supplier_id')->constrained('parties')->restrictOnDelete();
            $table->foreignUuid('purchase_request_id')->nullable()->constrained('purchase_requests')->restrictOnDelete();
            $table->foreignUuid('supplier_quotation_id')->nullable()->constrained('supplier_quotations')->restrictOnDelete();
            $table->string('number', 80);
            $table->string('currency', 3)->default('IDR');
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->unsignedInteger('payment_days')->default(0);
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_total', 20, 4)->default(0);
            $table->decimal('total', 20, 4)->default(0);
            $table->string('status', 30)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUuid('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('line_total', 20, 4);
            $table->decimal('received_quantity', 20, 6)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('quotation_comparison_lines');
        Schema::dropIfExists('quotation_comparisons');
        Schema::dropIfExists('supplier_quotation_lines');
        Schema::dropIfExists('supplier_quotations');
        Schema::dropIfExists('rfq_suppliers');
        Schema::dropIfExists('rfq_lines');
        Schema::dropIfExists('rfqs');
    }
};
