<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('person_thing', function (Blueprint $table) {
            $table->unsignedBigInteger('person_id');
            $table->unsignedBigInteger('thing_id');

            $table->unique(['person_id', 'thing_id']);

            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');
            $table->foreign('thing_id')->references('id')->on('things')->onDelete('cascade');

            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('person_thing');
    }
};