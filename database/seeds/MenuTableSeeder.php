<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now()->toDateTimeString();


        DB::table('admin_menu')->insertOrIgnore([
            // [
            //     'id' => 8,
            //     'parent_id' => 0,
            //     'order' => 8,
            //     'title' => '内容库',
            //     'icon' => 'fa-bars',
            //     'uri' => '',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 9,
            //     'parent_id' => 8,
            //     'order' => 9,
            //     'title' => '文章库',
            //     'icon' => 'fa-book',
            //     'uri' => 'content-categories?type=article',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 10,
            //     'parent_id' => 8,
            //     'order' => 10,
            //     'title' => '标题库',
            //     'icon' => 'fa-pencil',
            //     'uri' => 'content-categories?type=title',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 11,
            //     'parent_id' => 8,
            //     'order' => 11,
            //     'title' => '网站名称库',
            //     'icon' => 'fa-external-link',
            //     'uri' => 'content-categories?type=website_name',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 12,
            //     'parent_id' => 8,
            //     'order' => 12,
            //     'title' => '栏目库',
            //     'icon' => 'fa-columns',
            //     'uri' => 'content-categories?type=column',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 13,
            //     'parent_id' => 8,
            //     'order' => 13,
            //     'title' => '句子库',
            //     'icon' => 'fa-pencil-square',
            //     'uri' => 'content-categories?type=sentence',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 14,
            //     'parent_id' => 8,
            //     'order' => 14,
            //     'title' => '图片库',
            //     'icon' => 'fa-image',
            //     'uri' => 'content-categories?type=image',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 15,
            //     'parent_id' => 8,
            //     'order' => 15,
            //     'title' => '视频库',
            //     'icon' => 'fa-video-camera',
            //     'uri' => 'content-categories?type=video',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 16,
            //     'parent_id' => 8,
            //     'order' => 16,
            //     'title' => '文件库',
            //     'icon' => 'fa-file-o',
            //     'uri' => 'files',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 17,
            //     'parent_id' => 8,
            //     'order' => 17,
            //     'title' => '自定义库',
            //     'icon' => 'fa-circle-thin',
            //     'uri' => 'diy-categories',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 18,
            //     'parent_id' => 8,
            //     'order' => 18,
            //     'title' => '标签',
            //     'icon' => 'fa-tag',
            //     'uri' => 'tags',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 19,
            //     'parent_id' => 8,
            //     'order' => 19,
            //     'title' => '内容分类管理',
            //     'icon' => 'fa-bars',
            //     'uri' => 'content-categories',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            [
                'id'            => 20,
                'parent_id'     => 0,
                'order'         => 20,
                'title'         => '站点管理',
                'icon'          => '/asset/imgs/icon/7.png',
                'icon_selected' => '/asset/imgs/default_icon/a12.png',
                'uri'           => '',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            // [
            //     'id' => 21,
            //     'parent_id' => 20,
            //     'order' => 21,
            //     'title' => '模板类型管理',
            //     'icon' => 'fa-map-o',
            //     'uri' => 'template-types',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 22,
            //     'parent_id' => 20,
            //     'order' => 22,
            //     'title' => '模板管理',
            //     'icon' => 'fa-file',
            //     'uri' => 'templates',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 23,
            //     'parent_id' => 20,
            //     'order' => 23,
            //     'title' => '网站分类管理',
            //     'icon' => 'fa-align-left',
            //     'uri' => 'categories',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 24,
            //     'parent_id' => 8,
            //     'order' => 24,
            //     'title' => '关键词库',
            //     'icon' => 'fa-file-word-o',
            //     'uri' => 'content-categories?type=keyword',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            // [
            //     'id' => 25,
            //     'parent_id' => 20,
            //     'order' => 25,
            //     'title' => '网站管理',
            //     'icon' => 'fa-link',
            //     'uri' => 'websites',
            //     'created_at' => $now,
            //     'updated_at' => $now,
            // ],
            [
                'id'            => 26,
                'parent_id'     => 20,
                'order'         => 26,
                'title'         => '推送管理',
                'icon'          => '/asset/imgs/icon/8.png',
                'icon_selected' => '/asset/imgs/default_icon/a16.png',
                'uri'           => 'settings',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 30,
                'parent_id'     => 0,
                'order'         => 30,
                'title'         => '采集',
                'icon'          => '/asset/imgs/icon/5.png',
                'icon_selected' => '/asset/imgs/default_icon/a11.png',
                'uri'           => '',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 100,
                'parent_id'     => 30,
                'order'         => 30,
                'title'         => '伪原创配置',
                'icon'          => '/asset/imgs/icon/5.png',
                'icon_selected' => '/asset/imgs/default_icon/a11.png',
                'uri'           => 'gather-fake-origin',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 31,
                'parent_id'     => 30,
                'order'         => 31,
                'title'         => '规则采集',
                'icon'          => '/asset/imgs/icon/5.png',
                'icon_selected' => '/asset/imgs/default_icon/a11.png',
                'uri'           => 'gathers',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 101,
                'parent_id'     => 30,
                'order'         => 35,
                'title'         => '定时采集日志',
                'icon'          => '/asset/imgs/icon/5.png',
                'icon_selected' => '/asset/imgs/default_icon/a11.png',
                'uri'           => 'gather-crontab-logs',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 32,
                'parent_id'     => 0,
                'order'         => 32,
                'title'         => '蜘蛛管理',
                'icon'          => '/asset/imgs/icon/9.png',
                'icon_selected' => '/asset/imgs/default_icon/a13.png',
                'uri'           => '',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 33,
                'parent_id'     => 32,
                'order'         => 33,
                'title'         => '蜘蛛访问日志',
                'icon'          => '/asset/imgs/icon/9.png',
                'icon_selected' => '/asset/imgs/default_icon/a13.png',
                'uri'           => 'spider-records',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 34,
                'parent_id'     => 0,
                'order'         => 34,
                'title'         => '镜像',
                'icon'          => '/asset/imgs/icon/6.png',
                'icon_selected' => '/asset/imgs/default_icon/a15.png',
                'uri'           => 'mirrors',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 35,
                'parent_id'     => 20,
                'order'         => 26,
                'title'         => '授权管理',
                'icon'          => '/asset/imgs/icon/11.png',
                'icon_selected' => '/asset/imgs/icon/11.png',
                'uri'           => 'install',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 36,
                'parent_id'     => 0,
                'order'         => 19,
                'title'         => '站点分类',
                'icon'          => '/asset/imgs/icon/2.png',
                'icon_selected' => '/asset/imgs/default_icon/a10.png',
                'uri'           => '',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 37,
                'parent_id'     => 36,
                'order'         => -1,
                'title'         => '新增分类',
                'icon'          => '/asset/imgs/icon/3.png',
                'icon_selected' => '/asset/imgs/icon/3.png',
                'uri'           => 'categories/create',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
//            [
//                'id'         => 38,
//                'parent_id'  => 0,
//                'order'      => 36,
//                'title'      => '域名解析',
//                'icon'       => '/asset/imgs/icon/10.png',
            // 'icon_selected' => '/asset/imgs/default_icon/a14.png',
//                'uri'        => 'domain-parse',
//                'created_at' => $now,
//                'updated_at' => $now,
//            ],
            [
                'id'            => 39,
                'parent_id'     => 32,
                'order'         => 39,
                'title'         => '蜘蛛配置',
                'icon'          => '/asset/imgs/icon/9.png',
                'icon_selected' => '/asset/imgs/default_icon/a13.png',
                'uri'           => 'spider',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 40,
                'parent_id'     => 0,
                'order'         => 40,
                'title'         => '系统管理',
                'icon'          => '/asset/imgs/icon/12.png',
                'icon_selected' => '/asset/imgs/default_icon/a16.png',
                'uri'           => '',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 41,
                'parent_id'     => 40,
                'order'         => 42,
                'title'         => '密码修改',
                'icon'          => '/asset/imgs/icon/13.png',
                'icon_selected' => '/asset/imgs/icon/13.png',
                'uri'           => 'auth/setting',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 42,
                'parent_id'     => 40,
                'order'         => 43,
                'title'         => '操作日志',
                'icon'          => '/asset/imgs/icon/4.png',
                'icon_selected' => '/asset/imgs/icon/4.png',
                'uri'           => 'auth/logs',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 43,
                'parent_id'     => 40,
                'order'         => 41,
                'title'         => '更新与迁移',
                'icon'          => '',
                'icon_selected' => '',
                'uri'           => 'system-update-migration',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'id'            => 44,
                'parent_id'     => 40,
                'order'         => 35,
                'title'         => '并发配置',
                'icon'          => '',
                'icon_selected' => '',
                'uri'           => 'concurrent',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ]);
    }
}
