<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('category_rules', function (Blueprint $table) {
        //     $table->bigIncrements('id');
        //     // $table->integer('category_id')->default(0)->comment('分类ID');
        //     // $table->enum('type', ['fixed', 'random'])->comment('规则类型: fixed: 固定的, random: 随机的');
        //     // $table->string('route_tag', 50)->index()->default('')->comment('路由标识');
        //     // $table->string('route_name', 100)->index()->default('')->comment('路由名称');
        //     // $table->text('rule')->nullable()->comment('路由规则');
        //     // $table->timestamps();
        // });
        // DB::statement("ALTER TABLE `category_rules` comment '分类URL规则表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('category_rules');
    }
}
