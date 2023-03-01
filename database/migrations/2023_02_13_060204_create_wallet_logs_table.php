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
        Schema::create('wallet_logs', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('amount',18,4)->default(0);
            $table->decimal('balance_before',18,4)->default(0);
            $table->decimal('balance_after',18,4)->default(0);
            $table->string('details')->nullable();
            $table->enum('type',['deposit','withdraw','insert'])->default('withdraw');
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
        Schema::dropIfExists('wallet_logs');
    }
};
