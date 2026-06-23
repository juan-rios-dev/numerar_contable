<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(0)->index();
            $table->foreignId('accounting_period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->string('entry_number', 20);
            $table->string('entry_type', 10);
            $table->unsignedBigInteger('entry_sequence_id')->nullable();
            $table->date('date');
            $table->text('description')->nullable();
            $table->enum('status', ['POSTED', 'VOIDED'])->default('POSTED');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'entry_number']);
            $table->index(['tenant_id', 'entry_type', 'date']);
            $table->index(['tenant_id', 'status', 'date']);
            $table->index('accounting_period_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_entries');
    }
};
