<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTitlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('titles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('content', 255)->default('')->comment('标题');
            $table->string('tag', 20)->index()->default('')->comment('标签内容');
            $table->integer('category_id')->index()->default(0)->comment('分类ID');
            $table->integer('file_id')->nullable()->default(0)->comment('文件ID');
            $table->tinyInteger('is_collected')->nullable()->default(0)->comment('是否是采集: 1.是,0.不是');
            $table->text('source_url')->nullable()->comment('如果是采集,来源url');
            $table->timestamps();
            $table->engine = 'MyISAM';
        });
        DB::statement("ALTER TABLE `titles` comment '标题表'"); // 表注释
        DB::statement("ALTER TABLE `titles`  ADD UNIQUE INDEX category_content_idx(category_id,content(40))"); // 避免采集时内容重复

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('titles');
    }
}
