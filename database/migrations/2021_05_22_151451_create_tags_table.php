<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255)->nullable()->default('')->comment('标签名称');
            $table->string('identify', 50)->default('')->comment('标签标识');
            $table->string('tag', 90)->unique()->default('')->comment('标签内容');
            $table->integer('category_id')->nullable()->default(0)->comment('分类ID');
            // $table->enum('content_identify', [
            //     'article', 'column', 'diy',
            //     'image', 'video', 'sentence',
            //     'title', 'website_name', 'keyword', ''
            // ])->nullable()->default('')->comment('article: 文章, column: 栏目, diy: 自定义, image: 图片, video: 视频, sentence: 句子, title: 标题, website_name: 网站名称, keyword: 关键词');
            // $table->enum('type', ['system', 'diy'])->default('diy')->comment('类型: system: 系统标签, diy: 自定义标签');
            $table->timestamps();
            $table->engine = 'MyISAM';
        });
        DB::statement("ALTER TABLE `tags` comment '标签表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tags');
    }
}
