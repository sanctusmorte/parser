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
        Schema::create('pornstars', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('external_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('external_full_name');
            $table->integer('status')->default(0);
            $table->boolean('is_changeable');
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
        Schema::dropIfExists('pornstars');
    }
};
