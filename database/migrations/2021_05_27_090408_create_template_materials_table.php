<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_materials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('template_id')->index()->default(0)->comment('模板ID');
            $table->integer('module_id')->index()->default(0)->comment('模块ID');
            $table->enum('type', ['css','js','other'])->default('other')->comment('素材类型');
            $table->string('path', 255)->nullable()->default('')->comment('模块路径');
            $table->string('file_name', 100)->nullable()->default('')->comment('文件名称');
            $table->string('full_path', 255)->nullable()->default('')->comment('文件全路径');
            $table->timestamps();
            $table->engine = 'MyISAM';
        });
        DB::statement("ALTER TABLE `template_materials` comment '模板素材表'"); // 表注释s
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_materials');
    }
}
