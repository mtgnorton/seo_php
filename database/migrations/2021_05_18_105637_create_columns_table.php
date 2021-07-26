<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('columns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('content', 255)->nullable()->default('')->comment('栏目名称');
            $table->string('tag', 20)->index()->default('')->comment('标签内容');
            $table->integer('category_id')->index()->default(0)->comment('分类ID');
            $table->integer('file_id')->nullable()->default(0)->comment('文件ID');
            $table->tinyInteger('is_collected')->nullable()->default(0)->comment('是否是采集: 1.是,0.不是');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `columns` comment '栏目表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('columns');
    }
}
