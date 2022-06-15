<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGathersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gathers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->unique()->default('')->comment('采集名称');
            $table->string('category_id')->default(0)->comment('分类id');
            $table->string('tag')->default('')->comment('标签名称');
            $table->tinyInteger('is_filter_url')->default(0)->comment('是否过滤网址');
            $table->char('type', 20)->default('split')->comment('采集类型:sentence 分隔成句子,title 采集标题,full 文章,image 图片');
            $table->string('delimiter', 20)->default('')->comment('分隔字符,多个以|隔开');
            $table->char('storage_type', 20)->default('')->comment('存储类型');
            $table->text('user_agent');
            $table->text('header')->comment('header头');
            $table->string('keywords')->nullable()->comment('关键词');
            $table->string('agent')->default('')->comment('代理');
            $table->tinyInteger('is_auto')->default(0)->comment('是否自动采集: 0否,1是');
            $table->text('begin_url')->comment('开始采集的url');
            $table->text('regular_url')->nullable()->comment('匹配网址,一行一条');
            $table->text('test_url')->nullable()->comment('测试地址');
            $table->text('regular_content')->nullable()->comment('匹配内容,一行一条');
            $table->text('regular_title')->nullable()->comment('当为匹配文章时，匹配标题');
            $table->tinyInteger('is_open_fake_origin')->default(0)->comment('是否开启伪原创');
            $table->text('regular_image')->nullable()->comment('当为匹配图片时,匹配地址');
            $table->integer('filter_length_limit')->nullable()->default(0)->comment('内容最小长度,小于该值过滤');
            $table->text('filter_regular')->nullable()->comment('正则过滤,一行一条');
            $table->text('filter_content')->nullable()->comment('内容过滤');
            $table->text('no_regular_url')->nullable()->comment('忽略的网址正则,一行一条');

            /*定时任务字段*/


            $table->char('crontab_type', 15)->default('')->comment('采集定时任务类型');
            $table->integer('crontab_hour')->default(0)->comment('采集定时任务小时时间');
            $table->integer('crontab_minute')->default(0)->comment('采集定时任务分钟时间');

            $table->integer('crontab_setting_content_amount')->default(0)->comment('采集设定内容数量');
            $table->integer('crontab_setting_url_amount')->default(0)->comment('采集设定链接数量');
            $table->integer('crontab_setting_interval_time')->default(0)->comment('采集设定时间间隔');
            $table->integer('crontab_setting_timeout_time')->default(0)->comment('采集设定超时时间');

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
        Schema::dropIfExists('gathers');
    }
}
