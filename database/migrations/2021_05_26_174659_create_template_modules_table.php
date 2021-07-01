<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_modules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('template_id')->index()->default(0)->comment('模板ID');
            $table->string('column_name', 100)->nullable()->default('')->comment('栏目名称');
            $table->string('column_tag', 50)->nullable()->default('')->comment('栏目标识');
            $table->string('route_name', 100)->nullable()->default('')->comment('路由名称');
            $table->string('route_tag', 50)->nullable()->default('')->comment('路由标识');
            $table->string('path', 255)->nullable()->default('')->comment('模块路径');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `template_modules` comment '模板模块表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_modules');
    }
}
