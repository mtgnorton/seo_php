<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('module', 100)->default('site')->comment('模块');
            $table->integer('category_id')->default(0)->comment('分类ID');
            $table->integer('group_id')->default(0)->comment('分组ID');
            $table->integer('template_id')->default(0)->comment('模板ID');
            $table->string('key')->default('')->comment('键值');
            $table->text('value')->nullable()->comment('键对应的值');
            $table->tinyInteger('is_json')->default(0)->comment('是否是json');
            $table->string('description')->nullable()->default('')->comment('描述');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `configs` comment '配置表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('configs');
    }
}
