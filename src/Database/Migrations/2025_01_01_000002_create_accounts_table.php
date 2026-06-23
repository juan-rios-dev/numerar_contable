<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(0)->index();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('class_id')->constrained('account_classes')->restrictOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('nature', ['DEBIT', 'CREDIT']);
            $table->enum('account_type', ['MAYOR', 'MOVIMIENTO'])->default('MOVIMIENTO');
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'active', 'account_type']);
            $table->index('parent_id');
            $table->index('class_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
