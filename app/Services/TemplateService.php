<?php

namespace App\Services;

use App\Admin\Forms\Spider;
use App\Constants\RedisCacheKeyConstant;
use App\Models\Category;
use App\Models\Config;
use App\Models\Template as TemplateModel;
use App\Models\Template;
use App\Models\TemplateGroup;
use App\Models\TemplateMaterial;
use App\Models\TemplateType;
use App\Models\TemplateModule;
use App\Models\TemplateModulePage;
use App\Models\Website;
use App\Models\WebsiteTemplate;
use Doctrine\Common\Cache\RedisCache;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Models\ContentCategory;
use App\Models\File;

/**
 * 模板服务类
 *
 * Class TemplateService
 * @package App\Services
 */
class TemplateService extends BaseService
{
    const MAX_PAGE_COUNT = 3;

    /**
     * 模板素材类型
     * 
     * @var array
     */
    const MATERIAL_TYPE = [
        'js' => 'JS文件',
        'css' => 'CSS文件',
        'other' => '其他文件',
    ];

    /**
     * 基本栏目路径
     * 
     * @var array
     */
    const BASE_COLUMN_PATH = [
        'list' => '列表',
        'detail' => '详情',
    ];

    /**
     * 将分类数据拼成下拉框需要的格式
     *
     * @return array
     */
    public static function typeOptions($condition=[], $value = 'name', $key = 'id')
    {
        $result = TemplateType::where($condition)
                        ->orderBy('id', 'asc')
                        ->pluck($value, $key);

        return $result->isEmpty() ?
                    [0 => '暂无类型'] :
                    $result;
    }

    /**
     * 将模板数据拼成下拉框需要的格式
     *
     * @return array
     */
    public static function templateOptions($condition=[])
    {
        $data = TemplateModel::where($condition)
                        ->with('group:id,name')
                        ->select('name', 'id', 'group_id')
                        ->get();
        $result = [];
        foreach ($data as $template) {
            $result[$template->id] = $template->name . '----' . ($template->group ? $template->group->name : '');
        }

        return empty($result) ?
                    [0 => '暂无模板'] :
                    $result;
    }

    /**
     * 将模块数据拼成下拉框需要的格式
     *
     * @return array
     */
    public static function moduleOptions($templateId)
    {
        $template = TemplateModel::find($templateId);
        $templateName = $template->name ?? '';

        $result = TemplateModule::where([
                    'template_id' => $templateId
                ])->pluck('route_name', 'id')
                ->map(function ($value) use ($templateName) {
                    return $templateName . '--' . $value;
                });

        return $result->isEmpty() ?
                    [0 => '暂无模块'] :
                    $result;
    }

    /**
     * 将模块数据拼成下拉框需要的格式
     *
     * @return array
     */
    public static function moduleChainOptions($templateId)
    {
        return TemplateModule::where('template_id', $templateId)
                    ->get(['id', DB::raw('route_name as text')]);
    }

    /**
     * 将模块数据拼成下拉框需要的格式
     *
     * @return array
     */
    public static function templateSelect($condition = [])
    {
        return TemplateModel::where($condition)
                    ->get()->map(function ($template, $key) {
                        return [
                            'id' => $template->id,
                            'text' => $template->type->name . '----' . $template->name
                        ];
                    });
    }

    /**
     * 将模块数据拼成下拉框需要的格式
     *
     * @return array
     */
    public static function templates($condition = [])
    {
        return TemplateModel::where($condition)
                    ->get()->mapWithKeys(function ($template) {
                        return [
                            $template->id => $template->type->name . '----' . $template->name
                        ];
                    })->toArray();
    }

    /**
     * 新建模板对应模块数据
     *
     * @param TemplateModel $template   模板对象
     * @return TemplateModule
     */
    public static function createModules(TemplateModel $template)
    {
        $insertData = [];

        // 判断模板对应模块是否为空
        $modules = $template->module;
        if (!empty($modules)) {
            foreach ($modules as $key => $module) {
                $path = 'template/' .
                        $template->category->tag . 
                        '/' . $template->group->tag .
                        '/' . $template->tag . 
                        '/' . $key;
                $insertData[] = [
                    'template_id' => $template->id,
                    'route_name' => $module,
                    'route_tag' => $key,
                    'path' => $path
                ];
            }

            DB::beginTransaction();

            try {
                $template->modules()->createMany($insertData);

                DB::commit();

                return $template;
            } catch (Exception $e) {
                DB::rollBack();
                common_log('写入模板模块失败', $e);

                throw $e;
            }
        }
    }

    /**
     * 新建首页模块
     *
     * @param TemplateModel $template   模板对象
     * @return void
     */
    public static function createIndexModule(TemplateModel $template)
    {
        $path = 'template/' .
                $template->category->tag . 
                '/' . $template->group->tag .
                '/' . $template->tag . '/';

        $moduleData = [
            '首页' => '',
            '头部' => 'base/head/',
            '尾部' => 'base/foot/',
        ];

        foreach ($moduleData as $key => $tag) {
            TemplateModule::create([
                'template_id' => $template->id,
                'route_name' => $key,
                'route_tag' => '/' . $tag,
                'path' => $path . $tag,
            ]);
        }
    }

    /**
     * 上传模块文件
     *
     * @param TemplateModule $module    模块对象
     * @param array $data        文件
     * @return void
     */
    public static function uploadModuleFile(TemplateModule $module, array $data)
    {
        if (is_array($data)) {
            $count = count($data);
            $successCount = 0;
            $failCount = 0;
            foreach ($data as $file) {
                $path = $module->path;
                $fileName = $file->getClientOriginalName();
                $ext = $file->getClientOriginalExtension();
        
                if ($ext != 'html') {
                    $failCount++;
                    continue;
                }

                $content = file_get_contents($file);
                $content = CommonService::changeCharset2Utf8($content, 'html');
                // dump($path, $fileName);
                // $fileResult = Storage::disk('public')->putFileAs($path, $file, $fileName);
                // dump($fileResult);
                $fullPath = rtrim($path, '/') . '/' . $fileName;
                $fileResult = Storage::disk('public')->put($fullPath, $content);
        
                if (empty($fileResult)) {
                    $failCount++;
                    continue;
                }
        
                // 判断是否是重名文件
                $modulePage = TemplateModulePage::where([
                    'module_id' => $module->id,
                    'file_name' => $fileName
                ])->first();
        
                if (!empty($modulePage)) {
                    $successCount++;
                    continue;
                }
        
                // 判断当前页面是否已经超过最大页面数
                $count = TemplateModulePage::where('module_id', $module->id)
                                            ->count();
                if ($count >= self::MAX_PAGE_COUNT) {
                    // 删除已上传的文件
                    Storage::disk('public')->delete($fullPath);
        
                    return self::error('当前模块文件数量大于最大限制数: ' . self::MAX_PAGE_COUNT);
                }
        
                // 判断路径最后是否以/结尾
                if ($path[mb_strlen($path) - 1] !== '/') {
                    $path .= '/';
                }
        
                // 写入数据库
                TemplateModulePage::create([
                    'template_id' => $module->template_id,
                    'module_id' => $module->id,
                    'file_name' => $fileName,
                    'full_path' => $path . $fileName
                ]);
                // 清空对应缓存
                // $key = 'public'. $path . $fileName;
                // Cache::forget($key);
        
                $successCount++;
            }

            return self::success(
                [],
                "文件数量为{$count}个, 上传成功{$successCount}个, 上传失败为{$failCount}个"
            );
        } else {
            return self::error('上传失败');
        }
    }

    /**
     * 获取分页文件内容
     *
     * @param integer $pageId
     * @return void
     */
    public static function getPageFileContent($pageId = 0)
    {
        $page = TemplateModulePage::findOrFail($pageId);
        $content = '';
        $path = $page->full_path;

        // 判断文件是否存在
        if (Storage::disk('public')->exists($path)) {
            $content = Storage::disk('public')->get($path);
        }

        return $content;
    }

    /**
     * 获取页面详情
     *
     * @param integer $pageId
     * @return void
     */
    public static function pageInfo($condition)
    {
        if (!is_array($condition)) {
            $condition = ['id' => $condition];
        }

        return TemplateModulePage::where($condition)->firstOrFail();
    }

    /**
     * 获取页面详情
     *
     * @param integer $pageId
     * @return void
     */
    public static function materialInfo($condition)
    {
        if (!is_array($condition)) {
            $condition = ['id' => $condition];
        }

        return TemplateMaterial::where($condition)->firstOrFail();
    }

    /**
     * 更新页面文件内容
     *
     * @param integer $id       模块页面ID
     * @param string $content   内容
     * @return void
     */
    public static function editPageFileContent(int $id, string $content)
    {
        $page = self::pageInfo($id);

        if (Storage::disk('public')->exists($page->full_path)) {
            return Storage::disk('public')->put($page->full_path, $content);
        }
        
        throw new Exception('文件不存在, 请重试');
    }

    /**
     * 更新素材文件内容
     *
     * @param integer $id       模块页面ID
     * @param string $content   内容
     * @return void
     */
    public static function editMaterialFileContent(int $id, string $content)
    {
        $material = self::materialInfo($id);

        if (Storage::disk('public')->exists($material->full_path)) {
            return Storage::disk('public')->put($material->full_path, $content);
        }
        
        throw new Exception('文件不存在, 请重试');
    }

    /**
     * 新增素材
     *
     * @param mixed $data
     * @param string $type
     * @param integer $templateId
     * @return void
     */
    public static function addMaterial(
        array $data,
        string $type,
        int $templateId,
        int $moduleId
    ) {
        // 判断file是否是数组
        if (is_array($data)) {
            $count = count($data);
            $successCount = 0;
            $failCount = 0;
            foreach ($data as $file) {
                // 判断文件是否存在
                if (!file_exists($file)) {
                    $failCount++;
                    continue;
                }
                $ext = $file->getClientOriginalExtension();
                // 如果类型是js,css, 则文件后缀必须为js,css
                if ($type != 'other' && $ext != $type) {
                    $failCount++;
                    continue;
                }
        
                // 保存文件
                $template = TemplateModel::findOrFail($templateId);
                $module = TemplateModule::findOrFail($moduleId);
                $columnTag = $module->column_tag ?: 'index';
                $path = 'template/' .
                    $template->category->tag . 
                    '/' . $template->group->tag .
                    '/' . $template->tag . 
                    '/' . $columnTag .
                    '/static' .
                    '/' . $type;

                $fileName = $file->getClientOriginalName();
        
                $fileResult = Storage::disk('public')->putFileAs($path, $file, $fileName);
        
                if (empty($fileResult)) {
                    $failCount++;
                    continue;
                }
        
                // 判断文件是不是已存在, 已存在则覆盖
                if (TemplateMaterial::where([
                    'template_id' => $templateId,
                    'module_id' => $module->id,
                    'type' => $type,
                    'file_name' => $fileName
                ])->exists()) {
                    $successCount++;
                    continue;
                }
        
                TemplateMaterial::create([
                    'template_id' => $templateId,
                    'module_id' => $module->id,
                    'type' => $type,
                    'path' => $path,
                    'file_name' => $fileName,
                    'full_path' => $path . '/' . $fileName,
                ]);
        
                $successCount++;
            }

            return self::success(
                [],
                "文件数量为{$count}个, 上传成功{$successCount}个, 上传失败为{$failCount}个"
            );
        } else {
            return self::error('上传失败');
        }
    }

    /**
     * 添加模块
     *
     * @param integer $templateId   模板ID
     * @param string $columnName    栏目名称
     * @param string $columnPath    栏目标识
     * @return void
     */
    public static function addModule(int $templateId, string $columnName, string $tag, int $parentId = 0)
    {
        // 判断栏目标识如果为list或者detail, 则返回错误
        if (in_array($tag, ['list', 'detail'])) {
            return self::error('栏目标识名称不能为list或detail');
        }
        // 获取模型
        $template = TemplateModel::find($templateId);
        // $parentTag = self::getParentTags($parentId);
        $level = (TemplateModule::find($parentId)->level ?? 0) + 1;

        // 去除栏目两端的 / 
        $columnTag = self::getTrueColumnTag($parentId, $tag);
        $columnTag = trim($columnTag, '/');

        // 判断该模块在该模型中是否已存在
        $moduleCount = $template->modules()->where('column_tag', $columnTag)->count();
        if ($moduleCount > 0) {
            return self::error('该模块已存在');
        }

        $basePath = 'template/' .
                $template->category->tag .
                '/' . $template->group->tag .
                '/' . $template->tag;

        DB::beginTransaction();

        try {
            $baseColumnPath = self::BASE_COLUMN_PATH;

            foreach ($baseColumnPath as $key => $columnPath) {
                $routeName = $columnName . $columnPath;
                $routeTag = '/'.$columnTag.'/'.$key.'/';
                // if (!empty($parentTag)) {
                //     $routeTag = '/'.$parentTag.$routeTag;
                // }
                TemplateModule::create([
                    'template_id' => $templateId,
                    'column_name' => $columnName,
                    'column_tag' => $columnTag,
                    'route_name' => $columnName . $columnPath,
                    'route_tag' => $routeTag,
                    'path' => $basePath . $routeTag,
                    'type' => $key,
                    'parent_id' => $parentId,
                    'level' => $level
                ]);

                // 新增模型中的module字段
                $moduleArr = $template->module;
                $moduleArr[$routeTag] = $routeName;
                $template->module = $moduleArr;
                $template->save();
            }

            DB::commit();

            return self::success();
        } catch (Exception $e) {
            DB::rollBack();
            common_log('模块添加失败', $e);

            return self::error('模块添加失败');
        }
    }

    /**
     * 获取所有父类栏目标识
     *
     * @param int $parentId 父类ID
     * @return string
     */
    public static function getParentTags($moduleId)
    {
        static $tagData = [];

        $module = TemplateModule::find($moduleId);
        if (empty($module)) {
            return '';
        }

        $tagData[] = $module->column_tag ?? '';
        $parentId = $module->parent_id ?? '';

        if (!empty($parentId)) {
            return self::getParentTags($module->parent_id ?? 0);
        }

        $tagData = array_reverse($tagData);
        $tagStr = implode('/', $tagData);

        return $tagStr;
    }

    /**
     * 获取素材内容
     *
     * @param integer $id
     * @return void
     */
    public static function getMaterialContent(int $id)
    {
        $page = TemplateMaterial::findOrFail($id);
        $content = '';
        $path = $page->full_path;

        if ($page->type == 'other') {
            throw new Exception(ll('Only js or css file can edit'));
        }

        // 判断文件是否存在
        if (Storage::disk('public')->exists($path)) {
            $content = Storage::disk('public')->get($path);
        }

        return $content;
    }

    /**
     * 获取默认的模块页面
     *
     * @param integer $templateId   模板ID
     * @param string  $type         模板类型
     * @return void
     */
    public static function getDefaultModule(int $templateId, $type = 'index')
    {
        if (!in_array($type, [
            'index', 'list', 'detail'
        ])) {
            $type = 'index';
        }

        // 判断该模板是否含有带页面的模块
        $query = TemplateModule::where('template_id', $templateId);
        if ($type == 'index') {
            $query->where('route_tag', '/');
        } else {
            $query->where('path', 'like', '%'.$type.'/');
        }
        $moduleIds = $query->whereHas('pages')
                            ->pluck('id')
                            ->toArray();
        $count = count($moduleIds) - 1;

        if ($count < 0) {
            return 0;
        }

        return $moduleIds[mt_rand(0, $count)] ?? 0;
    }

    /**
     * 增加类型
     *
     * @param array $data   数组
     * @return void
     */
    public static function addType(array $data)
    {
        return TemplateType::create($data);
    }

    /**
     * 获取当前网站的模板ID
     *
     * @param Website $website
     * @return int
     */
    public static function getWebsiteTemplateId()
    {
        // // 获取当前域名信息
        // $host = request()->getHost();

        // $hostArr = explode('.', $host);

        // $newHostArr = [];
        // for ($i=0; $i<2; $i++) {
        //     array_unshift($newHostArr, array_pop($hostArr));
        // }

        // $newHost = implode('.', $newHostArr);
        // $wwwNewHost = 'www.' . $newHost;
        // $hostData = compact('host', 'newHost', 'wwwNewHost');
        // $groupIds = Website::whereIn('url', $hostData)
        //                     ->pluck('group_id')
        //                     ->toArray();

        // if (empty($groupIds)) {
        //     return 0;
        // }

        // $groupId = $groupIds[0];
        $groupId = self::getGroupId();
        
        if (empty($groupId)) {
            return 0;
        }

        $spider = SpiderService::getSpider();

        if (empty($spider) || $spider === 'other') {
            $tag = 'common';
        } else {
            $tag = $spider;
        }
        
        // 判断该标签下是否有对应模板
        // $templates = Template::where([
        //             'type_tag' => $tag,
        //             'group_id' => $groupId,
        //         ])->get()
        //         ->toArray();

        $template = CommonService::templates([
            'type_tag' => $tag,
            'group_id' => $groupId,
        ], 1);

        if (!empty($template)) {
            return $template['id'] ?? 0;
        }

        // 判断通用模板是否有值
        // $templates = Template::where([
        //             'type_tag' => 'common',
        //             'group_id' => $groupId
        //         ])->get()
        //         ->toArray();

        $template = CommonService::templates([
            'type_tag' => 'common',
            'group_id' => $groupId
        ], 1);

        if (!empty($template)) {
            return $template['id'] ?? 0;
        }

        // 判断所有模板是否有值
        // $templates = Template::where([
        //                 'group_id' => $groupId
        //             ])->get()
        //             ->toArray();

        $template = CommonService::templates([
            'group_id' => $groupId
        ], 1);

        if (!empty($template)) {
            return $template['id'] ?? 0;
        }

        return 0;
    }

    /**
     * 获取当前网址对应分组ID
     *
     * @return int
     */
    public static function getGroupId()
    {
        // 获取当前域名信息
        $host = request()->getHost();

        $hostArr = explode('.', $host);

        $newHostArr = [];
        for ($i=0; $i<2; $i++) {
            array_unshift($newHostArr, array_pop($hostArr));
        }

        $newHost = implode('.', $newHostArr);
        $wwwNewHost = 'www.' . $newHost;
        // $hostData = compact('host', 'newHost', 'wwwNewHost');

        $groupData1 = CommonService::websites(['url' => $host], 1, 'group_id');
        if (!empty($groupData1)) {
            return $groupData1;
        }
        $groupData2 = CommonService::websites(['url' => $newHost], 1, 'group_id');
        if (!empty($groupData2)) {
            return $groupData2;
        }
        $groupData3 = CommonService::websites(['url' => $wwwNewHost], 1, 'group_id');
        if (!empty($groupData3)) {
            return $groupData3;
        }
        
        return 0;
    }

    /**
     * 新增模板
     *
     * @param array $param
     * @return void
     */
    public static function add(array $param)
    {
        DB::beginTransaction();

        try {
            $data = Arr::only($param, [
                'name',
                'tag',
                'type_id',
            ]);
            $data['group_id'] = $param['groupId'] ?? 0;
            $data['category_id'] = $param['categoryId'] ?? 0;
            // 判断该tag在该分类下是否已存在
            if (Template::where([
                'group_id' => $data['group_id'],
                'tag' => $data['tag']
            ])->exists()) {
                DB::rollBack();

                return self::error('该分类下该标签名已存在, 请更换标签名');
            }
            $data['module'] = CategoryService::CATEGORY_BASE;
            $data['type_tag'] = TemplateType::find($data['type_id'])->tag;

            $template = TemplateModel::create($data);

            // 删除缓存
            $key = RedisCacheKeyConstant::CACHE_TEMPLATES;
            Cache::store('file')->forget($key);

            // 创建模板模块表数据
            self::createIndexModule($template);
            
            // // 添加配置
            // ConfigService::addDefaultSite($template);

            DB::commit();

            return self::success();
        } catch (Exception $e) {
            DB::rollBack();
            common_log('插入模板失败', $e);

            return self::error();
        }
    }

    /**
     * 复制模板
     *
     * @param integer $templateId   模板ID
     * @param array $params         参数
     * @return array
     */
    public static function copyTemplate(int $templateId, array $params)
    {
        DB::beginTransaction();

        try {
            // 1. 新增模板表数据
            $originalTemplate = Template::find($templateId);
            $name = $params['name'] ?? '';
            $tag = trim($params['tag'] ?? '', '/');
            $groupId = $params['group_id'] ?? 0;
            $typeId = $params['type_id'] ?? 0;

            $type = TemplateType::find($typeId);
            $group = TemplateGroup::find($groupId);

            // 判断模板是否已存在
            if (Template::where([
                'group_id' => $groupId,
                'tag' => $tag,
            ])->exists()) {
                DB::rollBack();

                return self::error('该模板已存在');
            }
    
            $templateParams = [
                'name' => $name,
                'tag' => $tag,
                'group_id' => $groupId,
                'category_id' => $group->category_id,
                'type_id' => $typeId,
                'type_tag' => $type->tag ?? '',
                'module' => $originalTemplate->module ?? '',
            ];
    
            $template = Template::create($templateParams);

            // 删除缓存
            $key = RedisCacheKeyConstant::CACHE_TEMPLATES;
            Cache::store('file')->forget($key);

            // 2. 新增模板模块
            $originalModuleBasePath = 'template/' .
                            $originalTemplate->category->tag .
                            '/' . $originalTemplate->group->tag .
                            '/' . $originalTemplate->tag;
            $moduleBasePath = 'template/' .
                    $template->category->tag .
                    '/' . $template->group->tag .
                    '/' . $template->tag;

            $originalModules = $originalTemplate->modules;

            $moduleContrastData = [];
            foreach ($originalModules as $originalModule) {
                $path = str_replace($originalModuleBasePath, $moduleBasePath, $originalModule->path);
                $moduleData = [
                    'template_id' => $template->id,
                    'column_name' => $originalModule->column_name,
                    'column_tag' => $originalModule->column_tag,
                    'route_name' => $originalModule->route_name,
                    'route_tag' => $originalModule->route_tag,
                    'path' => $path,
                ];

                $module = TemplateModule::create($moduleData);
                $moduleContrastData[$originalModule->id] = $module->id;
            }

            // 3. 新增模板文件
            // 3.1 复制数据库内容
            $originalPages = $originalTemplate->pages()->orderBy('id', 'asc')->get();
            foreach ($originalPages as $originalPage) {
                $originalFullPath = $originalPage->full_path;
                $fullPath = str_replace(
                    $originalModuleBasePath,
                    $moduleBasePath,
                    $originalFullPath
                );
                $pageData = [
                    'template_id' => $template->id,
                    'module_id' => $moduleContrastData[$originalPage->module_id] ?? 0,
                    'file_name' => $originalPage->file_name,
                    'full_path' => $fullPath
                ];
                
                TemplateModulePage::create($pageData);

                // 3.2 复制文件
                if (!Storage::disk('public')->exists($fullPath)) {
                    Storage::disk('public')->copy($originalFullPath, $fullPath);
                }
            }

            // 4.新增素材文件
            // 4.1 复制数据库
            $originalMaterials = $originalTemplate->materials;
            foreach ($originalMaterials as $originalMaterial) {
                $materialPath = str_replace(
                    $originalModuleBasePath,
                    $moduleBasePath,
                    $originalMaterial->path
                );
                $materialFullPath = str_replace(
                    $originalModuleBasePath,
                    $moduleBasePath,
                    $originalMaterial->full_path
                );
                $materialData = [
                    'template_id' => $template->id,
                    'module_id' => $moduleContrastData[$originalMaterial->module_id] ?? 0,
                    'type' => $originalMaterial->type,
                    'path' => $materialPath,
                    'file_name' => $originalMaterial->file_name,
                    'full_path' => $materialFullPath,
                ];

                TemplateMaterial::create($materialData);

                // 复制文件
                $originMatrialPath = $originalMaterial->full_path;
                if (!Storage::disk('public')->exists($materialFullPath) &&
                    Storage::disk('public')->exists($originMatrialPath)
                ) {
                    Storage::disk('public')->copy($originMatrialPath, $materialFullPath);
                }
            }

            // 5. 绑定域名
            // $originalWebsites = Website::where('group_id', $originalTemplate->group_id)->get();
            // if ($originalTemplate->group_Id != $template->group_id) {
            //     $originalWebsites = CommonService::websites(['group_id', $originalTemplate->group_id]);
            //     // $websiteContrastData = [];
            //     foreach ($originalWebsites as $originalWebsite) {
            //         $websiteData = [
            //             // 'name' => $originalWebsite->name,
            //             'url' => $originalWebsite['url'],
            //             'category_id' => $template->category_id,
            //             'group_id' => $template->group_id,
            //             'is_enabled' => $originalWebsite['is_enabled'],
            //         ];
            //         Website::create($websiteData);
    
            //         // $websiteContrastData[$originalWebsite->id] = $website->id;
            //     }
            //     $websiteKey = RedisCacheKeyConstant::CACHE_WEBSITES;
            //     Cache::forget($websiteKey);
            // }
            // $originalWebsiteTemplates = $originalTemplate->websiteTemplates;
            // foreach ($originalWebsiteTemplates as $originalWebsiteTemplate) {
            //     $websiteTemplateData = [
            //         'website_id' => $websiteContrastData[$originalWebsiteTemplate->website_id] ?? 0,
            //         'template_id' => $template->id,
            //     ];
            //     WebsiteTemplate::create($websiteTemplateData);
            // }

            // 6.配置文件
            // $configs = Config::where('template_id', $templateId)->get();
            // foreach ($configs as $config) {
            //     $configData = [
            //         'module' => $config->module,
            //         'category_id' => $categoryId,
            //         'template_id' => $template->id,
            //         'key' => $config->key,
            //         'value' => $config->value,
            //         'is_json' => $config->is_json,
            //         'description' => $config->description,
            //     ];

            //     Config::create($configData);
            // }

            DB::commit();

            return self::success();
        } catch (Exception $e) {
            common_log('复制模板失败, ID为: '.$templateId, $e);

            return self::error('复制失败');
        }
    }

    /**
     * 新增模板分组
     *
     * @param array $param
     * @return array
     */
    public static function addGroup(array $param)
    {
        $data = Arr::only($param, [
            'name',
            'tag',
            'category_id'
        ]);

        // 判断该分类下是否存在相同tag
        if (TemplateGroup::where([
            'tag' => $data['tag'],
            'category_id' => $data['category_id']
        ])->exists()) {
            return self::error('该标识在该分类下已存在');
        }

        $group = TemplateGroup::create($data);

        // 新增各种配置
        ConfigService::addDefaultAd($group);
        ConfigService::addDefaultCache($group);
        ConfigService::addDefaultSite($group);

        return self::success();
    }

    /**
     * 模板分组数据
     *
     * @param array $condition
     * @return array
     */
    public static function groupOptions(array $condition = [])
    {
        $result = TemplateGroup::where($condition)
                        ->pluck('name', 'id');

        return $result->isEmpty() ?
                    [0 => '暂无模板'] :
                    $result;
    }

    /**
     * 获取公共页面数据
     *
     * @param string $tag
     * @return string
     */
    public static function getBaseHtml(string $tag)
    {
        $templateId = TemplateService::getWebsiteTemplateId();
        $route_tag = '';
        if ($tag == '头部标签') {
            // $route_tag = '/base/head/';
            $route_name = '头部';
        } else if ($tag == '尾部标签') {
            // $route_tag = '/base/foot/';
            $route_name = '尾部';
        } else {
            return '';
        }

        $module = TemplateModule::where([
            'template_id' => $templateId,
            // 'route_tag' => $route_tag
            'route_name' => $route_name,
        ])->first();

        if (empty($module)) {
            return '';
        }

        $files = Storage::disk('public')->files($module->path);
        
        $randKey = count($files)-1 >=0 ? count($files) -1 : 0;
        $randIndex = mt_rand(0, $randKey);
        $fullPath = $files[$randIndex] ?? '';

        $content = IndexService::readFile($fullPath);

        return $content;
    }

    /**
     * 根据模板ID获取模块树状数据
     *
     * @param int $templateId   模板ID
     * @param int $parentId     父类ID
     * @return array
     */
    public static function treeModules($templateId, $parentId=0)
    {
        $data = TemplateModule::where([
            'template_id' => $templateId,
            'parent_id' => $parentId,
        ])->where('column_name', '<>', '')
        ->where('route_tag', 'like', '%/list/')->get();
        static $result = [
            0 => '顶级模块'
        ];
        foreach ($data as $val) {
            $result[$val->id] = str_repeat('--', $val->level - 1) . $val->column_name;

            self::treeModules($templateId, $val->id);
        }

        return $result;
    }

    /**
     * 根据模板ID获取模块树状数据
     *
     * @param int $templateId   模板ID
     * @param int $parentId     父类ID
     * @return array
     */
    public static function treeModulesTitle($templateId, $parentId=0)
    {
        $data = TemplateModule::where([
            'template_id' => $templateId,
            'parent_id' => $parentId,
        ])->whereNotIn('route_name', ['头部', '尾部'])
        ->get();
        static $result = [];
        foreach ($data as $val) {
            $result[$val->id] = str_repeat('--', $val->level - 1) . $val->route_name;

            self::treeModulesTitle($templateId, $val->id);
        }

        return $result;
    }

    /**
     * 根据模板ID获取模块树状数据
     *
     * @param int $templateId   模板ID
     * @param int $parentId     父类ID
     * @return array
     */
    public static function allTreeModules($templateId, $parentId=0)
    {
        $template = Template::find($templateId);
        $tag = $template->tag;
        $basePath = 'template/' .
                $template->category->tag .
                '/' . $template->group->tag .
                '/' . $template->tag;
        $data = TemplateModule::where([
                'template_id' => $templateId,
                'parent_id' => $parentId,
            ])->orderBy('id', 'asc')
            ->select(
                'id','template_id','parent_id',
                'level','type','column_name',
                'column_tag','route_name','route_tag',
                'path'
            )->with(
                'pages:template_id,module_id,file_name,full_path',
                'materials:template_id,module_id,type,path,file_name,full_path'
            )->get()->toArray();

        static $result = [];
        foreach ($data as $val) {
            $id = $val['id'] ?? 0;
            unset($val['id']);
            $result[$id] = $val;
            // 复制页面内容和素材到导出文件夹
            foreach ($val['pages'] as $page) {
                $path = $page['full_path'];
                $newPath = str_replace($basePath, 'exportTemplate/'.$tag.'/modules', $path);

                if (Storage::disk('public')->exists($path) &&
                    !Storage::disk('public')->exists($newPath)
                ) {
                    Storage::disk('public')->copy($path, $newPath);
                }
            }
            foreach ($val['materials'] as $material) {
                $path = $material['full_path'];
                $newPath = str_replace($basePath, 'exportTemplate/'.$tag.'/modules', $path);

                if (Storage::disk('public')->exists($path) &&
                    !Storage::disk('public')->exists($newPath)
                ) {
                    Storage::disk('public')->copy($path, $newPath);
                }
            }

            self::allTreeModules($templateId, $id);
        }

        return $result;
    }

    /**
     * 根据模板ID获取物料库分类的树状数据
     *
     * @param integer $templateId
     * @param integer $parentId
     * @return void
     */
    public static function allTreeContentCategory($template, $parentId=0)
    {
        $groupId = $template->group_id ?? 0;
        $tag = $template->tag ?? '';
        $categories = ContentCategory::where([
            'group_id' => $groupId,
            'parent_id' => $parentId
        ])->orderBy('id', 'asc')
        ->select(
            'id', 'category_id', 'group_id',
            'name', 'tag', 'parent_id',
            'sort', 'type'
        )->with('files:id,name,path,ext,size,rows,success_rows,message,type,category_id,tag_id,is_collected')
        ->get()
        ->toArray(); 
        
        static $result = [];
        foreach ($categories as $category) {
            $id = $category['id'];
            unset($category['id']);
            $result[$id] = $category;
            $files = $category['files'];
            foreach ($files as $file) {
                $path = $file['path'];
                $exportPath = 'exportTemplate/'.$tag.'/'.$path;
                if (Storage::disk('admin')->exists($path) &&
                    !Storage::disk('public')->exists($exportPath)
                ) {
                    $data = Storage::disk('admin')->get($path);

                    Storage::disk('public')->put($exportPath, $data);
                }
            }

            self::allTreeContentCategory($template, $id);
        }

        return $result;
    }

    /**
     * 获取所有的物料库内容
     *
     * @param [type] $groupId
     * @return void
     */
    public static function allContentData($template)
    {
        $groupId = $template->group_id ?? 0;
        $tag = $template->tag ?? '';
        // 获取该分组下的所有物料库分类
        $categoryIds = ContentCategory::where('group_id', $groupId)
                                ->pluck('id')->toArray();

        $models = ContentService::CONTENT_MODEL;

        $data = [];
        foreach ($models as $key => $model) {
            $data[$key] = $model::whereIn('category_id', $categoryIds)
                ->orderBy('id', 'asc')
                // ->limit(50)
                ->get()->map(function ($data) use ($key, $tag) {
                    unset($data->created_at);
                    unset($data->updated_at);
                    unset($data->id);

                    // 如果类型是图片, 则将图片文件复制一份保存
                    if ($key == 'image') {
                        $path = $data['url'];
                        $exportPath = str_replace_once('seo/'.$data->category_id, 'exportFiles', $path);
                        if ($exportPath === $path) {
                            $exportPath = str_replace_once('seo', 'exportTemplate/'.$tag.'/files', $path);
                        }
                        if (Storage::disk('admin')->exists($path) &&
                            !Storage::disk('public')->exists($exportPath)
                        ) {
                            $content = Storage::disk('admin')->get($path);

                            Storage::disk('public')->put($exportPath, $content);
                        }
                    }

                    return $data;
                })->toArray();
        }

        return $data;
    }

    /**
     * 获取模块真实标识
     *
     * @param int $parentId
     * @param string $tag
     * @return void
     */
    public static function getTrueColumnTag($parentId=0, $tag='')
    {
        $parentTag = TemplateModule::find($parentId)->column_tag ?? '';

        if (empty($parentTag)) {
            return $tag;
        }

        return $parentTag . '/' . $tag;
    }

    /**
     * 导出模板
     *
     * @param int $templateId
     * @return void
     */
    public static function exportTemplate($templateId)
    {
        // 获取模板数据
        $template = Template::find($templateId);
        if (empty($template)) {
            return self::error('模板数据获取失败');
        }
        $groupId = $template->group_id ?? 0;
        $module = $template->getOriginal('module') ?? '';
        $module = str_replace('\/', '/',$module);

        $path = 'template/' .
                $template->category->tag .
                '/' . $template->group->tag .
                '/' . $template->tag;
        $tag = $template->tag ?? '';
        // 1. 获取所有的模块数据
        common_log('开始导出模块:'.$templateId);
        $modules = self::allTreeModules($templateId);
        common_log('导出模块成功:'.$templateId);
        // 2. 获取物料库分类数据
        common_log('开始导出物料库分类数据:'.$templateId);
        $contentCategories = self::allTreeContentCategory($template);
        common_log('导出物料库分类数据成功:'.$templateId);
        // 3. 获取物料库内容数据
        common_log('开始导出物料库内容:'.$templateId);
        $contents = self::allContentData($template);
        common_log('导出物料库内容成功:'.$templateId);

        $data = [
            'template' => [
                'module' => $module,
                'path' => $path,
                'tag' => $tag,
            ],
            'modules' => $modules,
            'contentCategories' => $contentCategories,
            'contents' => $contents,
        ];

        // 将数据写入文件文件
        $result = var_export($data, true);

        $str = <<<EOF
<?php

return $result;
EOF;
        Storage::disk('public')->put('exportTemplate/'.$tag.'/exportData.php', $str);

        // 将文件夹转为zip包
        common_log('开始打包:'.$templateId);
        $path = storage_path('app/public/exportTemplate/'.$tag);
        $zipName = $tag.'.zip';
        $zipPath = FileService::completePath(storage_path('app/public/exportTemplate/'.$zipName));
        ZipService::zip($path, '', $zipPath);
        common_log('打包成功:'.$templateId);

        common_log('开始删除临时文件夹');
        try {
            // 删除原文件夹
            FileService::deleteDir($path);
        } catch (Exception $e) {
            common_log('删除原文件夹失败', $e);
        }

        return self::success('/storage/exportTemplate/'.$zipName);
    }

    /**
     * 导入模板
     *
     * @param [type] $file
     * @param array $params
     * @return void
     */
    public static function importTemplate(array $params)
    {
        DB::beginTransaction();

        try {
            // $fileData = include 'exportTemplate.php';
            // 获取传来的参数
            // 1. 新增模板表数据
            $name = $params['name'] ?? '';
            $tag = trim($params['tag'] ?? '', '/');
            $groupId = $params['group_id'] ?? 0;
            $typeId = $params['type_id'] ?? 0;
            $file = $params['import_data'] ?? '';
            if (empty($file)) {
                return self::error('文件不能为空');
            }

            common_log('开始解压');
            $filePath = $file->getRealPath();
            $baseFilePath = 'app/public/importTemplate/'.$tag;
            $unzipPath = storage_path($baseFilePath);
            $unzipResult = ZipService::unzip($filePath, $unzipPath);
            
            if (!$unzipResult) {
                common_log('解压失败');
                
                return self::error('解压文件失败, 请重试');
            }
            common_log('解压完成');
            $fileData = include app()->basePath() . '/storage/'.$baseFilePath.'/exportData.php';

            $type = TemplateType::findOrFail($typeId);
            $group = TemplateGroup::findOrFail($groupId);
    
            // 判断模板是否已存在
            if (Template::where([
                'group_id' => $groupId,
                'tag' => $tag,
            ])->exists()) {
                DB::rollBack();
    
                return self::error('该模板已存在');
            }
    
            $originModule = data_get($fileData, 'template.module', '');
            $originPath = data_get($fileData, 'template.path', '');
            $templateParams = [
                'name' => $name,
                'tag' => $tag,
                'group_id' => $groupId,
                'category_id' => $group->category_id,
                'type_id' => $typeId,
                'type_tag' => $type->tag ?? '',
                'module' => $originModule,
            ];
        
            $template = Template::create($templateParams);
            common_log('新建模板成功');
    
            // 删除缓存
            $key = RedisCacheKeyConstant::CACHE_TEMPLATES;
            Cache::store('file')->forget($key);
            common_log('删除缓存成功');
    
            $basePath = data_get($fileData, 'template.path', '');
            if (empty($basePath)) {
                throw new Exception('模板路径获取失败');
            }

            $modules = data_get($fileData, 'modules', []);
            $contentCategories = data_get($fileData, 'contentCategories', []);
            $contentData = data_get($fileData, 'contents', []);

            // 插入全部的模板
            self::insertAllModules($template, $modules, $originPath);
            common_log('插入模板数据成功');
            // 插入全部的物料库分类
            $categoryFileData = self::insertAllContentCategories($template, $contentCategories);
            common_log('插入物料库分类数据成功');

            // 插入物料库内容
            self::insertAllContent($template, $contentData, $categoryFileData);
            common_log('插入物料库内容成功');

            // 删除临时数据
            common_log('开始删除临时文件夹');
            try {
                // 删除临时文件夹
                FileService::deleteDir($unzipPath);
            } catch (Exception $e) {
                common_log('临时文件夹删除失败');
            }
            common_log('临时文件夹删除成功');
            
            DB::commit();

            return self::success();
        } catch (Exception $e) {
            DB::rollBack();
            common_log('导入模板失败, 失败数据为: '.json_encode($params, JSON_UNESCAPED_UNICODE), $e);

            return self::error('导入模板失败, 请稍后重试: '.$e->getMessage());
        }
    }

    /**
     * 插入全部模块
     *
     * @param [type] $template
     * @param [type] $modules
     * @return void
     */
    public static function insertAllModules($template, $modules, $originPath, $parentId=0, $newParentId=0)
    {
        $templateId = $template->id ?? 0;
        $tag = rtrim($template->tag, '/');
        $newBasePath = 'template/' .
                $template->category->tag .
                '/' . $template->group->tag .
                '/' . $tag;

        foreach ($modules as $key => $data) {
            if ($data['parent_id'] == $parentId) {
                $data['parent_id'] = $newParentId;
                $data['template_id'] = $templateId;
                $routeTag = $data['route_tag'];
                $path = $data['path'];
                $pages = $data['pages'];
                $materials = $data['materials'];
                unset($data['pages']);
                unset($data['materials']);
                if ($routeTag == '/') {
                    $originalPath = mb_substr($path, 0, mb_strlen($path)-1);
                } else {
                    $originalPath = str_replace($data['route_tag'], '', $data['path']);
                }
                $data['path'] =str_replace($originalPath, $newBasePath, $path);

                $newModule = TemplateModule::create($data);

                common_log('开始复制模板文件: '.$path);
                foreach ($pages as $page) {
                    $fullPath = $page['full_path'];
                    $truePath = str_replace($originalPath, 'importTemplate/'.$template->tag.'/modules', $fullPath);
                    $fullPath = str_replace($originalPath, $newBasePath, $fullPath);

                    $pageData = [
                        'template_id' => $templateId,
                        'module_id' => $newModule->id,
                        'file_name' => $page['file_name'],
                        'full_path' => $fullPath
                    ];

                    TemplateModulePage::create($pageData);
    
                    // 3.2 复制文件
                    if (Storage::disk('public')->exists($truePath)
                        // && !Storage::disk('public')->exists($fullPath)
                    ) {
                        $data = Storage::disk('public')->get($truePath);
                        $newData = str_replace($originPath, $newBasePath, $data);
                        
                        Storage::disk('public')->put($fullPath, $newData);
                    }
                }
                common_log('模板文件复制完成: '.$path);

                common_log('开始复制素材文件: '.$path);
                foreach ($materials as $material) {
                    $path = $material['path'];
                    $fullPath = $material['full_path'];
                    $truePath = str_replace($originalPath, 'importTemplate/'.$template->tag.'/modules', $fullPath);
                    $path = str_replace($originalPath, $newBasePath, $path);
                    $fullPath = str_replace($originalPath, $newBasePath, $fullPath);

                    $materialData = [
                        'template_id' => $templateId,
                        'module_id' => $newModule->id,
                        'type' => $material['type'],
                        'path' => $path,
                        'file_name' => $material['file_name'],
                        'full_path' => $fullPath
                    ];

                    TemplateMaterial::create($materialData);
    
                    // 3.2 复制文件
                    if (Storage::disk('public')->exists($truePath) &&
                        !Storage::disk('public')->exists($fullPath)
                    ) {
                        Storage::disk('public')->copy($truePath, $fullPath);
                    }
                }
                common_log('素材文件复制完成: '.$path);

                self::insertAllModules($template, $modules, $key, $newModule->id);
            }
        }
    }

    /**
     * 插入全部物料库分类数据
     *
     * @param [type] $template
     * @param [type] $contentCategories
     * @param integer $parentId
     * @param integer $newParentId
     * @return void
     */
    public static function insertAllContentCategories(
        $template, $contentCategories,
        $parentId=0, $newParentId=0
    ) {
        $categoryId = $template->category_id;
        $groupId = $template->group_id;
        $typeData = ContentService::CONTENT_TYPE;
        $typeData = array_flip($typeData);

        static $categoryFileData = [
            'category' => [],
            'file' => [],
        ];

        foreach ($contentCategories as $key => $data) {
            if ($data['parent_id'] == $parentId) {
                $data['parent_id'] = $newParentId;
                $data['category_id'] = $categoryId;
                $data['group_id'] = $groupId;
                $files = $data['files'];
                unset($data['files']);

                // 判断相同分组下是否存在同名分类标签
                $categories = ContentCategory::where([
                    'group_id' => $groupId,
                    'name' => $data['name'],
                    'type' => $data['type'],
                ])->get();
                if (!$categories->isEmpty()) {
                    // 如果存在, 则删除原分类
                    foreach ($categories as $category) {
                        $category->delete();
                        common_log('删除原同名分类: '.$category->name);
                    }
                }

                $newCategory = ContentCategory::create($data);
                $newCategoryId = $newCategory->id ?? 0;

                $categoryFileData['category'][$key] = $newCategoryId;

                common_log('开始复制物料库分类数据: '.$data['name']);
                foreach ($files as $file) {
                    $file['category_id'] = $newCategoryId;
                    $file['type'] = $typeData[$file['type']] ?? '';
                    $fileId = $file['id'] ?? 0;
                    unset($file['id']);

                    $newFile = File::create($file);
                    $path = $newFile['path'];
                    $newFileId = $newFile['id'] ?? 0;

                    $categoryFileData['file'][$fileId] = $newFileId;
                    $truePath = str_replace_once('files', 'importTemplate/'.$template->tag.'/files', $path);
                    if (Storage::disk('public')->exists($truePath) &&
                        !Storage::disk('admin')->exists($path)
                    ) {
                        $fileContent = Storage::disk('public')->get($truePath);

                        Storage::disk('admin')->put($path, $fileContent);
                    }
                }
                common_log('复制物料库分类数据完成: '.$data['name']);

                self::insertAllContentCategories($template, $contentCategories, $key, $newCategoryId);
            }
        }

        return $categoryFileData;
    }

    /**
     * 插入所有物料库内容
     *
     * @param [type] $template
     * @param [type] $contentData
     * @param [type] $categoryFileData
     * @return void
     */
    public static function insertAllContent($template, $contentData, $categoryFileData)
    {
        $groupId = $template->group_id;
        $fileIds = $categoryFileData['file'] ?? [];
        $categoryIds = $categoryFileData['category'] ?? [];
        $models = ContentService::CONTENT_MODEL;
        foreach ($contentData as $key => $content) {
            common_log('开始插入物料库内容: '.$key);

            $model = $models[$key] ?? '';
            if (empty($model)) {
                continue;
            }

            foreach ($content as $val) {
                if (!isset($fileIds[$val['file_id']]) ||
                    !isset($categoryIds[$val['category_id']])
                ) {
                    continue;
                }
                $originCategoryId = $val['category_id'];
                $val['file_id'] = $fileIds[$val['file_id']];
                $val['category_id'] = $categoryIds[$val['category_id']];

                if ($key == 'image') {
                    $path = $val['url'];
                    $exportPath = str_replace_once('seo/'.$originCategoryId, 'importTemplate/'.$template->tag.'/files', $path);
                    $newPath = str_replace_once('seo/'.$originCategoryId, 'seo/'.$groupId, $path);
                    if ($exportPath == $path) {
                        $exportPath = str_replace_once('seo', 'importTemplate/'.$template->tag.'/files', $path);
                    }
                    if ($newPath == $path) {
                        $newPath = str_replace_once('seo', 'seo/'.$groupId, $path);
                    }

                    $val['url'] = $newPath;
                    // 将图片文件复制到新路径
                    if (Storage::disk('public')->exists($exportPath) &&
                        !Storage::disk('admin')->exists($newPath)
                    ) {
                        $contentVal = Storage::disk('public')->get($exportPath);

                        Storage::disk('admin')->put($newPath, $contentVal);
                        common_log('复制图片内容数据成功: '.$path);
                    }
                }

                $model::create($val);
            }
            common_log('插入物料库内容完成: '.$key);
        }
    }
}
