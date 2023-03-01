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
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->decimal('master_agent_rake',18,4)->default(0);
            $table->decimal('agent_rake',18,4)->default(0);
            $table->decimal('sub_agent_rake',18,4)->default(0);
            $table->string('game_id');
            $table->string('sport_key');
            $table->string('sport_title');
            $table->string('home_team')->nullable();
            $table->string('away_team')->nullable();
            $table->decimal('bet_amount',18,4)->default(0);
            $table->enum('type',['h2h','spreads','totals'])->default('h2h');
            $table->enum('outcomes',['win','lose','draw', 'cancelled,','waiting'])->default('waiting');
            $table->json('bet_data');
            $table->timestamp('commence_time');
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
        Schema::dropIfExists('bets');
    }
};
