<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMirrorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mirrors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('category_id')->default(0)->comment('所属分类');
            $table->text('targets')->nullable()->comment('目标站,多个,一行一个');
            $table->string('title')->default('')->comment('标题');
            $table->string('keywords')->default('')->comment('标题');
            $table->string('description')->default('')->comment('描述');
            $table->string('conversion')->default('')->comment('简繁 to_complex,中英 to_english,no_conversion 不转换');
            $table->tinyInteger('is_ignore_dtk')->default(0)->comment('是否开启dtk,0否 1是');
            $table->string('user_agent')->default('')->comment('');
            $table->tinyInteger('is_disabled')->default(0)->comment('是否禁用');
            $table->text('replace_contents')->nullable()->comment('替换内容,一行一个');
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
        Schema::dropIfExists('mirrors');
    }
}
