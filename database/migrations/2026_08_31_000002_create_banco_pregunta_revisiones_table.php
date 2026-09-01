<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBancoPreguntaRevisionesTable extends Migration
{
    public function up()
    {
        Schema::create('banco_pregunta_revisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banco_pregunta_lote_id')
                ->constrained('banco_pregunta_lotes')
                ->cascadeOnDelete();
            $table->foreignId('users_id')->constrained('users')->restrictOnDelete();
            $table->string('accion', 20);
            $table->text('comentario')->nullable();
            $table->string('archivo_path', 1024)->nullable();
            $table->string('archivo_nombre')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('banco_pregunta_lote_id', 'bpr_lote_unique');
            $table->index(
                ['users_id', 'accion'],
                'bpr_usuario_accion_idx'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('banco_pregunta_revisiones');
    }
}
