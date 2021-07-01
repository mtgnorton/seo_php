<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TemplateTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();

        DB::table('template_types')->insert([
            [
                'name' => '通用模板',
                'tag' => 'common',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '百度模板',
                'tag' => 'baidu',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '谷歌模板',
                'tag' => 'google',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '搜狗模板',
                'tag' => 'sougou',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '360模板',
                'tag' => 'qihoo',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '神马模板',
                'tag' => 'shenma',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '今日头条模板',
                'tag' => 'toutiao',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
