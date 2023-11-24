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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('external_id')->nullable()->default(null);
            $table->string('domain');
            $table->string('full_domain');
            $table->integer('status')->default(0);
            $table->boolean('is_suitable')->nullable()->default(null);
            $table->boolean('is_first_parsed')->default(0);
            $table->text('links')->nullable()->default(null);
            $table->text('logs')->nullable()->default(null);
            $table->string('type')->nullable()->default(null);
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
        Schema::dropIfExists('sites');
    }
};
