<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSentencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sentences', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('content')->comment('句子内容');
            $table->string('tag', 255)->nullable()->default('')->comment('标签内容');
            $table->integer('category_id')->index()->default(0)->comment('分类ID');
            $table->integer('file_id')->nullable()->default(0)->comment('文件ID');
            $table->tinyInteger('is_collected')->nullable()->default(0)->comment('是否是采集: 1.是,0.不是');
            $table->text('source_url')->nullable()->comment('如果是采集,来源url');
            $table->timestamps();

        });

        DB::statement("ALTER TABLE `sentences` comment '句子表'"); // 表注释
        DB::statement("ALTER TABLE `sentences`  ADD UNIQUE INDEX category_content_idx(category_id,content(40))"); // 避免采集时内容重复

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sentences');
    }
}
