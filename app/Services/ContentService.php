<?php

namespace App\Services;

use App\Constants\ContentConstant;
use App\Constants\RedisCacheKeyConstant;
use App\Jobs\ProcessAddContent;
use App\Models\File;
use Exception;
use App\Models\Article;
use App\Models\Column;
use App\Models\ContentCategory;
use App\Models\Diy;
use App\Models\Image;
use App\Models\Keyword;
use App\Models\Sentence;
use App\Models\Tag;
use App\Models\Title;
use App\Models\Video;
use App\Models\Website;
use App\Models\WebsiteName;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 内容服务类
 *
 * Class ContentService
 * @package App\Services
 */
class ContentService extends BaseService
{
    /**
     * @var 图片上传类型
     */
    const IMAGE_TYPE = [
        'local' => '本地上传',
        'link' => '外部链接'
    ];

    /**
     * @var 内容库类型
     */
    const CONTENT_TYPE = [
        'article' => '文章库',
        'title' => '标题库',
        'website_name' => '网站名称库',
        'column' => '栏目库',
        'sentence' => '句子库',
        'image' => '图片库',
        'video' => '视频库',
        'keyword' => '关键词库',
        'diy' => '自定义库',
    ];
    /**
     * 内容模型
     *
     * @var array
     */
    const CONTENT_TABLE = [
        'title' => 'titles',
        'website_name' => 'website_names',
        'column' => 'columns',
        'sentence' => 'sentences',
        'image' => 'images',
        'video' => 'videos',
        'keyword' => 'keywords',
        'article' => 'articles',
        'diy' => 'diys',
    ];

    const CONTENT_TAG = [
        'article' => '文章',
        'title' => '标题',
        'website_name' => '网站名称',
        'column' => '栏目',
        'sentence' => '句子',
        'image' => '图片',
        'video' => '视频',
        'keyword' => '关键词',
        'diy' => '',
    ];

    /**
     * 内容模型
     * 
     * @var array
     */
    const CONTENT_MODEL = [
        'title' => 'App\Models\Title',
        // 'website_name' => 'App\Models\WebsiteName',
        'column' => 'App\Models\Column',
        'sentence' => 'App\Models\Sentence',
        'image' => 'App\Models\Image',
        'video' => 'App\Models\Video',
        'keyword' => 'App\Models\Keyword',
        'article' => 'App\Models\Article',
        'diy' => 'App\Models\Diy',
    ];

    /**
     * 结构相同的内容库烈性
     */
    const CONTENT_SAME = [
        'title',
        'column',
        'website_name',
        'sentence',
        'keyword',
        'diy',
        'video'
    ];

    /**
     * @var 标签类型
     */
    const TAG_TYPE = [
        'system' => '系统标签',
        'diy' => '自定义标签'
    ];

    /**
     * 根据上传文件内容获取对应数据, 并写入数据库
     *
     * @param $file
     * @param string $type
     * @param int $categoryId
     * @return array
     */
    public static function import($file, $type='', $categoryId = 0)
    {
        // 判断文件是否存在
        if (!file_exists($file)) {
            return self::error(ll('File does not exist'));
        }
        // 判断是否是文件类型
        if (!$file instanceof UploadedFile) {
            return self::error(ll('File uploaded failed'));
        }
        // 判断文件类型是否为txt
        if ($file->getClientOriginalExtension() != 'txt') {
            return self::error(ll('Only txt file'));
        }
        // 判断类型是否为空
        if (empty($type)) {
            return self::error(ll('Type cannot be empty'));
        }
        // 判断分类是否为空
        if (empty($categoryId)) {
            return self::error(ll('Category cannot be empty'));
        }

        // 获取文件数据, 每行一条数据
        $fileData = file($file);
        $content = file_get_contents($file);
        $contentU = CommonService::changeCharset2Utf8($content);
        $fileData = CommonService::linefeedStringToArray($contentU);

        // 如果数据为空, 则直接返回成功
        if (empty($fileData)) {
            return self::success();
        }

        // 保存文件
        $filePath = 'files/'.date('Y/m/d/') . Str::random(40).'.txt';

        // $path = $file->storePubliclyAs($filePath, Str::random(40).'.txt');
        $path = Storage::disk('admin')->put($filePath, $contentU);
        if (!$path) {
            return self::error('文件上传失败');
        }

        $fileInsertData = [
            'name' => $file->getClientOriginalName(),
            'path' => $filePath,
            'ext' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
            'rows' => count($fileData),
            'type' => $type,
            'category_id' => $categoryId,
            'message' => '',
        ];

        $fileObj = File::create($fileInsertData);

        // ProcessAddContent::dispatch(compact('categoryId', 'type', 'fileObj', 'fileData', 'tagId'));
        ProcessAddContent::dispatch(compact('categoryId', 'type', 'fileObj'));

        // dd(compact('categoryId', 'type', 'fileObj', 'fileData', 'tagId'));
        // self::insertContentByFile($categoryId, $type, $fileObj, $fileData, $tagId);

        return self::success([], '导入成功');
    }

    /**
     * 根据文件写入内容库
     *
     * @param integer $categoryId   分类ID
     * @param string $type          类型
     * @param File $fileObj         文件对象
     * @param array $fileData       文件数据
     * @return void
     */
    public static function insertContentByFile(
        int $categoryId,
        string $type,
        File $fileObj
    ) {
        $fullPath = $fileObj->path;
        if (!Storage::disk('admin')->exists($fullPath)) {
            common_log('获取内容库文件失败');
            
            return false;
        } 
        $data = Storage::disk('admin')->get($fullPath);
        $contentU = CommonService::changeCharset2Utf8($data);
        $fileData = CommonService::linefeedStringToArray($contentU);

        $sum = 0;
        $successCount = 0;
        $failCount = 0;
        $model = IndexPregService::CONTENT_MODEL[$type];
        $table = self::CONTENT_TABLE[$type];
        $column = IndexPregService::CONTENT_COLUMN[$type];
        $sames = self::CONTENT_SAME;
        $insertNum = 500;
        static $allData = [];
        // if (in_array($type, ['image', 'sentence'])) {
        //     $insertNum = 10;
        // } else if (in_array($type, ['article'])) {
        //     $insertNum = 1;
        // }
        $insertKey = RedisCacheKeyConstant::CACHE_CONTENT_INSERT_KEY;
        $now = Carbon::now()->toDateTimeString();

        $baseData = [
            'category_id' => $categoryId,
            'file_id' => $fileObj->id,
            // 'is_collected' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        // 去重, 并记录重复数量
        $beforeCount = count($fileData);
        $fileData = array_unique($fileData);
        $afterCount = count($fileData);
        $failCount += ($beforeCount - $afterCount);
        $sum += ($beforeCount - $afterCount);
        $tag = self::contentTag($categoryId, $type);

        foreach ($fileData as $val) {
            if ($type == 'article') {
                // 批量处理数据
                $val = trim($val);
                if (empty($val)) {
                    continue;
                }
                $sum++;

                $data = explode('******', $val);
                [$title, $image, $content] = array_pad($data, 3, '');

                // 去除内容首尾的空白
                $content = trim($content);
                $image = trim($image);

                // 如果内容中不含标签或者不以<开头, 则在最外层包裹div标签
                if ($content[0] != '<' ||
                    $content == strip_tags($content)
                ) {
                    $content = '<div>' . $content . '</div>';
                }

                if (!empty($image)) {
                    $imageFile = get_image_file_by_url($image);

                    if ($imageFile instanceof UploadedFile) {
                        $image = $imageFile->store('images/'.date('Y/m/d'));
                    } else {
                        $image = '';
                    }
                }

                // $tag = self::contentTag($categoryId, 'article');

                $insertData = array_merge(
                    $baseData,
                    compact('title', 'image', 'content', 'tag')
                );
            } else if ($type == 'image') {
                // 批量处理数据
                $val = trim($val);
                if (empty($val)) {
                    continue;
                }
                $sum++;

                // 去除换行和空格
                $val = trim($val);

                $imageFile = get_image_file_by_url($val);
                if ($imageFile instanceof UploadedFile) {
                    $val = $imageFile->store('seo/'.date('Y/m/d'));
                } else {
                    $failCount++;
                    
                    continue;
                }

                // $tag = self::contentTag($categoryId, 'image');

                $insertData = array_merge($baseData, [
                    'url' => $val,
                    'tag' => $tag,
                ]);
            } else if (in_array($type, $sames)) {
                // 批量处理数据
                $val = trim($val);
                if (empty($val)) {
                    continue;
                }
                if ($type == 'video' &&
                    (strpos($val, 'http://') !== 0 &&
                    strpos($val, 'https://') !== 0)
                ) {
                    continue;
                }
                $sum++;

                // $tag = self::contentTag($categoryId, $type);
                $insertData = array_merge($baseData, [
                    $column => $val,
                    'tag' => $tag,
                ]);
            }

            // $allData = Cache::get($insertKey, []);
            $allData[] = $insertData;
            $count = count($allData);
            if ($count < $insertNum) {
                // Cache::put($insertKey, $allData, 3600);
                continue;
            }

            try {
                $insertCount = DB::table($table)->insertOrIgnore($allData);

                $successCount += $insertCount;
                $failCount += ($count - $insertCount);
                // Cache::forget($insertKey);
            } catch (Exception $e) {
                $failCount += $count;
                // Cache::forget($insertKey);
            }
            $allData = [];
        }
        // 判断是否有残存未插入数据, 若有, 则将剩余数据插入
        // $allData = Cache::get($insertKey, []);
        if (!empty($allData)) {
            $count = count($allData);
            try {
                $insertCount = DB::table($table)->insertOrIgnore($allData);

                $successCount += $insertCount;
                $failCount += ($count - $insertCount);
                // Cache::forget($insertKey);
            } catch (Exception $e) {
                $failCount += $count;
                // Cache::forget($insertKey);
            }
            $allData = [];
        }

        $message = "导入完成, 总数据量为{$sum}条, 成功{$successCount}条, 失败/重复{$failCount}条";

        $fileObj->success_rows = $successCount;
        $fileObj->message = $message;
        $fileObj->save();

        $message .= ", 导入文件ID为: {$fileObj->id}";

        common_log($message);

        // 清空当前分类下的内容库缓存
        $baseKey = ContentConstant::cacheKeyText()[$type] ?? '';
        $category = ContentCategory::find($categoryId);
        $groupId = $category->group_id ?? 0;

        // 完整标签key
        $key1 = $baseKey . $groupId . $tag;
        Cache::store('file')->forget($key1);
        // 上级分类key
        $parentTag = self::contentTag($category->parent_id, $type);
        $key2 = $baseKey . $groupId . $parentTag;
        Cache::store('file')->forget($key2);
        if ($type != 'diy') {
            // 分类标签key
            $typeName = self::CONTENT_TAG[$type] ?? '';
            $key3 = $baseKey . $groupId . $typeName;
            Cache::store('file')->forget($key3);
        }
    }

    /**
     * 将分类数据拼成下拉框需要的格式
     *
     * @return void
     */
    public static function categoryOptions($condition=[])
    {
        $result = ContentCategory::where($condition)
                        ->pluck('name', 'id');

        return $result->isEmpty() ?
                    [0 => '暂无分类'] :
                    $result;
    }

    /**
     * 将分类数据拼成下拉框需要的格式
     *
     * @return void
     */
    public static function tagOptions($condition=[])
    {
        $result = Tag::where($condition)
                        ->pluck('name', 'id');

        return $result->isEmpty() ?
                    [0 => '暂无标签'] :
                    $result;
    }

    /**
     * 根据分类ID和类型生成对应的标签名称
     *
     * @param integer $categoryId   分类ID
     * @param string $type          类型
     * @return void
     */
    public static function contentTag(int $categoryId, string $type)
    {
        // $category = CommonService::contentCategories(['id' => $categoryId], 1);
        $typeName = self::CONTENT_TAG[$type] ?? '';

        $category = ContentCategory::find($categoryId);
        if (empty($category)) {
            // 获取缓存中是否有数据
            $key = RedisCacheKeyConstant::CACHE_DELETE_CONTENT_TEMPLATE . $categoryId;
            $category = Cache::get($key);
            if (empty($category)) {
                return $typeName;
            }
        }

        $categoryName = $category->name ?? '';

        return $categoryName . $typeName;
    }

    /**
     * 添加自定义标签
     *
     * @param string $tag   标签标识
     * @return void
     */
    public static function addDiyTag(string $tag, int $categoryId=0)
    {
        return Tag::create([
            'name' => $tag,
            'tag' => $tag,
            'identify' => '',
            'category_id' => $categoryId,
        ]);
    }

    /**
     * 根据标签获取对应的文件ID
     *
     * @param int $tagId
     * @return void
     */
    public static function getFileIdsByTag($tagId)
    {
        return Diy::where('tag_id', $tagId)
                    ->groupBy('file_id')
                    ->pluck('file_id');
    }

    /**
     * 根据标签获取对应的文件ID
     *
     * @param int $categoryId
     * @param string $type
     * @return void
     */
    public static function getFileIdsByCategory($categoryId, $type)
    {
        $model = self::CONTENT_MODEL[$type] ?? '';
        if (empty($model)) {
            return [];
        }

        return $model::where('category_id', $categoryId)
                ->groupBy('file_id')
                ->pluck('file_id');
    }

    /**
     * 获取自定义分类页面操作
     *
     * @param Tag $tag  标签对象
     * @return void
     */
    public static function getDiyTagOption(Tag $tag)
    {
        // 查看文件
        $tagId = $tag->id;
        $url = '/admin/files?tag_id='.$tagId.'&type=diy';
        $result = $tag->only(['name', 'tag']);
        $csrfToken = csrf_token();
        // 上传文件
        $a = <<<HTML

        <a data-toggle="modal" id="filebutton{$tagId}" style="cursor:pointer; margin-left: 10px">上传文件</a>
        <!-- 模态框（Modal） -->
        <div class="modal fade" id="myModal{$tagId}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title" id="myModalLabel">上传文件</h4>
                    </div>
                    <div class="modal-body">
                        <form id="uploadFormDiy{$tagId}" enctype="multipart/form-data">
                            <input type="hidden" name="tag_id" value="{$tagId}">
                            <label for="">请上传文件</label>
                            <input id="file" type="file" name="file"/> 
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                        <button type="button" class="btn btn-primary" id="diy-submit{$tagId}">提交更改</button>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal -->
        </div>
        <script>
        $(function () {
            $('#filebutton{$tagId}').on('click', function() {
                $('#myModal{$tagId}').modal('show');
            });
            $("#diy-submit{$tagId}").on('click', function () {
                var formData = new FormData($('#uploadFormDiy{$tagId}')[0]);
                $.ajax({
                    type : 'post',
                    url: '/admin/diy-categories/upload',
                    data: formData,
                    dataType: 'json',
                    cache: false, 
                    processData: false, 
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': "{$csrfToken}"
                    },
                }).success(function (data) {
                    if (data.code == 0) {
                        swal("Oops", "上传成功!", 'success')
                        .then((value) => {
                            $('#myModal{$tagId}').modal('hide');
                        });
                    }
                }).error(function (data) {
                    swal("Oops","上传失败","error");
                });
            });
        })
        </script>

HTML;
        $result['option'] = "<a href='".$url."'>查看文件</a>".$a;

        return $result;
    }

    /**
     * 导入本地图片
     *
     * @param [type] $files
     * @param [type] $categoryId
     * @return void
     */
    public static function importLocalImage($files, int $categoryId)
    {
        // 保存文件
        $fileName = Str::random(40).'.txt';
        $filePath = 'files/'.date('Y/m/d') . '/'. $fileName;

        $path = Storage::put($filePath, '');

        $fileObj = File::create([
            'name' => $fileName,
            'path' => $filePath,
            'ext' => 'txt',
            'size' => 0,
            'rows' => 0,
            'type' => 'image',
            'category_id' => $categoryId,
            'message' => '',
        ]);

        $sum = 0;
        $successCount = 0;
        $failCount = 0;
        $urlArr = [];
        
        $tag = self::contentTag($categoryId, 'image');
        
        foreach ($files as $file) {
            try {
                $sum++;
                $val = $file->store('seo/'.date('Y/m/d'));
                if (empty($val)) {
                    continue;
                }
                $url = Storage::url($val);

                Storage::append($filePath, $url);
        
                $insertData = array_merge([
                    'category_id' => $categoryId,
                    'file_id' => $fileObj->id,
                    'is_collected' => 0,
                    'url' => $val,
                    'tag' => $tag,
                ]);

                Image::create($insertData);

                $successCount++;
            } catch (Exception $e) {
                $failCount++;

                common_log('本地上传图片失败', $e);
            }
        }
        $message = "导入完成, 总数据量为{$sum}条, 成功{$successCount}条, 失败{$failCount}条";

        $fileObj->size = Storage::size($filePath);
        $fileObj->rows = $sum;
        $fileObj->message = $message;
        $fileObj->save();

        // 清空当前分类下的内容库缓存
        $baseKey = ContentConstant::cacheKeyText()['image'] ?? '';
        $category = ContentCategory::find($categoryId);
        $groupId = $category->group_id;

        $key = $baseKey . $groupId . $tag;
        Cache::store('file')->forget($key);

        return self::success([], $message);
    }

    /**
     * 获取系统干扰模板
     *
     * @param int $count    内容循环数量
     * @return void
     */
    public static function getTemplateDisturb($count = 5)
    {
        $content = '';
        for ($i=0; $i<=$count; $i++) {
            $content .= <<<HTML
<pre id="{随机数字}" style="display:none;">
    <p id="{随机数字}">
        <span id="{随机数字}"></span>
    </p>
    <p id="{随机字母}">
        <span id="{随机字母}"></span>
    </p>
    <p id="{随机字母}">
        <span id="{随机字母}"></span>
    </p>
    <p id="{随机数字}">
        <span id="{随机数字}"></span>
    </p>
    <p id="{随机数字}">
        <span id="{随机数字}"></span>
    </p>
    <p id="{随机数字}">
        <span id="{随机数字}"></span>
    </p>
    <p id="{随机字母}">
        <span id="{随机字母}"></span>
    </p>
</pre>
HTML;
        }

        return $content;
    }

    /**
     * 获取禁止抓取快照内容
     *
     * @return void
     */
    public static function getForbinSnapshotContent()
    {
        $content = <<<HTML
<meta content="noarchive" name="Baiduspider" />
HTML;

        return $content;
    }

    /**
     * 获取随机关键词
     *
     * @param integer $groupId
     * @param array $condition
     * @return void
     */
    public static function getRandKeywordATag($groupId=0, $condition=[])
    {
        // 获取当前域名信息
        // $host = $_SERVER["HTTP_HOST"];

        // // 根据host的值判断是否已绑定域名
        // $website = Website::where([
        //     'url' => $host,
        //     'is_enabled' => 1
        // ])->first();

        $nowUrl = request()->url();
        $urlArr = explode('/', $nowUrl);
        array_pop($urlArr);
        $newUrl = implode('/', $urlArr) . '/' . mt_rand(10000, 99999) . '.html';

        // // $categoryIds = $website->category->contentCategories()->pluck('id')->toArray();
        // $webCategoryId = CommonService::getCategoryId();
        // // $categoryIds = ContentCategory::where('category_id', $webCategoryId)->pluck('id')->toArray();
        // $categoryIds = CommonService::contentCategories(['category_id' => $webCategoryId], 0, 'id');
        // $idsStr = implode(',', $categoryIds);

        // $where = " where category_id in ({$idsStr})";

        // $keyword = Keyword::join(
        //     DB::raw("
        //         (
        //             SELECT ROUND(
        //                 RAND() * (
        //                     (SELECT MAX(id) FROM `keywords`{$where})-(SELECT MIN(id) FROM `keywords`{$where})
        //                 )+(
        //                     SELECT MIN(id) FROM `keywords`{$where}
        //                 )
        //             ) AS xid
        //         ) as t2
        //     "), 
        //     'keywords.id', '>=', 't2.xid' 
        // )->first();

        // $keywordContent = $keyword->content ?? '';
        $keywordData = CommonService::contents('keyword', '关键词', 'App\Models\Keyword', $groupId, 'content', $condition, [], 0, ['content', 'id']);
        
        $data = array_flip($keywordData);
        $keywordContent = '';
        if (!empty($data)) {
            $keywordContent = array_rand($data);
        }
        // $keywordContent = $keywordData[$id];

        $aTag = <<<HTML
<a href="$newUrl">$keywordContent</a>
HTML;

        return $aTag;
    }

    /**
     * 将摘要写入缓存中
     *
     * @param string $baseUrl       基本地址
     * @param string $value         值
     * @param integer $cacheTime    缓存时间
     * @return void
     */
    public static function putSummary($result, &$globalData)
    {
        if (!isset($globalData['summary_data'])) {
            // 获取内容中的p标签内容
            $nodeValues = CommonService::getHtmlContent($result);
            $nodeValues = implode('', $nodeValues);
    
            $summaryLength = mt_rand(50, 60);
            $summary = mb_substr($nodeValues, 0, $summaryLength);
    
            $globalData['summary_data'] = $summary;
        }
    }

    /**
     * 将摘要写入缓存中
     *
     * @param string $baseUrl       基本地址
     * @param string $value         值
     * @param integer $cacheTime    缓存时间
     * @return void
     */
    public static function putSentenceSummary($result, &$globalData)
    {
        static $summaryLength = 0;
        if (empty($summaryLength)) {
            $summaryLength = mt_rand(50, 60);
        }
        if (isset($globalData['summary_data'])) {
            $oldSummary = $globalData['summary_data'];
            // 判断已存的摘要长度是否足够
            $length = mb_strlen($oldSummary);
            if ($length < $summaryLength) {
                // 判断加上当前句子后的长度是否足够摘要长度
                $nowLength = mb_strlen($result);
                if ($nowLength + $length > $summaryLength) {
                    $newSummary = mb_substr($result, 0, $summaryLength - $length);
                    $summary = $oldSummary . $newSummary;
                } else {
                    $summary = $oldSummary . $result;
                }
                $globalData['summary_data'] = $summary;
            }
        } else {
            // 判断当前句子的长度是否足够
            $length = mb_strlen($result);
            if ($length >= $summaryLength) {
                // 截取摘要长度的字符串放进句子中
                $summary = mb_substr($result, 0, $summaryLength);
            } else {
                $summary = $result;
            }
            $globalData['summary_data'] = $summary;
        }
    }
}
