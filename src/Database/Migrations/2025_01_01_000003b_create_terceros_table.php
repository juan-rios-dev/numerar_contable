<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terceros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(0)->index();

            // Identificación
            $table->enum('tipo_persona', ['NATURAL', 'JURIDICA'])->default('JURIDICA');
            $table->enum('tipo_documento', ['NIT', 'CC', 'CE', 'PA', 'TE', 'RC', 'TI', 'PEP', 'PPT'])->default('NIT');
            $table->string('numero_documento', 20);
            $table->char('digito_verificacion', 1)->nullable();  // Solo NIT

            // Razón social / Nombre
            $table->string('razon_social', 200)->nullable();     // Persona jurídica
            $table->string('primer_nombre', 100)->nullable();    // Persona natural
            $table->string('segundo_nombre', 100)->nullable();
            $table->string('primer_apellido', 100)->nullable();
            $table->string('segundo_apellido', 100)->nullable();

            // Roles (un tercero puede ser cliente Y proveedor)
            $table->boolean('es_cliente')->default(false);
            $table->boolean('es_proveedor')->default(false);
            $table->boolean('es_empleado')->default(false);
            $table->boolean('es_otro')->default(false);

            // Fiscal
            $table->enum('responsabilidad_fiscal', [
                'RESPONSABLE_IVA',
                'NO_RESPONSABLE',
                'GRAN_CONTRIBUYENTE',
                'REGIMEN_SIMPLE',
                'NO_APLICA',
            ])->default('NO_APLICA');

            // Ubicación
            $table->string('municipio', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('direccion', 250)->nullable();

            // Contacto
            $table->string('email', 150)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('celular', 20)->nullable();

            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'tipo_documento', 'numero_documento']);
            $table->index(['tenant_id', 'es_cliente']);
            $table->index(['tenant_id', 'es_proveedor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terceros');
    }
};
