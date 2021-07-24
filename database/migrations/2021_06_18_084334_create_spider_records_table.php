<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpiderRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spider_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('type', [
                'baidu', 'google', 'qihoo',
                'sougou', 'shenma', 'toutiao',
                'other'
            ])->default('other')->comment('蜘蛛类型: baidu: 百度, google: 谷歌, qihoo: 360, sougou: 搜狗, shenma: 神马, toutiao: 今日头条, other: 其他');
            $table->string('user_agent', 255)->nullable()->default('')->comment('头部标识');
            $table->string('ip', 20)->nullable()->default('')->comment('ip地址');
            $table->string('host')->default('')->comment('访问域名');
            $table->string('url')->default('')->comment('访问链接');
            $table->enum('url_type', [
                'index', 'list',
                'detail', 'sitemap',
                ''
            ])->defautl('index')->comment('链接类型: index: 首页, list: 列表页, detail: 详情页, sitemap, 站点地图页');
            $table->integer('category_id')->default(0)->comment('分类ID');
            $table->integer('group_id')->default(0)->comment('分组ID');
            $table->integer('template_id')->default(0)->comment('模板ID');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `spider_records` comment '蜘蛛记录表'"); // 表注释
        DB::statement("ALTER TABLE `spider_records`  ADD INDEX spider_records_created_at(created_at)"); // 避免采集时内容重复
        DB::statement("ALTER TABLE `spider_records`  ADD INDEX spider_records_type(type)"); // 避免采集时内容重复
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spider_records');
    }
}
