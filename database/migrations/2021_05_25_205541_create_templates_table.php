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
            $table->string('name', 100)->default('')->comment('模板名称');
            $table->string('tag', 100)->default('')->comment('模板标识');
            $table->integer('category_id')->index()->default(0)->comment('分类ID');
            $table->integer('type_id')->index()->default(0)->comment('类型ID');
            $table->integer('group_id')->index()->default(0)->comment('分类ID');
            $table->enum('platform', [
                'pc', 'mobile'
            ])->default('pc')->comment('平台: pc.电脑端, mobile.移动端');
            $table->string('type_tag', 50)->index()->default('')->comment('类型标签');
            $table->longText('module')->nullable()->comment('模块, json格式');
            $table->tinyInteger('is_deleted')->nullable()->default(0)->comment('是否已删除: 0.未删除(正常). 1.已删除');
            $table->timestamps();
            $table->engine = 'MyISAM';
        });
        DB::statement("ALTER TABLE `templates` comment '模板表'"); // 表注释
        DB::statement("ALTER TABLE `templates`  ADD UNIQUE INDEX group_tag_idx(group_id,tag)"); // 避免采集时内容重复
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
