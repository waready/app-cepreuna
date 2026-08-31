<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBancoPreguntaAlternativasTable extends Migration
{
    public function up()
    {
        Schema::create('banco_pregunta_alternativas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banco_pregunta_id')
                ->constrained('banco_preguntas')
                ->cascadeOnDelete();
            $table->char('clave', 1);
            $table->longText('texto');
            $table->boolean('es_correcta')->default(false);
            $table->unsignedTinyInteger('orden');
            $table->timestamps();

            $table->unique(
                ['banco_pregunta_id', 'clave'],
                'bpa_pregunta_clave_unique'
            );
            $table->index(
                ['banco_pregunta_id', 'es_correcta'],
                'bpa_pregunta_correcta_idx'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('banco_pregunta_alternativas');
    }
}
