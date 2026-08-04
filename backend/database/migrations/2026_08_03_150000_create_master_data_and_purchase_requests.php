<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->unsignedTinyInteger('precision')->default(0);
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('sku', 100);
            $table->string('name');
            $table->string('type', 30)->default('stock');
            $table->foreignUuid('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->boolean('lot_tracking')->default(false);
            $table->boolean('serial_tracking')->default(false);
            $table->boolean('expiry_tracking')->default(false);
            $table->decimal('minimum_price', 20, 4)->default(0);
            $table->string('status', 30)->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'sku']);
        });

        Schema::create('parties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('code', 100);
            $table->string('type', 30)->default('customer');
            $table->string('legal_name');
            $table->string('tax_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('credit_limit', 20, 4)->default(0);
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('tax_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->decimal('rate', 8, 4)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('price_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('currency', 3)->default('IDR');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('price_list_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('price_list_id')->constrained('price_lists')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('minimum_quantity', 20, 6)->default(1);
            $table->decimal('price', 20, 4);
            $table->timestamps();
            $table->unique(['price_list_id', 'item_id', 'minimum_quantity']);
        });

        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('requester_id')->constrained('users')->restrictOnDelete();
            $table->string('number', 80);
            $table->date('request_date');
            $table->date('required_date')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->decimal('estimated_total', 20, 4)->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('purchase_request_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUuid('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->decimal('estimated_unit_price', 20, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['item_id', 'purchase_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_lines');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('price_list_lines');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('tax_codes');
        Schema::dropIfExists('parties');
        Schema::dropIfExists('items');
        Schema::dropIfExists('units');
    }
};
