<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->unique()->default('')->comment('模板名称');
            $table->string('tag', 100)->unique()->default('')->comment('模板标识');
            $table->integer('category_id')->index()->default(0)->comment('分类ID');
            $table->integer('type_id')->index()->default(0)->comment('类型ID');
            $table->string('type_tag', 50)->index()->default('')->comment('类型标签');
            $table->longText('module')->nullable()->comment('模块, json格式');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `templates` comment '模板表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('templates');
    }
}
