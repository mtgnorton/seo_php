<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('category_id')->nullable()->default(0)->comment('网站分类ID');
            $table->integer('group_id')->nullable()->default(0)->comment('分组ID');
            $table->string('name', 255)->nullable()->default('')->comment('内容分类名称');
            $table->string('tag', 100)->nullable()->default('')->comment('内容分类标签: 拼音或英文');
            $table->integer('parent_id')->default(0)->comment('父类ID');
            $table->integer('sort')->default(0)->common('排序: 由小到大升序排列');
            $table->enum('type', [
                'article', 'column', 'website_name',
                'image', 'video', 'sentence',
                'title', 'diy', 'keyword', ''
            ])->default('')->comment('article: 文章, column: 栏目, image: 图片, video: 视频, sentence: 句子, title: 标题, website_name: 网站名称, diy: 自定义, keyword:关键词');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `content_categories` comment '内容分类表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_categories');
    }
}
