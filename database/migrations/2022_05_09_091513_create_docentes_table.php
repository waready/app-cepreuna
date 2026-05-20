<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocentesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')::create('docentes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombres');
            $table->string('paterno');
            $table->string('materno');
            $table->string('dni');
            $table->enum('condicion', ['1', '2'])->comment('1:particular  2:Unap')->default('1');
            $table->string('direccion');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('celular');
            $table->foreignId('periodo_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('docentes');
    }
}
