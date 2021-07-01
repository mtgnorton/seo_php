<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 255)->default('')->comment('标题');
            $table->string('tag', 255)->nullable()->default('')->comment('标签内容');
            $table->string('image', 255)->nullable()->default('')->comment('缩略图');
            $table->longText('content')->comment('内容');
            $table->integer('category_id')->index()->default(0)->comment('分类ID');
            $table->integer('file_id')->nullable()->default(0)->comment('文件ID');
            $table->tinyInteger('is_collected')->nullable()->default(0)->comment('是否是采集: 1.是,0.不是');
            $table->text('source_url')->nullable()->comment('如果是采集,来源url');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `articles` comment '文章表'"); // 表注释
        DB::statement("ALTER TABLE `articles`  ADD UNIQUE INDEX category_content_idx(category_id,content(40))"); // 避免采集时内容重复

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articles');
    }
}
