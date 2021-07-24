<?php

namespace App\Services;

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
use Illuminate\Http\UploadedFile;
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
        'website_name' => 'App\Models\WebsiteName',
        'column' => 'App\Models\Column',
        'sentence' => 'App\Models\Sentence',
        'image' => 'App\Models\Image',
        'video' => 'App\Models\Video',
        'keyword' => 'App\Models\Keyword',
        'article' => 'App\Models\Article',
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

        // 如果数据为空, 则直接返回成功
        if (empty($fileData)) {
            return self::success();
        }

        // 保存文件
        $filePath = 'files/'.date('Y/m/d');

        $path = $file->storePubliclyAs($filePath, Str::random(40).'.txt');

        $fileInsertData = [
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'ext' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
            'rows' => count($fileData),
            'type' => $type,
            'category_id' => $categoryId,
        ];

        $fileObj = File::create($fileInsertData);

        // ProcessAddContent::dispatch(compact('categoryId', 'type', 'fileObj', 'fileData', 'tagId'));
        ProcessAddContent::dispatch(compact('categoryId', 'type', 'fileObj', 'fileData'));

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
        File $fileObj,
        array $fileData
    ) {
        $sum = 0;
        $successCount = 0;
        $failCount = 0;

        $baseData = [
            'category_id' => $categoryId,
            'file_id' => $fileObj->id,
            'is_collected' => 0,
        ];
        if ($type == 'article') {
            // 批量处理数据
            foreach ($fileData as $val) {
                try {
                    if (empty($val) || $val == "\r\n") {
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

                    $tag = self::contentTag($categoryId, 'article');

                    $insertData = array_merge(
                        $baseData,
                        compact('title', 'image', 'content', 'tag')
                    );

                    Article::create($insertData);

                    $successCount++;
                } catch (Exception $e) {
                    $failCount++;

                    // common_log('批量上传文章失败', $e);
                }
            }
        } else if ($type == 'image') {
            // 批量处理数据
            foreach ($fileData as $val) {
                try {
                    if (empty($val) || $val == "\r\n") {
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

                    $tag = self::contentTag($categoryId, 'image');

                    $insertData = array_merge($baseData, [
                        'url' => $val,
                        'tag' => $tag,
                    ]);

                    Image::create($insertData);

                    $successCount++;
                } catch (Exception $e) {
                    $failCount++;

                    // common_log('批量上传图片失败', $e);
                }
            }
        } else if ($type == 'title') {
            // 批量处理数据
            foreach ($fileData as $val) {
                try {
                    if (empty($val) || $val == "\r\n") {
                        continue;
                    }
                    $sum++;

                    $tag = self::contentTag($categoryId, 'title');

                    $insertData = array_merge($baseData, [
                        'content' => trim($val),
                        'tag' => $tag
                    ]);

                    Title::create($insertData);

                    $successCount++;
                } catch (Exception $e) {
                    $failCount++;

                    // common_log('批量上传标题失败', $e);
                }
            }
        } else if ($type == 'website_name') {
            // 批量处理数据
            foreach ($fileData as $val) {
                try {
                    if (empty($val) || $val == "\r\n") {
                        continue;
                    }
                    $sum++;

                    $tag = self::contentTag($categoryId, 'website_name');

                    $insertData = array_merge($baseData, [
                        'content' => trim($val),
                        'tag' => $tag,
                    ]);

                    WebsiteName::create($insertData);

                    $successCount++;
                } catch (Exception $e) {
                    $failCount++;

                    // common_log('批量上网站名称失败', $e);
                }
            }
        } else if ($type == 'column') {
            // 批量处理数据
            foreach ($fileData as $val) {
                try {
                    if (empty($val) || $val == "\r\n") {
                        continue;
                    }
                    $sum++;

                    $tag = self::contentTag($categoryId, 'column');

                    $insertData = array_merge($baseData, [
                        'content' => trim($val),
                        'tag' => $tag,
                    ]);

                    Column::create($insertData);

                    $successCount++;
                } catch (Exception $e) {
                    $failCount++;

                    // common_log('批量上传栏目失败', $e);
                }
            }
        } else if ($type == 'sentence') {
            // 批量处理数据
            foreach ($fileData as $val) {
                try {
                    if (empty($val) || $val == "\r\n") {
                        continue;
                    }
                    $sum++;

                    $tag = self::contentTag($categoryId, 'sentence');

                    $insertData = array_merge($baseData, [
                        'content' => trim($val),
                        'tag' => $tag,
                    ]);

                    Sentence::create($insertData);

                    $successCount++;
                } catch (Exception $e) {
                    $failCount++;

                    // common_log('批量上传句子失败', $e);
                }
            }
        } else if ($type == 'video') {
            // 批量处理数据
            foreach ($fileData as $val) {
                try {
                    if (empty($val) ||
                        $val == "\r\n" ||
                        (strpos($val, 'http://') !== 0 &&
                        strpos($val, 'https://') !== 0)
                    ) {
                        continue;
                    }
                    $sum++;

                    $tag = self::contentTag($categoryId, 'video');

                    $insertData = array_merge($baseData, [
                        'url' => trim($val),
                        'tag' => $tag,
                    ]);

                    Video::create($insertData);

                    $successCount++;
                } catch (Exception $e) {
                    $failCount++;

                    // common_log('批量上传视频失败', $e);
                }
            }
        } else if ($type == 'diy') {
            // 批量处理数据
            foreach ($fileData as $val) {
                try {
                    if (empty($val) || $val == "\r\n") {
                        continue;
                    }
                    $sum++;

                    $tag = self::contentTag($categoryId, 'diy');

                    $insertData = [
                        'content' => trim($val),
                        'tag' => $tag,
                        'file_id' => $fileObj->id,
                        'is_collected' => 0,
                        'category_id' => $categoryId,
                    ];

                    Diy::create($insertData);

                    $successCount++;
                } catch (Exception $e) {
                    $failCount++;

                    // common_log('批量上传自定义失败', $e);
                }
            }
        } else if ($type == 'keyword') {
            // 批量处理数据
            foreach ($fileData as $val) {
                try {
                    if (empty($val) || $val == "\r\n") {
                        continue;
                    }
                    $sum++;

                    $tag = self::contentTag($categoryId, 'keyword');
                    unset($baseData['is_collected']);

                    $insertData = array_merge($baseData, [
                        'content' => trim($val),
                        'tag' => $tag
                    ]);

                    Keyword::create($insertData);

                    $successCount++;
                } catch (Exception $e) {
                    $failCount++;

                    // common_log('批量上传关键词失败', $e);
                }
            }
        }

        $message = "导入完成, 导入文件ID为: {$fileObj->id}, 总数据量为{$sum}条, 成功{$successCount}条, 失败{$failCount}条";

        common_log($message);
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
        $category = ContentCategory::find($categoryId);

        $categoryName = $category->name ?? '';

        $typeName = self::CONTENT_TAG[$type] ?? '';

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
        ]);

        $sum = 0;
        $successCount = 0;
        $failCount = 0;
        $urlArr = [];
        
        foreach ($files as $file) {
            try {
                $sum++;
                $val = $file->store('seo/'.date('Y/m/d'));
                $url = Storage::url($val);

                Storage::append($filePath, $url);
        
                $tag = self::contentTag($categoryId, 'image');
        
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
        $fileObj->size = Storage::size($filePath);
        $fileObj->rows = $sum;
        $fileObj->save();

        $message = "导入完成, 总数据量为{$sum}条, 成功{$successCount}条, 失败{$failCount}条";

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

    public static function getRandKeywordATag()
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

        // $categoryIds = $website->category->contentCategories()->pluck('id')->toArray();
        $webCategoryId = CommonService::getCategoryId();
        $categoryIds = ContentCategory::where('category_id', $webCategoryId)->pluck('id')->toArray();
        $idsStr = implode(',', $categoryIds);

        $where = " where category_id in ({$idsStr})";

        $keyword = Keyword::join(
            DB::raw("
                (
                    SELECT ROUND(
                        RAND() * (
                            (SELECT MAX(id) FROM `keywords`{$where})-(SELECT MIN(id) FROM `keywords`{$where})
                        )+(
                            SELECT MIN(id) FROM `keywords`{$where}
                        )
                    ) AS xid
                ) as t2
            "), 
            'keywords.id', '>=', 't2.xid' 
        )->first();

        $keywordContent = $keyword->content ?? '';

        $aTag = <<<HTML
<a href="$newUrl">$keywordContent</a>
HTML;

        return $aTag;
    }
}
