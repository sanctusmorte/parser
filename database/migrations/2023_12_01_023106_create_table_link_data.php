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
        Schema::create('link_data', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('parent_link_id')->nullable()->default(null);
            $table->bigInteger('parent_site_id')->nullable()->default(null);
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
        Schema::dropIfExists('link_data');
    }
};
