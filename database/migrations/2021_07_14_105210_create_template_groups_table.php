<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateGroupsTable extends Migration
{
    /**
     * Run the migrations.  
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->default('')->comment('分组名称');
            $table->string('tag', 100)->default('')->comment('分组标识');
            $table->integer('category_id')->default(0)->comment('分类ID');
            $table->tinyInteger('is_deleted')->nullable()->default(0)->comment('是否已删除: 0.未删除(正常). 1.已删除');
            $table->timestamps();
            $table->engine = 'MyISAM';
        });
        DB::statement("ALTER TABLE `template_groups` comment '模板组表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_groups');
    }
}
