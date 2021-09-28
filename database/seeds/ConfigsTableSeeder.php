<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();

        DB::table('configs')->insert([
            [
                'module'     => 'update',
                'key'        => 'version',
                'value'      => config('seo.app_version'),
                'is_json'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'module'     => 'spider',
                'key'        => 'spider_strong_attraction',
                'value'      => '{"is_open":"off","type":[""],"urls":""}',
                'is_json'    => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module'     => 'spider',
                'key'        => 'no_spider',
                'value'      => '{"is_open":"off","type":"black_list","black_list":[""],"white_list":[""]}',
                'is_json'    => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module'     => 'push',
                'key'        => 'auto_push',
                'value'      => '{"is_open":"off","baidu_normal":"","baidu_quick":"","interval":""}',
                'is_json'    => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module'     => 'push',
                'key'        => 'baidu_normal',
                'value'      => '',
                'is_json'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module'     => 'push',
                'key'        => 'baidu_quick',
                'value'      => '',
                'is_json'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module'     => 'push',
                'key'        => 'push_js',
                'value'      => '',
                'is_json'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module'     => 'qihoopush',
                'key'        => 'is_open',
                'value'      => 'off',
                'is_json'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module'     => 'qihoopush',
                'key'        => 'url_format',
                'value'      => '',
                'is_json'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module'     => 'qihoopush',
                'key'        => 'url_format',
                'value'      => '',
                'is_json'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'module'     => 'fakeorigin',
                'key'        => 'article_image_type',
                'value'      => 'article_image_remote',
                'is_json'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
