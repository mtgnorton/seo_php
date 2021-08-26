<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('url', 255)->nullable()->default('')->comment('视频链接内容');
            $table->string('tag', 20)->nullable()->default('')->comment('标签内容');
            $table->integer('category_id')->index()->default(0)->comment('分类ID');
            $table->integer('file_id')->nullable()->default(0)->comment('文件ID');
            $table->tinyInteger('is_collected')->nullable()->default(0)->comment('是否是采集: 1.是,0.不是');
            $table->timestamps();
            $table->engine = 'MyISAM';
        });
        DB::statement("ALTER TABLE `videos` comment '视频链接表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('videos');
    }
}
