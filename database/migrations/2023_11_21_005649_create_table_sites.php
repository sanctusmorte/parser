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
            $table->boolean('is_main_page')->nullable()->default(null);
            $table->string('full_domain');
            $table->integer('status')->default(0);
            $table->json('links')->nullable()->default(null);
            $table->string('type')->nullable()->default(null);
            $table->string('meta_title')->nullable()->default(null);
            $table->text('meta_description')->nullable()->default(null);
            $table->text('meta_keywords')->nullable()->default(null);
            $table->json('h_tags')->nullable()->default(null);
            $table->json('img_alts')->nullable()->default(null);
            $table->json('href_titles')->nullable()->default(null);
            $table->boolean('is_video_content')->nullable()->default(null);
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
