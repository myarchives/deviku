<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLinkBrokenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brokenlinks', function (Blueprint $table) {
            //
            $table->increments('id');
            $table->integer('contents_id');
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
        Schema::dropIfExists('brokenlinks', function (Blueprint $table) {
            //
            Schema::dropIfExists('brokenlinks');
        });
    }
}
