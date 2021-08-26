<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('content')->nullable()->default('')->comment('内容');
            $table->string('tag', 20)->index()->default('')->comment('标签内容');
            $table->integer('file_id')->nullable()->default(0)->comment('文件ID');
            $table->integer('category_id')->nullable()->default(0)->comment('分类ID');
            $table->tinyInteger('is_collected')->nullable()->default(0)->comment('是否是采集: 1.是,0.不是');
            $table->timestamps();
            $table->engine = 'MyISAM';
        });
        DB::statement("ALTER TABLE `diys` comment '自定义表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('diys');
    }
}
