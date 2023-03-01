<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id('id');
			$table->integer('user_id')->nullable();
			$table->string('username');
			$table->string('entry', 1000);
			$table->string('comment');
			$table->enum('family', array('void','insert','update','delete','login-success','login-failure','logout','others'))->default('others');
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
        Schema::dropIfExists('activity_logs');
    }
};
