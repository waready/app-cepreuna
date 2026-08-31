<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBancoPreguntasTable extends Migration
{
    public function up()
    {
        Schema::create('banco_preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banco_pregunta_lote_id')
                ->constrained('banco_pregunta_lotes')
                ->cascadeOnDelete();
            $table->string('tipo', 30)->default('opcion_multiple');
            $table->string('tema');
            $table->longText('enunciado');
            $table->string('dificultad', 20)->default('intermedia');
            $table->longText('explicacion')->nullable();
            $table->string('imagen_path', 1024)->nullable();
            $table->unsignedSmallInteger('orden')->default(1);
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['banco_pregunta_lote_id', 'orden'],
                'bp_lote_orden_idx'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('banco_preguntas');
    }
}
