<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $now = Carbon::now();

        // DB::table('tags')->insert([
        //     [
        //         'name' => '文章',
        //         'identify' => 'article',
        //         'tag' => '文章',
        //         'content_identify' => 'article',
        //         'type' => 'system',
        //         'created_at' => $now,
        //         'updated_at' => $now,
        //     ],
        //     [
        //         'name' => '栏目',
        //         'identify' => 'column',
        //         'tag' => '栏目',
        //         'content_identify' => 'column',
        //         'type' => 'system',
        //         'created_at' => $now,
        //         'updated_at' => $now,
        //     ],
        //     [
        //         'name' => '标题',
        //         'identify' => 'title',
        //         'tag' => '标题',
        //         'content_identify' => 'title',
        //         'type' => 'system',
        //         'created_at' => $now,
        //         'updated_at' => $now,
        //     ],
        //     [
        //         'name' => '网站名称',
        //         'identify' => 'website_name',
        //         'tag' => '网站名称',
        //         'content_identify' => 'website_name',
        //         'type' => 'system',
        //         'created_at' => $now,
        //         'updated_at' => $now,
        //     ],
        //     [
        //         'name' => '句子',
        //         'identify' => 'sentence',
        //         'tag' => '句子',
        //         'content_identify' => 'sentence',
        //         'type' => 'system',
        //         'created_at' => $now,
        //         'updated_at' => $now,
        //     ],
        //     [
        //         'name' => '图片',
        //         'identify' => 'image',
        //         'tag' => '图片',
        //         'content_identify' => 'image',
        //         'type' => 'system',
        //         'created_at' => $now,
        //         'updated_at' => $now,
        //     ],
        //     [
        //         'name' => '视频',
        //         'identify' => 'video',
        //         'tag' => '视频',
        //         'content_identify' => 'video',
        //         'type' => 'system',
        //         'created_at' => $now,
        //         'updated_at' => $now,
        //     ],
        //     [
        //         'name' => '关键词',
        //         'identify' => 'keyword',
        //         'tag' => '关键词',
        //         'content_identify' => 'keyword',
        //         'type' => 'system',
        //         'created_at' => $now,
        //         'updated_at' => $now,
        //     ],
        // ]);
    }
}
