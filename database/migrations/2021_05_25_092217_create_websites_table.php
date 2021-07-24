<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255)->nullable()->default('')->comment('网站名称');
            $table->string('url', 255)->nullable()->default('')->comment('网站链接');
            $table->integer('category_id')->index()->default(0)->comment('分组ID');
            $table->integer('group_id')->index()->default(0)->comment('分类ID');
            $table->integer('template_id')->index()->default(0)->comment('模板');
            $table->tinyInteger('is_enabled')->index()->default(0)->comment('是否启用');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `websites` comment '网站表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('websites');
    }
}
