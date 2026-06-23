<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_entry_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(0)->index();
            $table->foreignId('entry_type_id')
                ->constrained('accounting_entry_types')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('prefix', 20);
            $table->unsignedInteger('initial_number')->default(1);
            $table->unsignedSmallInteger('priority')->default(1);
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // La columna entry_sequence_id ya existe en accounting_entries (000005).
        // Aquí se agrega la FK ahora que la tabla de secuencias existe.
        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->foreign('entry_sequence_id')
                ->references('id')
                ->on('accounting_entry_sequences')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->dropForeign(['entry_sequence_id']);
        });

        Schema::dropIfExists('accounting_entry_sequences');
    }
};
