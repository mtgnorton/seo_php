<?php

namespace App\Services;

use App\Admin\Forms\Spider;
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
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
    const BASE_CLUMN_PATH = [
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
        $result = TemplateModel::where($condition)
                        ->pluck('name', 'id');

        return $result->isEmpty() ?
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
            '头部' => 'base/head',
            '尾部' => 'base/foot',
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
        
                $fileResult = Storage::disk('public')->putFileAs($path, $file, $fileName);
        
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
                    Storage::disk('public')->delete($fileResult);
        
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
    public static function addModule(int $templateId, string $columnName, string $columnTag)
    {
        // 获取模型
        $template = TemplateModel::find($templateId);

        // 去除栏目两端的 / 
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
            $baseClumnPath = self::BASE_CLUMN_PATH;

            foreach ($baseClumnPath as $key => $columnPath) {
                $routeName = $columnName . $columnPath;
                $routeTag = '/'.$columnTag.'/'.$key.'/';
                TemplateModule::create([
                    'template_id' => $templateId,
                    'column_name' => $columnName,
                    'column_tag' => $columnTag,
                    'route_name' => $columnName . $columnPath,
                    'route_tag' => $routeTag,
                    'path' => $basePath . $routeTag,
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
            return '';
        }

        $spider = SpiderService::getSpider();

        if (empty($spider) || $spider === 'other') {
            $tag = 'common';
        } else {
            $tag = $spider;
        }
        
        // 判断该标签下是否有对应模板
        $templates = Template::where([
                    'type_tag' => $tag,
                    'group_id' => $groupId,
                ])->get()
                ->toArray();

        if (!empty($templates)) {
            $template = $templates[0];

            return $template['id'];
        }

        // 判断通用模板是否有值
        $templates = Template::where([
                    'type_tag' => 'common',
                    'group_id' => $groupId
                ])->get()
                ->toArray();

        if (!empty($templates)) {
            $template = $templates[0];

            return $template['id'];
        }

        // 判断所有模板是否有值
        $templates = Template::where([
                        'group_id' => $groupId
                    ])->get()
                    ->toArray();

        if (!empty($templates)) {
            $template = $templates[0];

            return $template['id'];
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
        $hostData = compact('host', 'newHost', 'wwwNewHost');
        $groupIds = Website::whereIn('url', $hostData)
                            ->pluck('group_id')
                            ->toArray();

        if (empty($groupIds)) {
            $groupId =  0;
        } else {
            $groupId = $groupIds[0];
        }

        return $groupId;
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
            $originalPages = $originalTemplate->pages;
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
                if (!Storage::disk('public')->exists($materialFullPath)) {
                    Storage::disk('public')->copy($originalMaterial->full_path, $materialFullPath);
                }
            }

            // 5. 绑定域名
            $originalWebsites = Website::where('group_id', $originalTemplate->group_Id)->get();
            // $websiteContrastData = [];
            foreach ($originalWebsites as $originalWebsite) {
                $websiteData = [
                    'name' => $originalWebsite->name,
                    'url' => $originalWebsite->url,
                    'category_id' => $template->category_id,
                    'group_id' => $template->group_id,
                    'is_enabled' => $originalWebsite->is_enabled,
                ];
                Website::create($websiteData);

                // $websiteContrastData[$originalWebsite->id] = $website->id;
            }
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
            $route_tag = '/base/head';
        } else if ($tag == '尾部标签') {
            $route_tag = '/base/foot';
        } else {
            return '';
        }

        $module = TemplateModule::where([
            'template_id' => $templateId,
            'route_tag' => $route_tag
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
}
