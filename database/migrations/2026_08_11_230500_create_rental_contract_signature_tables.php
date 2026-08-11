<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_contract_signature_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignUuid('rental_contract_id')->constrained('rental_contracts')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUuid('requested_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->string('signer_name', 190);
            $table->string('signer_email', 190)->nullable();
            $table->string('signer_document', 30)->nullable();
            $table->string('signer_phone', 30)->nullable();
            $table->char('document_hash', 64);
            $table->char('signed_document_hash', 64)->nullable();
            $table->string('signature_path', 500)->nullable();
            $table->timestampTz('viewed_at')->nullable();
            $table->timestampTz('signed_at')->nullable();
            $table->timestampTz('expires_at');
            $table->string('signed_ip', 45)->nullable();
            $table->text('signed_user_agent')->nullable();
            $table->text('acceptance_text')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['rental_contract_id', 'status', 'expires_at'], 'rental_contract_signature_status_idx');
        });

        Schema::create('rental_contract_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignUuid('rental_contract_id')->constrained('rental_contracts')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUuid('signature_request_id')->nullable()->constrained('rental_contract_signature_requests')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('type', 60);
            $table->string('channel', 30)->nullable();
            $table->string('recipient', 190)->nullable();
            $table->timestampTz('occurred_at');
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['rental_contract_id', 'occurred_at'], 'rental_contract_events_contract_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_contract_events');
        Schema::dropIfExists('rental_contract_signature_requests');
    }
};
