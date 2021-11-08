<?php


namespace App\Services;


use App\Constants\FakeOriginConstants;
use App\Constants\GatherConstant;
use App\Models\ContentCategory;
use App\Models\GatherCrontabLog;
use App\Models\CollectedUrl;
use App\Models\File;
use App\Models\Gather;
use App\Services\Gather\CrawlService;
use Carbon\Carbon;
use Encore\Admin\Actions\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


/**
 * author: mtg
 * time: 2021/6/5   16:39
 * class description: 业务相关,根据具体业务的采集规则抓取
 * @package App\Services\Gather
 */
class GatherService
{

    protected $crontabID = 0;

    protected $requestAmount = 0;

    protected $contentAmount = 0;


    protected $maxContentAmount = 10;

    protected $maxRequestAmount = 10;

    protected $model;

    protected $dynamicURLs = []; //等待爬取的url

    protected $hasRequestURLs = []; //已经爬取的url


    protected $gatherLogBuffer = [];


    public function __construct($model = null)
    {

        $this->model = $model;
    }

    /**
     * author: mtg
     * time: 2021/6/5   16:42
     * function description:根据采集规则开始动态抓取标题,句子,文章
     * @param Gather $model
     */
    public function dynamic(Gather $model, int $maxContentAmount = 100, $maxRequestAmount = 10, $gatherRequestTimeInterval = 1, $maxRequestTime = 5, $crontabID = 0)
    {


        $this->model            = $model;
        $this->maxContentAmount = $maxContentAmount;
        $this->crontabID        = $crontabID;
        CrawlService::setOptions(CrawlService::getDomain($model->begin_url), [
            'CURLOPT_TIMEOUT' => $maxRequestTime
        ])->setOptionsByModel($model);


        $this->dynamicURLs = CrawlService::parseURLs($model->begin_url);
        $this->dynamicURLs = array_reverse($this->dynamicURLs);

        $this->notify('', sprintf('待采集的链接数量为%s', count($this->dynamicURLs)), 'info');

        if (ContentCategory::where('id', $model->category_id)->doesntExist()) {
            $this->notify('分类不存在,请先设置分类', '提示', 'error');
        }

        while ($dynamicURL = array_shift($this->dynamicURLs)) {

            if (Cache::get('stop_gather')) {
                $this->notify('停止采集');
                break;
            }

            if (array_search($dynamicURL, $this->hasRequestURLs) !== false) {
                gather_log(sprintf('url%s已经请求过,跳过', $dynamicURL));
                continue;
            }
            //   gather_log(sprintf('------请求url%s开始-------', $dynamicURL));
            try {

                $res = CrawlService::get($dynamicURL);

            } catch (\Exception $e) {
                $this->notify(sprintf("抓取链接为:%s失败,失败原因为%s,该链接跳过", $dynamicURL, $e->getMessage()));
                continue;
            }

            /*编码转换*/
            if (is_gbk_html($res)) {
                $res = iconv("gbk", "utf-8//IGNORE", $res);
            }

            /* 抓取匹配内容 */
            $data = $this->gatherRegularContentsAndProcess($res);

            $articleTitles = collect([]);
            //文章会有对应的标题
            if ($model->type == GatherConstant::TYPE_ARTICLE) {
                $articleTitles = $this->extractArticleTitles($res);

            }


            $inserts = [];
            /*写入数据库*/
            foreach ($data as $order => $value) {

                $value = trim($value);
                list($isInsert, $value) = $this->filter($value);

                if (!$isInsert) {
                    continue;
                }
                $insert = [
                    'content'      => $value,
                    'tag'          => $model->tag,
                    'category_id'  => $model->category_id,
                    'is_collected' => 1,
                    'file_id'      => 0,
                    'source_url'   => $dynamicURL,
                    'created_at'   => Carbon::now(),
                    'updated_at'   => Carbon::now()
                ];

                if ($model->type == GatherConstant::TYPE_IMAGE) {
                    unset($insert['content']);
                    $insert['url'] = $value;

                }
                if ($model->type == GatherConstant::TYPE_ARTICLE) {
                    $title = $articleTitles->get($order, $articleTitles->get(0, ''));
                    if (empty($title)) {
                        continue;
                    }

                    $insert['title'] = $title;
                }
                $inserts[] = $insert;
            }



            $actualInsertAmount = count($inserts) > 0 ? DB::table(Str::plural($model->storage_type))->insertOrIgnore($inserts) : 0;


            if ($actualInsertAmount > 0) {
                /*获取数据库中实际插入的内容写入到文件中*/
                $field         = $model->type == GatherConstant::TYPE_IMAGE ? 'url' : 'content';
                $actualInserts = DB::table(Str::plural($model->storage_type))->where('file_id', 0)->pluck($field);


                if ($model->type == GatherConstant::TYPE_ARTICLE) { //当为整篇文章时, 需要附带标题
                    $titles        = DB::table(Str::plural($model->storage_type))->where('file_id', 0)->pluck('title');
                    $actualInserts = collect($actualInserts)->map(function ($value, $key) use ($titles) {
                        $title = data_get($titles, $key, '');
                        return "[$title]" . "\r\n" . $value;
                    });

                }


                $writeFileContent = collect($actualInserts)->implode("\r\n");

                if ($model->type == GatherConstant::TYPE_IMAGE) { //文件中存储图片的url
                    $writeFileContent = collect($actualInserts)->map(function ($path) {
                        return Storage::url($path);
                    })->implode("\r\n");
                }


                /*写入文件*/
                $fileID = collect($actualInserts)->isNotEmpty() ? $this->writeFile(trim($writeFileContent)) : 0;
                DB::table(Str::plural($model->storage_type))->where('file_id', 0)->update([
                    'file_id' => $fileID
                ]);
            }


            if ($model->type != GatherConstant::TYPE_IMAGE) { //图片内容数量在processImage里叠加,防止图片下载过多
                $this->contentAmount += $actualInsertAmount;
            }


            gather_log(sprintf("url为:%s,响应处理:获取到%s个内容,实际插入%s", $dynamicURL, count($inserts), $actualInsertAmount));


            $this->notify(sprintf("url为:%s,获取到%s个内容,实际插入%s<br>", $dynamicURL, count($inserts), $actualInsertAmount), sprintf('当前获取内容数量为%s', $this->contentAmount));


            list($matchURLs, $filterMatchURLs) = $this->joinDynamicURLs($res);


            gather_log(sprintf("url为:%s,响应处理:获取到%s个新请求", $dynamicURL, count($filterMatchURLs)));


            $this->requestAmount++;

            $this->notify(sprintf("url为:%s,获取到的匹配网址数量为%s,未采集过的数量为%s", $dynamicURL, count($matchURLs), count($filterMatchURLs)), sprintf('当前请求链接数量为%s', $this->requestAmount), 'info');


            if ($this->isCrontab()) {
                GatherCrontabLog::where('id', $this->crontabID)->update([
                    'gather_content_amount' => $this->contentAmount,
                    'gather_url_amount'     => $this->requestAmount
                ]);
            }


            if ($this->requestAmount >= $maxRequestAmount || $this->contentAmount >= $maxContentAmount) {


                $this->notify(sprintf("本次采集共获取到%s个内容,请求数量为%s", $this->contentAmount, $this->requestAmount), '请求完成');

                return;
            }


            $this->hasRequestURLs[] = $dynamicURL;


            sleep($gatherRequestTimeInterval);
        }

        $this->notify(sprintf("本次采集共获取到%s个内容,请求数量为%s,没有获取到的新的链接,请等待采集页面更新内容或更换开始网址", $this->contentAmount, $this->requestAmount), '已完成');
    }


    /**
     * author: mtg
     * time: 2021/6/10   11:32
     * function description:将内容写入到文件,并将文件信息存储到数据库,返回主键id
     * @param $content
     * @param bool $isLast
     */
    public function writeFile($content): int
    {
        static $fullPath = null;


        if (is_null($fullPath)) {

            $path     = 'files/' . date('Y/m/d');
            $filename = 'gather_' . $this->model->name . '_' . $this->model->id . '_' . Str::random(13) . '.txt';
            $fullPath = $path . '/' . $filename;
            Storage::put($fullPath, '');
            if ($content) {
                Storage::append($fullPath, $content, '');
            }
        } else {
            if ($content) {
                Storage::append($fullPath, $content);
            }
        }

        clearstatcache();
        $fileInsertData = [
            'name'         => \Illuminate\Support\Facades\File::name($fullPath),
            'ext'          => \Illuminate\Support\Facades\File::extension($fullPath),
            'size'         => \Illuminate\Support\Facades\File::size(config('filesystems.disks.admin.root') . '/' . $fullPath),
            'rows'         => 0,
            'type'         => $this->model->storage_type,
            'category_id'  => $this->model->category_id,
            'is_collected' => 1
        ];

        $fileObj = File::updateOrCreate(['path' => $fullPath], $fileInsertData);

        return $fileObj->id;

    }

    /**
     * author: mtg
     * time: 2021/6/8   17:37
     * function description:长度和内容过滤
     * @param $content
     * @param $modal
     */
    public function filter(string $content)
    {
        $settingLengthLimit = $this->model->filter_length_limit;
        if (
            $settingLengthLimit > 0 &&
            strlen($content) < $settingLengthLimit
        ) { //长度过滤
            return [false, ''];
        }


        /*短语过滤*/
        if (empty($this->model->filter_content)) {
            return [true, $content];
        }
        $phrases = explode(PHP_EOL, $this->model->filter_content);

        foreach ($phrases as $phrase) {
            if (Str::startsWith($phrase, '*') && Str::endsWith($phrase, '*')) { //过滤掉包含短语的句子
                $phrase = trim($phrase, '*');
                if (strpos($content, $phrase) !== false) {
                    return [false, ''];
                }
            } else {
                if (strpos($content, $phrase) !== false) { //替换掉短语
                    return [true, str_ireplace($phrase, '', $content)];
                }
            }

        }
        return [true, $content];
    }


    /**
     * author: mtg
     * time: 2021/6/10   11:42
     * function description: 将返回结果中符合的url加入新的请求队列中
     * @param strin $res
     */
    public function joinDynamicURLs(string $res)
    {
        /*将响应中的url加入请求池中*/
        list($matchURLs, $filterMatchURLs) = CrawlService::extractAndPushUrls(
            $res,
            $this->model->regular_url,
            /*返回false的url将不再采集*/
            function ($url, $isFilter) {
                if ($isFilter && DB::table(Str::plural($this->model->storage_type))->where([
                        'source_url'  => $url,
                        'category_id' => $this->model->category_id,
                    ])->exists()) {
                    gather_log(sprintf("url为%s,已经采集过,不再采集", $url));

                    return false;
                }
                return true;

            });

        array_push($this->dynamicURLs, ...$filterMatchURLs);

        $this->dynamicURLs = array_unique($this->dynamicURLs);
        return [$matchURLs, $filterMatchURLs];
    }

    /**
     * author: mtg
     * time: 2021/6/10   11:13
     * function description:提取文章的标题
     * @param string $res
     * @return string
     */
    public function extractArticleTitles(string $res)
    {
        $patterns = CrawlService::parsePatterns($this->model->regular_title);

        $collect = $this->getMatches($patterns, $res);

        return $collect;
    }

    /**
     * author: mtg
     * time: 2021/6/10   10:53
     * function description: 将抓取结果根据匹配内容正则进行匹配,将匹配后的内容根据抓取类型进行处理
     * @param string $res
     * @return Collection
     */
    public function gatherRegularContentsAndProcess(string $res): Collection
    {


        $patterns = CrawlService::parsePatterns($this->model->regular_content);

        $contentMatches = $this->getMatches($patterns, $res);


        if ($contentMatches->isEmpty()) {
            return $contentMatches;
        }
        /*处理响应数据*/
        switch ($this->model->type) {
            case GatherConstant::TYPE_ARTICLE: //文章还需要匹配标题
                $data = $this->processFull($contentMatches);
                break;
            case GatherConstant::TYPE_IMAGE:
                $data = $this->processImage($contentMatches);
                break;
            case GatherConstant::TYPE_SENTENCE:
                $data = $this->processSentence($contentMatches);
                break;
            case GatherConstant::TYPE_TITLE:
                $data = $this->processTitle($contentMatches);
                break;

        }

        /*关键词过滤*/
        if ($this->model->type != GatherConstant::TYPE_IMAGE) {
            $data = $this->filterKeywords($data);
        }
        return $data;
    }


    /**
     * author: mtg
     * time: 2021/6/10   11:08
     * function description: 获取正则匹配的内容,优先返回第一子匹配,然后返回全匹配
     * @param Collection $patterns
     * @param string $res
     * @return Collection
     */
    public function getMatches(Collection $patterns, string $res)
    {

        return $contentMatches = $patterns
            ->map(function ($pattern) use ($res) {
                $resultIndex = 0; //当没有子项时完整匹配
                //有子项时,使用第一个子项匹配
                if (strpos($pattern, '(') !== false && strpos($pattern, '\(') === false) {
                    $resultIndex = 1;
                }
                preg_match_all($pattern, $res, $matches);
                return $matches[$resultIndex];
            })
            ->flatten();

    }

    /**
     * author: mtg
     * time: 2021/6/7   10:46
     * function description: 处理图片
     * @param Collection $contents
     * @return Collection
     */
    public function processImage(Collection $urls, $isFull = false): Collection
    {

        $rs = collect([]);

        foreach ($urls as $url) {


            $url = CrawlService::completeURL($url);

            $path = 'images/' . date('Y/m/d');
            $ext  = pathinfo($url, PATHINFO_EXTENSION);

            if (empty($ext)) {
                $ext = 'png';
            }
            $filename = 'gather_' . Str::random(33) . '.' . $ext;
            $fullPath = $path . '/' . $filename;


            $fullPath = Storage::path($fullPath);

            try {
                CrawlService::download($url, $fullPath);

            } catch (\Exception $e) {
                gather_log(sprintf("下载图片失败,失败链接为%s,失败原因为:%s", $url, $e->getMessage()));
                continue;
            }

            $relativePath = CrawlService::imageFullPathToRelative($fullPath);
            $rs->push($relativePath);

            if (!$isFull) {
                $this->contentAmount++;
                if ($this->contentAmount >= $this->maxContentAmount) {
                    return $rs->filter();
                }

            }

        }

        return $rs->filter();


    }

    /**
     * author: mtg
     * time: 2021/6/7   10:46
     * function description: 处理整篇文章
     * @param Collection $contents
     * @return Collection
     */
    public function processTitle(Collection $contents): Collection
    {

        return collect([trim($contents->implode("\r\n"))]);
    }

    /**
     * author: mtg
     * time: 2021/6/7   10:46
     * function description: 处理整篇文章
     * @param Collection $contents
     * @return Collection
     */
    public function processFull(Collection $contents): Collection
    {
        return $contents->map(function ($content) {

            $content = preg_replace('#<script.*?>[\s\S]*?<\/script>#', '', $content);
            $content = preg_replace('#<style.*?>[\s\S]*?<\/style>#', '', $content);
            $content = trim(strip_tags($content, '<p><img><br>'));

            if (conf_without_cache('article_image_type', 'fakeorigin', FakeOriginConstants::ARTICLE_IMAGE_REMOTE) == FakeOriginConstants::ARTICLE_IMAGE_LOCAL) {

                $preg = '/<img[\s\S]*?src=("[^"]*?"|\'[^\']*?\')[\s\S]*?>/';

                $content = preg_replace_callback($preg, function ($match) { //下载图片并替换成本地链接

                    $before = data_get($match, 0);
                    $link   = data_get($match, 1);

                    if (strpos($link, '"') !== false) { //src属性使用的引号,单引号或双引号
                        $symbol = '"';
                    } else {
                        $symbol = "'";
                    }

                    gather_log(sprintf('获取到的图片匹配为:%s', $link));
                    $imgRemoteURL = trim($link, $symbol);
                    $imgRemoteURL = trim($imgRemoteURL);

                    gather_log(sprintf('获取到的图片远程链接为:%s', $imgRemoteURL));

                    if (empty($imgRemoteURL)) {
                        return '';
                    }
                    $collects = $this->processImage(collect($imgRemoteURL), true);

                    $imgPath = data_get($collects, 0);
                    gather_log(sprintf('获取到的图片本地路径为:%s', $imgPath));

                    if (empty($imgPath)) {
                        return '';
                    }
                    $imgLocalURL = $imgPath;


                    gather_log(sprintf('获取到的图片url为:%s', $imgLocalURL));


                    $rs = str_replace($link, "{$symbol}{$imgLocalURL}{$symbol}", $before);

                    gather_log(sprintf('替换前的内容为:%s,被替换的链接为:%s,替换的链接为:%s,返回的内容为:%s', $before, $link
                        , "{$symbol}{$imgLocalURL}{$symbol}", $rs));

                    return $rs;

                }, $content);
            }


            $content = preg_replace("/[\s]{2,}/", "", $content); //去除空行和空格
            $content = $this->fakeOrigin($content);
            return $content;
        })->filter()->flatten();
    }


    /**
     * author: mtg
     * time: 2021/6/7   10:46
     * function description: 处理句子
     * @param Collection $contents
     * @return Collection
     */
    public function processSentence(Collection $contents): Collection
    {


        return $contents->map(function ($content) {
            $content = CrawlService::stripIrrelevantChars($content);
            $content = $this->fakeOrigin($content);
            return CrawlService::split2Sentence($content, $this->model->delimiter);
        })->flatten();
    }

    /**
     * author: mtg
     * time: 2021/6/16   15:57
     * function description:关键词包含过滤
     */
    public function filterKeywords(Collection $contents)
    {
        $keywords = array_filter(explode("\r\n", $this->model->keywords));

        if (empty($keywords)) {
            return $contents;
        }

        $rs = $contents->filter(function ($content) use ($keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($content, trim($keyword)) !== false) {
                    return true;
                }
            }
            return false;
        });
        return $rs;
    }

    public function fakeOrigin($content)
    {
        if (!$this->model->is_open_fake_origin) {
            return $content;
        }
        $type = conf('fakeorigin.type');


        switch ($type) {
            case FakeOriginConstants::TYPE_GOOGLE:
                $rs = FakerOriginService::toGoogle($content);
                break;
            case FakeOriginConstants::TYPE_5118:
                $rs = FakerOriginService::to5118($content);
                break;
            case FakeOriginConstants::TYPE_SYNONYM:
                $rs = FakerOriginService::toSynonym($content);
                break;
        }

        if (!$rs['state']) {
            $this->notify(sprintf('伪原创错误,错误内容为:%s', $rs['msg']), "提示", 'error');
        }

        $content = $rs['content'];

        return $content;

    }

    public function notify($content = "", $title = "", $type = "success")
    {

        if ($this->isCrontab()) { //后台任务不进行输出

            $content = $content . "\n";
            array_push($this->gatherLogBuffer, $content);

            if (count($this->gatherLogBuffer) > 15) {
                $logs = implode('', $this->gatherLogBuffer);
                GatherCrontabLog::where('id', $this->crontabID)->update([
                    'gather_log' => Db::raw("concat(gather_log,'$logs')")
                ]);
                $this->gatherLogBuffer = [];
            }

            return;
        }


        $timeStr = date('Y-m-d H:i:s');
        $content = "当前时间为:" . $timeStr . ' ,' . $content;
        $info    = <<<EOT

<div class="alert alert-$type alert-dismissible">
                <h4><i class="icon fa fa-info"></i> $title!</h4>
$content
 </div>
<script >
  var h = $(document).height()-$(window).height();

  $(document).scrollTop(h);

</script>
EOT;

        force_response($info);
    }


    public function isCrontab()
    {
        return !!$this->crontabID;
    }


    public function crontabFlushLog()
    {
        if (count($this->gatherLogBuffer) == 0) {
            return;
        }
        $logs = implode('', $this->gatherLogBuffer);
        GatherCrontabLog::where('id', $this->crontabID)->update([
            'gather_log' => Db::raw("concat(gather_log,'$logs')")
        ]);
    }
}

