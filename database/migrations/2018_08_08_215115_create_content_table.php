<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('drama_id');
            $table->string('status');
            $table->string('url');
            $table->string('f360p')->default('https://drive.google.com/open?id=1av4t26HaqPqgSlBAj6D_FSO54RyZR2Tu');
            $table->string('f720p')->default('https://drive.google.com/open?id=1av4t26HaqPqgSlBAj6D_FSO54RyZR2Tu');
            $table->string('mirror1')->nullable();
            $table->string('mirror2')->nullable();
            $table->string('mirror3')->nullable();
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
        Schema::dropIfExists('contents');
    }
}
