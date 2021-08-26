<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->unique()->default('')->comment('模板名称');
            $table->string('tag', 50)->unique()->default('')->comment('模板标识');
            $table->timestamps();
            $table->engine = 'MyISAM';
        });
        DB::statement("ALTER TABLE `template_types` comment '模板类型表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_types');
    }
}
