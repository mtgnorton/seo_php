<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateModulePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_module_pages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('template_id')->index()->default(0)->comment('模板ID');
            $table->integer('module_id')->index()->default(0)->comment('模块ID');
            $table->string('file_name', 100)->nullable()->default('')->comment('文件名称');
            $table->string('full_path', 255)->nullable()->default('')->comment('文件全路径');
            $table->timestamps();
            $table->engine = 'MyISAM';
        });
        DB::statement("ALTER TABLE `template_module_pages` comment '模板模块页面表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_module_pages');
    }
}
