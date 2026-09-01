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
            $table->unsignedTinyInteger('semana');
            $table->string('nivel', 20);
            $table->unsignedInteger('version')->default(1);
            $table->string('archivo_path', 1024);
            $table->string('archivo_nombre');
            $table->string('estado', 20)->default('en_revision');
            $table->timestamps();

            $table->unique(
                ['periodos_id', 'cursos_id', 'docentes_id', 'semana', 'version'],
                'bpl_contexto_version_unique'
            );
            $table->index(
                ['docentes_id', 'periodos_id', 'estado'],
                'bpl_docente_periodo_estado_idx'
            );
            $table->index(
                ['periodos_id', 'estado', 'created_at'],
                'bpl_periodo_estado_fecha_idx'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('banco_pregunta_lotes');
    }
}
