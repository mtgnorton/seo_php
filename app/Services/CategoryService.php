<?php

namespace App\Services;

use App\Constants\RedisCacheKeyConstant;
use App\Models\Category as CategoryModel;
use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Config;
use App\Models\Menu;
use App\Models\Template;
use App\Models\TemplateGroup;
use Doctrine\Common\Cache\RedisCache;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 站点分类服务类
 *
 * Class CategoryService
 * @package App\Services
 */
class CategoryService extends BaseService
{
    /**
     * 基本分类
     * 
     * @var array
     */
    const CATEGORY_BASE = [
        '/' => '首页',
        '/base/head' => '头部',
        '/base/foot' => '尾部',
    ];

    /**
     * 将分类数据拼成下拉框需要的格式
     *
     * @param   array   $condition  查询条件
     * @return  array
     */
    public static function categoryOptions($condition=[])
    {
        $result = CategoryModel::where($condition)
                        ->pluck('name', 'id');

        return $result->isEmpty() ?
                    [0 => '暂无分类'] :
                    $result;
    }

    /**
     * Undocumented function
     *
     * @param array $condition
     * @return void
     */
    public static function all($condition=[])
    {
        return CategoryModel::where($condition)
                        ->get();
    }

    /**
     * 新增数据
     *
     * @param array $data
     * @return void
     */
    public static function add(array $data)
    {
        return Category::create($data);
    }

    /**
     * 创建分类规则
     *
     * @param App\Models\Category $category     分类模型对象
     * @return void
     */
    public static function createCategroyRules(CategoryModel $category)
    {
        $insertData = [];
        // 判断分类系统模板是否为空
        if (!empty($category->module)) {
            foreach ($category->module as $key => $system) {
                $insertData[] = [
                    'type' => 'fixed',
                    'route_tag' => $key,
                    'route_name' => $system,
                ];
                $insertData[] = [
                    'type' => 'random',
                    'route_tag' => $key,
                    'route_name' => $system,
                ];
            }
            
            DB::beginTransaction();
    
            try {
                $category->rules()->createMany($insertData);
    
                DB::commit();
    
                return $category;
            } catch (Exception $e) {
                DB::rollBack();
                common_log('写入分类规则失败', $e);
    
                throw $e;
            }
        }
    }

    /**
     * 更新分类规则
     *
     * @param   Category $category    分类模型对象
     * @return  Category
     */
    public static function updateCategoryRules(CategoryModel $category)
    {
        DB::beginTransaction();

        try {
            // 判断分类系统模板是否为空
            if (!empty($category->module)) {
                $systemData = $category->module;
                $systemKeys = array_keys($category->module);

                // 将数据之外的页面删除
                CategoryRule::where([
                            'category_id' => $category->id,
                        ])->whereNotIn('route_tag', $systemKeys)
                        ->delete();

                // 判断是否所有页面都存在, 如不存在, 则创建
                foreach ($systemData as $key => $system) {
                    CategoryRule::firstOrCreate([
                        'category_id' => $category->id,
                        'type' => 'fixed',
                        'route_tag' => $key,
                        'route_name' => $system,
                    ]);
                    CategoryRule::firstOrCreate([
                        'category_id' => $category->id,
                        'type' => 'random',
                        'route_tag' => $key,
                        'route_name' => $system,
                    ]);
                }
            } else {
                CategoryRule::where([
                    'category_id' => $category->id,
                ])->delete();
            }

            DB::commit();

            return $category;
        } catch (Exception $e) {
            DB::rollBack();
            common_log('更新分类规则失败', $e);

            throw $e;
        }
    }

    /**
     * 新增分类时增加对应菜单
     *
     * @param int $categoryId
     * @return void
     */
    public static function addMenu($categoryId)
    {
        DB::beginTransaction();

        try {
            $siteMenu = Menu::where('title', '站点分类')->first();
            $parentId = $siteMenu->id ?? 36;
            $parent = Menu::find($parentId);
            $category = Category::find($categoryId);

            // 新建分类菜单
            $url = 'template-groups?category_id='.$categoryId;
            // $url = 'templates?category_id='.$categoryId;
            $categoryMenu = $parent->children()->create([
                'title' => $category->name,
                'uri' => $url,
                'icon' => '/asset/imgs/icon/4.png',
                'order' => $categoryId
            ]);

            // $categoryMenu->children()->createMany([
            //     [
            //         'title' => '模板设置',
            //         'icon' => 'fa-bars',
            //         'uri' => 'templates?category_id=' . $categoryId
            //     ],
            //     [
            //         'title' => '物料库',
            //         'icon' => 'fa-bars',
            //         'uri' => 'content-categories?type=title&category_id=' . $categoryId
            //     ],
            //     [
            //         'title' => '广告管理',
            //         'icon' => 'fa-bars',
            //         'uri' => 'ads?category_id=' . $categoryId
            //     ],
            //     [
            //         'title' => '缓存设置',
            //         'icon' => 'fa-bars',
            //         'uri' => 'caches?category_id=' . $categoryId
            //     ],
            // ]);

            // // 添加默认配置
            // ConfigService::addDefaultAd($categoryId);
            // ConfigService::addDefaultCache($categoryId);

            DB::commit();

            return $url;
        } catch (Exception $e) {
            DB::rollBack();
            common_log('新增分类菜单失败, 分类ID为: '.$categoryId, $e);
        }
    }

    /**
     * 删除分类
     *
     * @param int $categoryId   分类ID
     * @return void
     */
    public static function delete($categoryId)
    {
        common_log('开始删除分类, 分类ID为: '.$categoryId);
        DB::beginTransaction();

        try {
            $category = Category::findOrFail($categoryId);

            $key = RedisCacheKeyConstant::CACHE_DELETE_CATEGORY . $categoryId;
            Cache::store('file')->put($key, $category, 3600);
            
            $tag = $category->tag ?? '';
            if (empty($tag)) {
                throw new Exception('删除分类失败, 分类标识获取失败');
            }
            // 1. 删除分类记录
            Category::where('id', $categoryId)->delete();
    
            // 2. 删除菜单
            Menu::where('uri', 'template-groups?category_id='.$categoryId)->delete();
    
            // 3. 删除模板组
            $groups = TemplateGroup::where('category_id', $categoryId)->get();
            foreach  ($groups as $group) {
                $group->delete();
            }
            // // 3. 删除模板
            // $templates = Template::where('category_id', $categoryId)->get();
            // foreach  ($templates as $template) {
            //     $template->delete();
            // }
    
            // 4. 删除配置
            // Config::where('category_id', $categoryId)->delete();

            // 4. 删除目录文件夹
            // $path = 'template/'.$tag;
            // if ($path == 'template/') {
            //     throw new Exception('删除分类失败, 分类标识取失败');
            // }
            // if (Storage::disk('public')->exists($path)) {
            //     Storage::disk('public')->deleteDirectory($path);
            // }

            DB::commit();

            common_log('删除分类成功, 分类ID为: '.$categoryId);

            return self::success();
        } catch (Exception $e) {
            DB::rollBack();
            common_log('删除失败: ', $e);

            return self::error('删除失败');
        }
    }
}
