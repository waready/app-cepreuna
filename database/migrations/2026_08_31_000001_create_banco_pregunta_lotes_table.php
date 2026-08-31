<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBancoPreguntaLotesTable extends Migration
{
    public function up()
    {
        Schema::create('banco_pregunta_lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodos_id')->constrained('periodos')->restrictOnDelete();
            $table->foreignId('cursos_id')->constrained('cursos')->restrictOnDelete();
            $table->foreignId('docentes_id')->constrained('docentes')->restrictOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('estado', 20)->default('borrador');
            $table->text('observacion')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('revisado_at')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['periodos_id', 'cursos_id', 'docentes_id', 'version'],
                'bpl_contexto_version_unique'
            );
            $table->index(
                ['docentes_id', 'periodos_id', 'estado'],
                'bpl_docente_periodo_estado_idx'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('banco_pregunta_lotes');
    }
}
