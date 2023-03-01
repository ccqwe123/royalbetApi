<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('id');
            $table->string('email', 320)->unique();
            $table->string('password');
            $table->string('verify_code', 6)->nullable();
            $table->unsignedInteger('reset_timer')->default(130);
            $table->tinyInteger('account_is_verified')->default(0);
            $table->string('username', 15)->unique()->nullable();
            $table->string('first_name', 50)->nullable();
            $table->string('middle_name', 50)->nullable();
            $table->string('last_name', 50)->nullable();
            $table->date('birthdate')->nullable();
            $table->tinyInteger('details_is_provided')->default(0);
            $table->string('address', 256)->nullable();
            $table->string('municipality_city', 50)->nullable();
            $table->string('province_region', 50)->nullable();
            $table->string('phone', 11)->unique()->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('image')->nullable();
            $table->tinyInteger('contact_is_provided')->default(0);
            $table->string('reset_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->decimal('points',18,4)->default(0);
            $table->enum('type',['admin','agent','member'])->default('member');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
