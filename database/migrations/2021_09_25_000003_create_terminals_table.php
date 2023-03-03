<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTerminalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('terminals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('authorization_key')->unique();
            $table->ipAddress('ip_address')->unique();
            $table->string('remote_id')->nullable();
            $table->string('remote_password')->nullable();
            $table
                ->boolean('active')
                ->default(true)
                ->nullable();
            $table->string('responsible_name');
            $table->string('contact_primary');
            $table->string('contact_secondary')->nullable();
            $table->string('street');
            $table->integer('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('district');
            $table->string('city');
            $table->string('state');
            $table->string('zipcode');
            $table->string('paygo_id')->unique();
            $table->string('paygo_login');
            $table->string('paygo_password');
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
        Schema::dropIfExists('terminals');
    }
}
