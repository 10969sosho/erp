<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_bins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('code', 80);
            $table->string('name');
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();
            $table->unique(['warehouse_id', 'code']);
        });
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('number', 80);
            $table->string('count_type', 30)->default('cycle');
            $table->string('status', 30)->default('open')->index();
            $table->date('count_date');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'number']);
        });
        Schema::create('stock_count_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_count_id')->constrained('stock_counts')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('system_quantity', 20, 6);
            $table->decimal('counted_quantity', 20, 6)->nullable();
            $table->decimal('variance', 20, 6)->nullable();
            $table->timestamps();
            $table->unique(['stock_count_id', 'item_id']);
        });
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('code', 80);
            $table->string('name');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->string('statement_number', 100);
            $table->date('statement_date');
            $table->decimal('opening_balance', 20, 4)->default(0);
            $table->decimal('closing_balance', 20, 4)->default(0);
            $table->string('status', 30)->default('open')->index();
            $table->timestamps();
            $table->unique(['bank_account_id', 'statement_number']);
        });
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bank_statement_id')->constrained('bank_statements')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 20, 4);
            $table->string('direction', 10);
            $table->string('status', 30)->default('unmatched')->index();
            $table->uuid('matched_payment_id')->nullable();
            $table->timestamps();
        });
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('entity_type', 150);
            $table->uuid('entity_id');
            $table->string('document_type', 80);
            $table->string('title');
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->index(['tenant_id', 'entity_type', 'entity_id'], 'document_entity_index');
        });
        Schema::create('attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('storage_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('sha256', 64)->nullable();
            $table->string('scan_status', 30)->default('pending');
            $table->timestamps();
        });
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('provider', 100);
            $table->string('direction', 20);
            $table->string('idempotency_key', 150)->nullable();
            $table->string('status', 30)->default('queued')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'idempotency_key'], 'integration_provider_key_unique');
        });
    }

    public function down(): void
    {
        foreach (['integration_logs', 'attachments', 'documents', 'bank_statement_lines', 'bank_statements', 'bank_accounts', 'stock_count_lines', 'stock_counts', 'warehouse_bins'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
