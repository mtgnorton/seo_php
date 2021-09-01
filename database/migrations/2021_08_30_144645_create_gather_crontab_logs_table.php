<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGatherCrontabLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gather_crontab_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
//            $table->integer('crontab_call_amount')->default(0)->comment('定时任务采集调用次数');
            $table->integer('gather_id')->default(0)->comment('关联采集规则id');

            $table->integer('setting_content_amount')->default(0)->comment('采集设定内容数量');
            $table->integer('setting_url_amount')->default(0)->comment('采集设定链接数量');
            $table->integer('setting_interval_time')->default(0)->comment('采集设定时间间隔');
            $table->integer('setting_timeout_time')->default(0)->comment('采集设定超时时间');

            $table->integer('gather_url_amount')->default(0)->comment('采集链接数量');
            $table->integer('gather_content_amount')->default(0)->comment('采集内容数量');
            $table->text('error_log')->nullable()->comment('错误日志');
            $table->longText('gather_log')->comment('采集日志');
            $table->timestamp('end_time')->nullable()->comment('采集结束时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gather_crontab_logs');
    }
}
