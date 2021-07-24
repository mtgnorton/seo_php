<?php

use App\Admin\Forms\SystemUpdate;
use Illuminate\Routing\Router;


Admin::routes();


Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
    'as'         => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');
    // 分类
    $router->resource('categories', CategoryController::class);
    // 文章
    $router->resource('articles', ArticleController::class);
    // 图片
    $router->resource('images', ImageController::class);
    // 标题
    $router->resource('titles', TitleController::class);
    // 网站标题
    $router->resource('website-names', WebsiteNameController::class);
    // 栏目
    $router->resource('columns', ColumnController::class);
    // 句子
    $router->resource('sentences', SentenceController::class);
    // 视频
    $router->resource('videos', VideoController::class);
    // 关键词
    $router->resource('keywords', KeywordController::class);
    // 文件
    $router->resource('files', FileController::class);
    // 自定义
    $router->resource('diys', DiyController::class);
    // 标签
    $router->resource('tags', TagController::class);
    // 模块类型
    $router->resource('template-types', TemplateTypeControlelr::class);
    // 模板
    $router->resource('templates', TemplateController::class);
    // 更改模板列表地址(为了菜单展示)
    // $router->get('templates/index/{category_id}', 'TemplateController@index');
    // 模板模块
    $router->resource('template-modules', TemplateModuleController::class);
    // 模板模块页面
    $router->get('template-module-pages/module', 'TemplateModulePageController@module');
    $router->resource('template-module-pages', TemplateModulePageController::class);
    // 模板素材页面
    $router->resource('template-materials', TemplateMaterialController::class);
    // 内容分类
    $router->resource('content-categories', ContentCategoryController::class);
    // 网站
    $router->get('websites/types', 'WebsiteController@types');
    $router->resource('websites', WebsiteController::class);
    // 自定义标签分类
    $router->post('diy-categories/upload', 'DiyCategoryController@uploadFile');
    $router->resource('diy-categories', DiyCategoryController::class);

    // 启动采集
    $router->any('gathers/run', 'GatherController@run');

    $router->resource('gathers', GatherController::class);
    // 停止采集
    $router->any('gathers/stop', 'GatherController@stop');
    // 系统配置
    $router->get('settings', 'SettingController@index');
    // 系统配置
    $router->get('ads', 'AdController@index');
    // 站点配置
    $router->get('sites', 'SiteController@index');
    // 蜘蛛配置
    $router->get('spider', 'SpiderSettingController@index');
    // 缓存配置
    $router->get('caches', 'CacheController@index');
    // 清空缓存
    $router->get('cache/clear', 'CacheController@clear');
    // 获取蜘蛛饼状图数据
    $router->get('spider-records/pie-data', 'SpiderController@pieData');
    // 获取蜘蛛小时条形图图数据
    $router->get('spider-records/hour-data', 'SpiderController@hourData');
    // 获取蜘蛛小时条形图图数据
    $router->get('spider-records/day-data', 'SpiderController@dayData');
    // 蜘蛛管理
    $router->resource('spider-records', SpiderController::class);

    // 镜像相关
    $router->resource('mirrors', MirrorController::class);

    // 预览镜像
    $router->any('mirrors/preview/id/{id}/{target}', 'MirrorController@preview')->where('target', '(.*)');;


    //镜像相关
    $router->resource('mirrors', MirrorController::class);


    //安装
    $router->get('install', 'InstallController@form');

    $router->any('install-save', 'InstallController@formSave');

    //域名解析
    $router->resource('domain-parse', 'DomainParseController');
    $router->resource('website-templates', WebsiteTemplateController::class);
    $router->get('auth/logs', 'LogController@index');

    // 模板分组
    $router->resource('template-groups', TempalteGroupController::class);

    /*系统管理*/
    $router->any('system-update-migration', 'SettingUpdateMigrationController@index');


    $router->any('migration-export', 'SystemMigrationController@export');
    $router->any('migration-import', 'SystemMigrationController@import');
    $router->any('migration-download', 'SystemMigrationController@download');

    $router->any('server-export', 'SystemMigrationController@serverDownload');

});

Route::middleware(['web'])->get('/admin/auth/forget', 'App\Admin\Controllers\AuthController@forgetForm');
Route::middleware(['web'])->post('/admin/auth/forget', 'App\Admin\Controllers\AuthController@forget');
