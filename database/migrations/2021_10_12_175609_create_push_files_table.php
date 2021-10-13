<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePushFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('push_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255)->default('undefined.txt')->comment('文件名');
            $table->string('path', 255)->default('/undefined.txt')->comment('文件路径');
            $table->text('content')->nullable()->comment('内容');
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
        Schema::dropIfExists('push_files');
    }
}
