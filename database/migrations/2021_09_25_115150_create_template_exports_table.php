<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateExportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_exports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('template_id')->default(0)->coment('模板ID');
            $table->string('name', 100)->default('')->comment('模板名称');
            $table->string('tag', 100)->default('')->comment('模板标识');
            $table->string('path', 255)->default('')->comment('文件链接');
            $table->string('message', 255)->default('')->comment('导出信息');
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
        Schema::dropIfExists('template_exports');
    }
}
