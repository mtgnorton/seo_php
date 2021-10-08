<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpiderAttractionRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spider_attraction_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('type', [
                'baidu', 'google', 'qihoo',
                'sougou', 'shenma', 'toutiao',
                'other', 'selfTest'
            ])->default('other')->comment('蜘蛛类型: baidu: 百度, google: 谷歌, qihoo: 360, sougou: 搜狗, shenma: 神马, toutiao: 今日头条, other: 其他, selfTest: 自测');
            $table->integer('user_agent_id')->default(0)->comment('头部标识ID');
            $table->string('ip', 20)->nullable()->default('')->comment('ip地址');
            $table->string('from_host')->default('')->comment('访问域名');
            $table->string('from_url')->default('')->comment('访问链接');
            $table->string('to_url')->default('')->comment('目标链接');
            $table->enum('url_type', [
                'index', 'list',
                'detail', 'sitemap',
                ''
            ])->defautl('index')->comment('链接类型: index: 首页, list: 列表页, detail: 详情页, sitemap, 站点地图页');
            $table->integer('category_id')->default(0)->comment('分类ID');
            $table->integer('group_id')->default(0)->comment('分组ID');
            $table->integer('template_id')->default(0)->comment('模板ID');
            $table->timestamps();
            $table->engine = 'MyISAM';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spider_attraction_records');
    }
}
