<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('entity_type', 120);
            $table->string('name');
            $table->json('steps');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'entity_type', 'active'], 'wf_def_scope_index');
        });
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('definition_id')->constrained('workflow_definitions')->restrictOnDelete();
            $table->string('entity_type', 120);
            $table->uuid('entity_id');
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedInteger('current_step')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'entity_type', 'entity_id'], 'wf_instance_entity_index');
        });
        Schema::create('approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->foreignUuid('approver_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('step');
            $table->string('decision', 30)->default('pending')->index();
            $table->text('comment')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['workflow_instance_id', 'step', 'approver_id'], 'approval_step_user_unique');
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 100);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('status', 30)->default('unread')->index();
            $table->string('dedupe_key', 150)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['recipient_id', 'status'], 'notification_recipient_status_index');
        });
        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->string('activity_type', 50);
            $table->string('status', 30)->default('open')->index();
            $table->timestamp('due_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('related_type', 120)->nullable();
            $table->uuid('related_id')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'related_type', 'related_id'], 'activity_related_index');
        });
        Schema::create('opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('stage', 30)->default('new')->index();
            $table->decimal('expected_value', 20, 4)->default(0);
            $table->unsignedTinyInteger('probability')->default(0);
            $table->date('expected_close_date')->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('code', 80);
            $table->string('name');
            $table->string('status', 30)->default('planned')->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 20, 4)->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUuid('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('status', 30)->default('todo')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->date('due_date')->nullable();
            $table->decimal('actual_cost', 20, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('service_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('customer_id')->constrained('parties')->restrictOnDelete();
            $table->foreignUuid('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number', 80);
            $table->string('subject');
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30)->default('open')->index();
            $table->timestamp('due_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'number']);
        });
        Schema::create('report_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('report_key', 120);
            $table->string('format', 20)->default('xlsx');
            $table->json('filters')->nullable();
            $table->string('status', 30)->default('queued')->index();
            $table->string('file_path')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('import_type', 120);
            $table->string('file_path');
            $table->string('status', 30)->default('queued')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['import_jobs', 'report_jobs', 'service_tickets', 'project_tasks', 'projects', 'opportunities', 'activities', 'notifications', 'approvals', 'workflow_instances', 'workflow_definitions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
