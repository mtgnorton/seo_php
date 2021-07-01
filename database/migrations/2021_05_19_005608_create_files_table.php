<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable()->default('')->comment('文件名称');
            $table->string('path')->nullable()->default('')->comment('文件路径');
            $table->string('ext')->nullable()->default('')->comment('文件扩展名');
            $table->bigInteger('size')->nullable()->default(0)->comment('文件大小(字节)');
            $table->integer('rows')->nullable()->default(0)->comment('行数');
            $table->enum('type', [
                'article', 'column', 'diy',
                'image', 'video', 'sentence',
                'title', 'website_name', 'keyword', ''
            ])->nullable()->default('diy')->comment('内容库标识: article: 文章库, column: 栏目库, diy: 自定义库, image: 图片库, video: 视频库, sentence: 句子库, title: 标题库, website_name: 网站名称库, keyword: 关键词');
            $table->integer('category_id')->default(0)->comment('分类ID');
            $table->integer('tag_id')->default(0)->comment('标签ID');
            $table->tinyInteger('is_collected')->nullable()->default(0)->comment('是否是采集: 1.是,0.不是');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `files` comment '文件表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
