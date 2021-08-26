<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpiderUserAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spider_user_agents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_agent')->comment('蜘蛛头部标识');
            $table->engine = 'MyISAM';
        });
        DB::statement("ALTER TABLE `spider_user_agents` comment '蜘蛛头表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spider_user_agents');
    }
}
