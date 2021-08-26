<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsiteTemplateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('website_template', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('website_id')->default(0)->comment('网站ID');
            $table->integer('template_id')->default(0)->comment('模板ID');
            $table->timestamps();
            $table->engine = 'MyISAM';
        });
        DB::statement("ALTER TABLE `website_template` comment '网站模板中间表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('website_template');
    }
}
