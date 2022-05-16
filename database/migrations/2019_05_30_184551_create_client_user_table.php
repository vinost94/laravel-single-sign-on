<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('laravel-sso.clientUserTable', 'client_user'), function (Blueprint $table) {
            $table->id();

            $table->integer('user_id')->foreign('user_id')->references('id')->on('users');
            $table->integer('client_id')->foreign('client_id')->references('id')->on('clients');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('laravel-sso.clientUserTable', 'client_user'));
    }
}
