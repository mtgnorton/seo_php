<?php

namespace App\Services;

use App\Constants\ContentConstant;
use App\Constants\RedisCacheKeyConstant;
use App\Constants\SpiderConstant;
use App\Models\Article;
use App\Models\ContentCategory;
use App\Models\OperationLog;
use App\Models\PushFile;
use App\Models\Sentence;
use App\Models\Template;
use App\Models\Website;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Redis;

/**
 * 通用服务类
 *
 * Class CommonService
 * @package App\Services
 */
class CommonService extends BaseService
{
    /**
     * 需要过滤的后缀
     */
    const MATERIAL_EXTS = [
        'js', 'css', 'jpg', 'png', 'gif',
        'ico', 'woff', 'ttf', 'otf',
        'ttc'
    ];
    
    /**
     * 被删除的log文件夹
     */
    const CLEAR_CACHE_PATH = '/data/logs';

    /**
     * 需要删除的文件名
     */
    const CLEAR_CACHE_FILES = [
        'a.log',
        'b.log',
        'c.log',
        'd.log',
    ];

    /**
     * 判断当前请求类型
     *
     * @return string
     */
    public static function requestType()
    {
        $result = 'normal';

        // 是否是pjax请求
        if (request()->pjax()) {
            $result = 'pjax';
            // 是否是ajax
        } else if (request()->ajax()) {
            $result = 'ajax';
        }

        return $result;
    }

    /**
     * 返回不同类型的响应
     *
     * @return mixed
     */
    public static function responseErrorType(string $message = '', string $type = 'normal')
    {
        if ($type === 'pjax') {
            return back_error('错误', $message);
        } else if ($type === 'ajax') {
            return response()->json([
                'status'  => false,
                'message' => $message,
                'display' => [],
            ]);
        }
    }

    /**
     * 将大小进行单位格式化
     *
     * @param [type] $size
     * @return string
     */
    public static function formatBytes($size)
    {
        // 将文件尺寸加上单位
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
            $size = bcdiv($size, 1024, 2);
        }

        return $size . $units[$i];
    }

    /**
     * 获取自定义方法跳转连接
     *
     * @param string $url 连接地址
     * @param string $name 连接名称
     * @param string $buttonType 按钮类型
     * @param integer $interval 间隔
     * @return void
     */
    public static function getActionJumpUrl(
        string $url,
        string $name,
        string $buttonType = 'default',
        int $interval = 10,
        string $icon = 'fa-mail-forward'
    )
    {
        return "<a class='btn btn-sm btn-{$buttonType} form-history-bac' style='float: right;margin-left: {$interval}px;' href='{$url}'><i class='fa {$icon}'></i>&nbsp;{$name}</a>";
    }

    /**
     * Unicode编码
     *
     * @param [type] $str
     * @return void
     */
    public static function unicodeEncode($str)
    {
        //split word
        preg_match_all('/./u', $str, $matches);

        $unicodeStr = "";
        foreach ($matches[0] as $m) {
            //拼接
            $unicodeStr .= "&#" . base_convert(bin2hex(iconv('UTF-8', "UCS-4", $m)), 16, 10);
        }

        return $unicodeStr;
        // $name = iconv('UTF-8', 'UCS-2', $name);
        // $len = strlen($name);
        // $str = '';
        // for ($i = 0; $i < $len - 1; $i = $i + 2)
        // {
        //     $c = $name[$i];
        //     $c2 = $name[$i + 1];
        //     if (ord($c) > 0)
        //     {   //两个字节的文字
        //         $str .= '\u'.base_convert(ord($c), 10, 16).str_pad(base_convert(ord($c2), 10, 16), 2, 0, STR_PAD_LEFT);
        //     }
        //     else
        //     {
        //         $str .= $c2;
        //     }
        // }
        // return $str;
    }

    /**
     * 将UNICODE编码后的内容进行解码
     *
     * @param [type] $name
     * @return void
     */
    public static function unicodeDecode($name)
    {
        //转换编码，将Unicode编码转换成可以浏览的utf-8编码
        $pattern = '/([\w]+)|(\\\u([\w]{4}))/i';
        preg_match_all($pattern, $name, $matches);
        if (!empty($matches)) {
            $name = '';
            for ($j = 0; $j < count($matches[0]); $j++) {
                $str = $matches[0][$j];
                if (strpos($str, '\\u') === 0) {
                    $code  = base_convert(substr($str, 2, 2), 16, 10);
                    $code2 = base_convert(substr($str, 4), 16, 10);
                    $c     = chr($code) . chr($code2);
                    $c     = iconv('UCS-2', 'UTF-8', $c);
                    $name  .= $c;
                } else {
                    $name .= $str;
                }
            }
        }

        return $name;
    }

    /**
     * 获取特殊ascii码
     *
     * @param [type] $count
     * @return void
     */
    public static function getSpecAscii()
    {
        // $luanma_array = array("\5", "\6", "\7", "\10");
        // $luanma = '';
        // $i = 0;
        // while ($i < mt_rand(2, 5)) {
        //     $luanma .= $luanma_array[mt_rand(0, count($luanma_array) - 1)];
        //     $i++;
        // }

        // return $luanma;

        $file = Storage::disk('store')->get('ascii.txt');

        $asciiData = CommonService::linefeedStringToArray($file);

        $count = count($asciiData) - 1;

        return $asciiData[mt_rand(0, $count)] ?? '';
    }

    /**
     * 将库文件内容转化为数组格式
     *
     * @param array|string $stores 如果是多个, 就是字符串数组
     * @param string $separator 分隔符
     * @return array
     */

    public static function storeStringToArray($stores, $separator = '******')
    {

        $tempArr = self::linefeedStringToArray($stores);

        $result = [];

        foreach ($tempArr as $temp) {
            $tempData = explode($separator, $temp);
            if (!isset($tempData[0]) || !isset($tempData[1])) {
                continue;
            }

            $result[$tempData[0]] = $tempData[1];
        }

        return $result;
    }


    public static  function getSynonyms()
    {
        static $synonyms = null;

        if (!is_null($synonyms)) {
            return $synonyms;
        }
        $path = app()->basePath() . '/storage/storehouse/synonym.php';


        $synonyms = include $path;

        return $synonyms;
    }

    /**
     * 将换行的字符串或数组转为数组返回
     *
     * @param string|array $data
     * @return array
     */
    public static function linefeedStringToArray($data, $pattern = '/\r\n|\n/')
    {
        $tempArr = [];

        if (is_array($data)) {
            foreach ($data as $store) {
                $storeArr = preg_split($pattern, $store, -1, PREG_SPLIT_NO_EMPTY);
                $tempArr  = array_merge($tempArr, $storeArr);
            }
        } else {
            $tempArr = preg_split($pattern, $data, -1, PREG_SPLIT_NO_EMPTY);
        }

        return $tempArr;
    }

    /**
     * 获取用户真实ip地址
     *
     * @return void
     */
    public static function getUserIpAddr()
    {
        $ipaddress = '';

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;
    }

    /**
     * 获取当前链接对应的网站分类ID
     *
     * @return void
     */
    public static function getCategoryId($host = '')
    {
        // 获取当前域名信息
        if (empty($host)) {
            $host = request()->getHost();
        }

        $hostArr = explode('.', $host);

        $newHostArr = [];
        for ($i = 0; $i < 2; $i++) {
            array_unshift($newHostArr, array_pop($hostArr));
        }

        // 获取不带前缀的域名和带www的域名
        $newHost    = implode('.', $newHostArr);
        $wwwNewHost = 'www.' . $newHost;
        // $hostData = compact('host', 'newHost', 'wwwNewHost');
        
        $categoryData1 = CommonService::websites(['url' => $host], 1, 'category_id'); 
        if (!empty($categoryData1)) {
            return $categoryData1;
        }
        
        $categoryData2 = CommonService::websites(['url' => $newHost], 1, 'category_id');
        if (!empty($categoryData2)) {
            return $categoryData2;
        }
        
        $categoryData3 = CommonService::websites(['url' => $wwwNewHost], 1, 'category_id');
        if (!empty($categoryData3)) {
            return $categoryData3;
        }

        return 0;
    }

    /**
     * 获取官网公告
     *
     * @return void
     */
    public static function getNotices($perpage = 5, $page = 1)
    {
        $officialDomain = config('seo.official_domain');
        $url            = $officialDomain . "/index/version/getNotice?perpage={$perpage}&page={$page}";

        $opts    = array('http' => array('header' => "User-Agent:MyAgent/1.0\r\n"));
        $context = stream_context_create($opts);
        try {
            $result = file_get_contents($url, false, $context);

            $data = json_decode($result, true);

            if ($data['code'] == '200') {
                return $data['data']['data'];
            }

            return [];
        } catch (Exception $e) {
            common_log('获取官网公告失败', $e);

            return [];
        }
    }

    /**
     * 获取用户最后一次操作数据
     *
     * @return void
     */
    public static function getLastOperation()
    {
        $result = OperationLog::latest()
            ->first()
            ->toArray();

        return $result;
    }

    /**
     * 清除数组中的特殊字符并
     *
     * @param array $data
     * @return array
     */
    public static function clearEmptyArrVal(array $data, $charlist = '')
    {
        foreach ($data as $key => &$val) {
            if (!empty($charlist)) {
                $val = trim($val, $charlist);
            } else {
                $val = trim($val);
            }
            if (empty($val)) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * 根据内容返回内容数组
     *
     * @param string $content
     * @return array
     */
    public static function getHtmlContent(string $content)
    {
        $textractor    = new Textractor();
        $contentResult = $textractor->parse($content); // mtg
        $contentResult = $textractor->getTextSource();

        $contentResult = self::linefeedStringToArray($contentResult, '/\r\n/');
        $contentResult = self::linefeedStringToArray($contentResult, '/\n/');
        $contentResult = self::clearEmptyArrVal($contentResult);

        return $contentResult;
    }

    /**
     * 获取百度下拉相关词
     *
     * @param string $keyword
     * @return void
     */
    public static function getBaiduDropdownWords(string $keyword)
    {
        $keyword = str_replace(' ', '', $keyword);
        $key = 'baidu_dropdown_words_' . $keyword;
        $words = [];
        $result = '';

        // 1. 获取百度相关词
        if (cache_static_file_get($key)) {
            $words = cache_static_file_get($key);
        } else {
            try {
                $url = "https://www.baidu.com/sugrec?pre=1&p=3&ie=utf-8&json=1&prod=pc&from=pc_web&wd=" . $keyword . "&req=2&bs=2&csor=1&cb=jQuery110204561448478299086_1626659204769&_=1626659204771";

                $tempRes = file_get_contents($url);
                // 正则匹配内容
                preg_match('#\[{(.*?)}\]#', $tempRes, $data);

                $jsonData = $data[0] ?? '';
                if (!empty($jsonData)) {
                    $arrData  = json_decode($jsonData, true);
                    $words = array_column($arrData, 'q');
                }
            } catch (Exception $e) {
                common_log('获取百度下拉词失败', $e);
                $words = [];
            }
        }

        // 2. 获取搜狗下拉词
        if (empty($words) || !is_array($words)) {
            try {
                $sougouUrl = "https://www.sogou.com/suggnew/ajajjson?type=web&key=".urlencode($keyword);
                $sougoutempRes = file_get_contents($sougouUrl);
                $sougoutempRes = iconv('GBK', 'UTF-8', $sougoutempRes);
                preg_match("#\,\[(.*?)\]\,#", $sougoutempRes, $preg);
    
                $sougouRes = $preg[1] ?? '';
                if (!empty($sougouRes)) {
                    $sougouStr = str_replace('"', '', $sougouRes);
                    $words = explode(',', $sougouStr);
                }
            } catch (Exception $e) {
                common_log('获取搜狗下拉词失败', $e);
                $words = [];
            }
        }

        // 3. 获取360下拉词
        if (empty($words) || !is_array($words)) {
            try {
                $sougouUrl = "https://sug.so.360.cn/suggest?encodein=utf-8&encodeout=utf-8&format=json&word=".$keyword."&callback=window.so.sug";
                $soTempRes = file_get_contents($sougouUrl);
                preg_match("#\[\{(.*?)\}\]#", $soTempRes, $soPreg);
    
                $soStr = $soPreg[0] ?? '';
                if (!empty($soStr)) {
                    $soArr = json_decode($soStr, true);
                    $words = array_column($soArr, 'word');
                }
            } catch (Exception $e) {
                common_log('获取360下拉词失败', $e);
                $words = [];
            }
        }

        if (!empty($words)) {
            Cache::store('file')->put($key, $words, 600);
            $count  = count($words) - 1;
            $result = $words[mt_rand(0, $count)] ?: '';
        }

        return $result;
    }

    /**
     * get请求
     *
     * @param [type] $url
     * @return void
     */
    public static function requestGet($url)
    {
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 1);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据

        return $data;
    }

    /**
     * 获取域名数据
     *
     * @param array $condition
     * @param int $count 查询数量, 1.返回一条,0.返回全部结果
     * @param string|array $column 要查询的键值
     * @return array
     */
    public static function websites($condition = [], $count = 0, $column = '')
    {
        $key = RedisCacheKeyConstant::CACHE_WEBSITES;

        $websites = cache_static_file_get($key);
        if (empty($websites)) {
            $websites = Website::select('id', 'url', 'category_id', 'group_id', 'is_enabled')
                ->get()->toArray();

            Cache::store('file')->put($key, $websites, 86400);
        }

        $websiteQuery = collect($websites)
            ->when(isset($condition['url']), function ($collection) use ($condition) {
                $url = is_array($condition['url']) ? $condition['url'] : [$condition['url']];

                return $collection->whereIn('url', $url);
            })->when(isset($condition['category_id']), function ($collection) use ($condition) {
                $categoryId = is_array($condition['category_id']) ? $condition['category_id'] : [$condition['category_id']];

                return $collection->whereIn('category_id', $categoryId);
            })->when(isset($condition['group_id']), function ($collection) use ($condition) {
                $grouId = is_array($condition['group_id']) ? $condition['group_id'] : [$condition['group_id']];

                return $collection->whereIn('group_id', $grouId);
            })->when(!empty($column), function ($collection) use ($column) {
                if (is_array($column)) {
                    $value = array_shift($column);
                    $key   = array_shift($column);

                    if (!empty($value)) {
                        if (!empty($key)) {
                            return $collection->pluck($value, $key);
                        } else {
                            return $collection->pluck($value);
                        }
                    } else {
                        return $collection;
                    }
                } else {
                    return $collection->pluck($column);
                }
            });

        if ($count == 1) {
            $websiteData = $websiteQuery->first();
        } else {
            $websiteData = $websiteQuery->all();
            sort($websiteData);
        }

        return $websiteData;
    }

    /**
     * 获取内容分类数据
     *
     * @param array $condition
     * @param int $count 查询数量, 1.返回一条,0.返回全部结果
     * @param string $column 要查询的键值
     * @return array
     */
    public static function contentCategories($condition = [], $count = 0, $column = '')
    {
        $key = RedisCacheKeyConstant::CACHE_CONTENT_CATEGORIES;

        $categories = cache_static_file_get($key);
        if (empty($categories)) {
            $categories = ContentCategory::select(
                'id', 'category_id', 'group_id',
                'name', 'parent_id', 'type'
            )->get()->toArray();

            $pid = getmypid();

            optimize_log(sprintf('进程号%s,开始contentCategories,时间为%s', $pid, time()));

            Cache::store('file')->put($key, $categories, 3600);

            optimize_log(sprintf('进程号%s,结束contentCategories,时间为%s', $pid, time()));

        }

        $categoryQuery = collect($categories)
            ->when(isset($condition['id']), function ($collection) use ($condition) {
                $id = is_array($condition['id']) ? $condition['id'] : [$condition['id']];

                return $collection->whereIn('id', $id);
            })->when(isset($condition['name']), function ($collection) use ($condition) {
                $name = is_array($condition['name']) ? $condition['name'] : [$condition['name']];

                return $collection->whereIn('name', $name);
            })->when(isset($condition['type']), function ($collection) use ($condition) {
                $type = is_array($condition['type']) ? $condition['type'] : [$condition['type']];

                return $collection->whereIn('type', $type);
            })->when(isset($condition['category_id']), function ($collection) use ($condition) {
                $categoryId = is_array($condition['category_id']) ? $condition['category_id'] : [$condition['category_id']];

                return $collection->whereIn('category_id', $categoryId);
            })->when(isset($condition['group_id']), function ($collection) use ($condition) {
                $grouId = is_array($condition['group_id']) ? $condition['group_id'] : [$condition['group_id']];

                return $collection->whereIn('group_id', $grouId);
            })->when(isset($condition['parent_id']), function ($collection) use ($condition) {
                // $parentId = is_array($condition['parent_id']) ? $condition['parent_id'] : [$condition['parent_id']];
                $parentId = $condition['parent_id'];

                if (is_array($condition['parent_id'])) {
                    $parentId = array_pop($condition['parent_id']);
                }
                // dump($parentId);
                $allContentCategoryIds = self::allContentCategoryIds($parentId);
                // dd($allContentCategoryIds);

                return $collection->whereIn('id', $allContentCategoryIds);
            })->when(!empty($column), function ($collection) use ($column) {
                if (is_array($column)) {
                    $value = array_shift($column);
                    $key   = array_shift($column);

                    if (!empty($value)) {
                        if (!empty($key)) {
                            return $collection->pluck($value, $key);
                        } else {
                            return $collection->pluck($value);
                        }
                    } else {
                        return $collection;
                    }
                } else {
                    return $collection->pluck($column);
                }
            });

        if ($count == 1) {
            $categoryData = $categoryQuery->first();
        } else {
            $categoryData = $categoryQuery->all();
            sort($categoryData);
        }

        return $categoryData;
    }

    /**
     * 获取内容分类子类ID(包括本身)
     *
     * @return void
     */
    public static function allContentCategoryIds($categoryId)
    {
        static $categoryIds = [];
        $categoryIds[] = $categoryId;

        $children = ContentCategory::where('parent_id', $categoryId)
                        ->pluck('id');

        foreach ($children as $child) {
            self::allContentCategoryIds($child);
        }

        return $categoryIds;
    }

    /**
     * 获取域名数据
     *
     * @param array $condition
     * @param int $count 查询数量, 1.返回一条,0.返回全部结果
     * @param string $column 要查询的键值
     * @return array
     */
    public static function templates($condition = [], $count = 0, $column = '')
    {
        $key = RedisCacheKeyConstant::CACHE_TEMPLATES;

        $templates = cache_static_file_get($key);
        if (empty($templates)) {
            $templates = Template::select(
                'id', 'name', 'tag', 'category_id',
                'type_id', 'group_id', 'type_tag'
            )->get()->toArray();

            Cache::store('file')->put($key, $templates, 86400);
        }

        $templateQuery = collect($templates)
            ->when(isset($condition['id']), function ($collection) use ($condition) {
                $id = is_array($condition['id']) ? $condition['id'] : [$condition['id']];

                return $collection->whereIn('id', $id);
            })->when(isset($condition['name']), function ($collection) use ($condition) {
                $name = is_array($condition['name']) ? $condition['name'] : [$condition['name']];

                return $collection->whereIn('name', $name);
            })->when(isset($condition['tag']), function ($collection) use ($condition) {
                $tag = is_array($condition['tag']) ? $condition['tag'] : [$condition['tag']];

                return $collection->whereIn('tag', $tag);
            })->when(isset($condition['category_id']), function ($collection) use ($condition) {
                $categoryId = is_array($condition['category_id']) ? $condition['category_id'] : [$condition['category_id']];

                return $collection->whereIn('category_id', $categoryId);
            })->when(isset($condition['group_id']), function ($collection) use ($condition) {
                $groupId = is_array($condition['group_id']) ? $condition['group_id'] : [$condition['group_id']];

                return $collection->whereIn('group_id', $groupId);
            })->when(isset($condition['type_id']), function ($collection) use ($condition) {
                $typeId = is_array($condition['type_id']) ? $condition['type_id'] : [$condition['type_id']];

                return $collection->whereIn('type_id', $typeId);
            })->when(isset($condition['type_tag']), function ($collection) use ($condition) {
                $typeTag = is_array($condition['type_tag']) ? $condition['type_tag'] : [$condition['type_tag']];

                return $collection->whereIn('type_tag', $typeTag);
            })->when(!empty($column), function ($collection) use ($column) {
                if (is_array($column)) {
                    $value = array_shift($column);
                    $key   = array_shift($column);

                    if (!empty($value)) {
                        if (!empty($key)) {
                            return $collection->pluck($value, $key);
                        } else {
                            return $collection->pluck($value);
                        }
                    } else {
                        return $collection;
                    }
                } else {
                    return $collection->pluck($column);
                }
            });

        if ($count == 1) {
            $templateData = $templateQuery->first();
        } else {
            $templateData = $templateQuery->all();
            sort($templateData);
        }

        return $templateData;
    }

    /**
     * 获取域名数据
     *
     * @param string $type 类型
     * @param string $model 模型
     * @param string $contentColumn 内容字段
     * @param array $condition 查询参数
     * @param array $noCondition notIn参数
     * @param int $count 查询数量, 1.返回一条,0.返回全部结果
     * @param string|array $column 要查询的键值, 如果想同时获取键值对, 则传数组, 值为第一个, 键为第二个
     * @return array
     */
    public static function contents($type = '', $tag = '', $model = '', $groupId = 0, $contentColumn = '', $condition = [], $noCondition = [], $count = 0, $column = '')
    {
        // $originType = $type;
        // if (in_array($type, ['article_title', 'article_content'])) {
        //     $type = 'article';
        // }
        $baseKey = ContentConstant::cacheKeyText()[$type] ?? '';
        $key     = $baseKey . $groupId . $tag;
        if (empty($key)) {
            return [];
        }
        $contents = cache_static_file_get($key);
        if (!is_null($contents)) {
            return $contents;
        }
        $pid = getmypid();
        /**
         * @var Redis $redis
         */
        $redis = app('redis');

        $lockKey = 'lock:' . $key;

        // 判断数据内容是否为空
        if ($redis->setnx($lockKey, 1)) {//加锁,如果为true,说明此时加锁成功
            $redis->expire($lockKey, 5);

            //  optimize_log(sprintf('进程号为:%s,获得锁,key为%s,开始执行时间为:%s', $pid, $lockKey, time()));

            if (in_array($type, ['article_title', 'article_content'])) {
                $baseCount = 500;
                $time      = 300;
            } else {
                $baseCount = 1000;
                $time      = 300;
            }
            try {
                $contentQuery = $model::when(isset($condition['category_id']), function ($query) use ($condition) {
                    $categoryId = is_array($condition['category_id']) ? $condition['category_id'] : [$condition['category_id']];

                    return $query->whereIn('category_id', $categoryId);
                })->when(isset($condition['tag']), function ($query) use ($condition) {
                    $tag = is_array($condition['tag']) ? $condition['tag'] : [$condition['tag']];

                    return $query->whereIn('tag', $tag);
                });

                $maxQuery   = clone $contentQuery;
                $rightQuery = clone $contentQuery;
                $leftQuery  = clone $contentQuery;
                $maxId      = $maxQuery->max('id');

                // 获取随机中间ID
                $queryId    = mt_rand(0, $maxId);
                $queryRight = $rightQuery->where('id', '>', $queryId)
                    ->limit($baseCount);
                $contents   = self::contentsQuery($queryRight, $column, $count, $contentColumn) ?: [];
                // 如果内容的数量小于需求数量, 则获取左边的数据
                if (count($contents) < $baseCount) {
                    $queryLeft   = $leftQuery->where('id', '<=', $queryId)
                        ->orderBy('id', 'desc')
                        ->limit($baseCount);
                    $contentLeft = self::contentsQuery($queryLeft, $column, $count, $contentColumn);
                    $contents    = $contents + $contentLeft;
                }
                Cache::store('file')->put($key, $contents, $time);
                $redis->del($lockKey); //放锁
                //    optimize_log(sprintf('进程号为:%s,结束执行时间为:%s', $pid, time()));

            } catch (\Exception $e) {
                $redis->del($lockKey); //放锁
                optimize_log('查询出错');
                optimize_log(full_error_msg($e));
            }


        } else {
            $i   = 0;
            $url = request()->url();
            while ($i < 6) { //最多休眠3秒
                optimize_log(sprintf('进程号为:%s,请求链接为:%s,请求key为:%s,次数为:%s,休眠0.5秒', $pid, $url, $lockKey, $i + 1));

                $lock = $redis->get($lockKey);
                if ($lock) {
                    $i++;
                    usleep(500000);
                } else {
                    $redis->del($lockKey); //放锁

                    $contents = cache_static_file_get($key) ?? null;
                    return $contents;
                }

            }
        }

        return $contents;
    }

    /**
     * Undocumented function
     *
     * @param [type] $query     模型
     * @param string $column 字段
     * @param integer $count 数量
     * @return array
     */
    public static function contentsQuery($query, $column = '', $count = 0, $contentColumn = '')
    {
        if (!empty($column)) {
            if (is_array($column)) {
                $resValue = array_shift($column);
                $resKey   = array_shift($column);

                if (!empty($resValue)) {
                    if (!empty($resKey)) {
                        $result = $query->pluck($resValue, $resKey);
                    } else {
                        $result = $query->pluck($resValue);
                    }
                }
            } else {

                $result = $query->pluck($column);
            }
        } else {
            if ($count == 1) {
                $result = $query->select(
                    'id', 'category_id', 'file_id',
                    'tag', $contentColumn
                )->first();
            } else {
                $result = $query->select(
                    'id', 'category_id', 'file_id',
                    'tag', $contentColumn
                )->get();
            }
        }

        return $result->toArray();
    }

    /**
     * 将字符串转码为UTF-8格式
     * @param string $content 内容
     * @param string $type 类型
     *
     * @return string
     */
    public static function changeCharset2Utf8(string $content = '', $type = '')
    {
        // 获取当前字符串编码
        $res = mb_detect_encoding($content, 'ASCII,UTF-8,GB2312,EUC-CN,GBK,BIG5');

        if ($type == 'html' && $res != 'UTF-8') {
            // 判断类型如果是html, 则先将meta标签中的charset=gbk转为utf-8
            $content = preg_replace_callback('#<meta.*?content=[\'\"].*?charset=(.*?)[\'\"].*?/>?#', function ($match) {
                $result = str_replace($match[1], 'utf-8', $match[0]);

                return $result;
            }, $content);
        }

        if ($res != 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $res);
        }

        return $content;
    }

    /**
     * 判断是否刷新不变
     *
     * @param array $config     站点配置
     *
     * @return boolean
     */
    public static function ifRefreshNotChange($config, $type='all')
    {
        // $isRefresh = isset($_SERVER['HTTP_CACHE_CONTROL']) &&
        //         $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0';
                
        $isRefreshConfig = $config['is_refresh_change'] ?? 'off';
        // $isNotChange = ($isRefreshConfig == 'off') && ($isRefresh == true);
        $isNotChange = ($isRefreshConfig == 'off');

        if ($type == 'config') {
            return $isRefreshConfig == 'off';
        }

        return $isNotChange;
    }

    /**
     * 获取刷新改变, 但第一次访问不变的内容
     *
     * @param string $baseUrl       基础地址
     * @param array $globalData     数据
     * @param string $headTag       头部标签
     * @param string $trueTag       真实标签
     * @return void
     */
    public static function getRefreshFixedContent($baseUrl='', &$globalData=[], $headTag = '', $trueTag = '')
    {
        // 如果地址不存在, 则重新获取
        if (empty($baseUrl)) {
            $baseUrl = request()->url();
        }

        $result = false;
        $contentId = 0;

        switch ($headTag) {
            case '头部标题':
                $key = $baseUrl;

                // $value = cache_static_get($key);
                $value = $globalData[$key] ?? '';
                if (!empty($value)) {
                    $value = $value;
                    $globalData[$trueTag] = $value;

                    $result = $value;
                }
                break;
            case '头部文章题目':
                $key = $baseUrl . '_article_id';

                // $articleId = redis_batch_get($key, true)[$key];
                $articleId = $globalData[$key] ?? 0;
                if (!empty($articleId)) {
                    $article = Article::find($articleId) ?? 0;
                    if (!empty($article)) {
                        $value = $article->title;
                        $globalData[$trueTag] = $value;

                        $result = $value;
                        $contentId = $articleId;
                    }
                }
                break;
            default:
                $result = false;
                break;
        }
        
        return [
            'content' => $result,
            'contentId' => $contentId,
        ];
    }

    /**
     * 获取页面中固定不变的内容(当刷新不变时)
     *
     * @param string $baseUrl       基础地址
     * @param array $globalData     数据
     * @param string $headTag       头部标签
     * @param string $trueTag       真实标签
     * @param int    $cacheTime     缓存时间
     * @return void
     */
    public static function getFixedContent($baseUrl='', &$globalData=[], $headTag = '', $trueTag = '')
    {
        // 如果地址不存在, 则重新获取
        if (empty($baseUrl)) {
            $baseUrl = request()->url();
        }

        $result = false;
        $contentId = 0;
        switch ($headTag) {
            case '头部标题':
                $key = $baseUrl;

                // $value = cache_static_get($key);
                $value = $globalData[$key] ?? '';
                if (!empty($value)) {
                    $value = $value;
                    $globalData[$trueTag] = $value;

                    $result = $value;
                }
                break;
            case '头部文章题目':
                $key = $baseUrl . '_article_id';

                // $articleId = redis_batch_get($key, true)[$key];
                $articleId = $globalData[$key] ?? 0;
                if (!empty($articleId)) {
                    $article = Article::find($articleId) ?? 0;
                    if (!empty($article)) {
                        $value = $article->title;
                        $globalData[$trueTag] = $value;

                        $result = $value;
                        $contentId = $articleId;
                    }
                }
                break;
            case '文章内容':
                $key = $baseUrl . '_article_id';

                // $articleId = redis_batch_get($key, true)[$key];
                $articleId = $globalData[$key] ?? 0;
                if (!empty($articleId)) {
                    $article = Article::find($articleId) ?? 0;
                    if (!empty($article)) {
                        $value = $article->content;
                        $globalData[$trueTag] = $value;

                        // 记录摘要内容
                        ContentService::putSummary($value, $globalData);

                        $result = $value;
                        $contentId = $articleId;
                    }
                }
                break;
            case '句子':
                // 获取句子数量
                // $sentenceKey = $baseUrl . '_sentence_count';
                // $sentenceTotal = cache_static_get($sentenceKey, 0);
                $sentenceTotal = $globalData['sentence_count'] ?? 0;

                // 获取缓存中的句子数组
                $key = $baseUrl . '_sentences_not_change';
                // $sentenceIdArr = redis_batch_get($key, true)[$key] ?? '';
                $sentenceIdArr = $globalData[$key] ?? '';
                $sentenceIdArr = json_decode($sentenceIdArr, true);
                if ( !empty($sentenceIdArr)) {
                    static $sentenceResult = [];

                    if (count($sentenceIdArr) == $sentenceTotal) {
                        if (empty($sentenceResult)) {
                            $sentenceIdStr = implode(',', $sentenceIdArr);
                            $sentenceResult = Sentence::whereIn('id', $sentenceIdArr)
                                ->orderByRaw(DB::raw('FIELD(id, '.$sentenceIdStr.') asc'))
                                ->pluck('content', 'id')
                                ->toArray();
                        }
                        // $tempSentences = array_flip($sentenceResult);
                        // $value = array_shift($sentenceResult);
                        $result = current($sentenceResult);
                        $contentId = key($sentenceResult);
                        unset($sentenceResult[$contentId]);
                        // $contentId = $tempSentences[$value];
                        // dump($sentenceResult, $tempSentences, $result, $contentId);

                        ContentService::putSentenceSummary($result, $globalData);
                    }
                }
                break;

            default:
                $result = false;
                break;
        }

        return [
            'content' => $result,
            'contentId' => $contentId,
        ];
    }

    /**
     * 清空缓存文件
     *
     * @return void
     */
    public static function clearCacheFiles()
    {
        common_log('开始清空缓存');
        try {
            // 1. 删除laravel日志文件
            $localFiles = Storage::disk('logs')->files();
    
            Storage::disk('logs')->delete($localFiles);

            // 2. 清空缓存文件
            $path = 'cache/templates/';
            if (Storage::disk('local')->allFiles($path)) {
                Storage::disk('local')->deleteDirectory($path);
            }
    
            // 3. 删除linux系统日志文件(/data/logs下)
            $basePath = self::CLEAR_CACHE_PATH;
            $shouldDelFiles = self::CLEAR_CACHE_FILES;

            $lsStr = "ls {$basePath}";
            exec($lsStr, $allFiles);
            
            $delFiles = [];
            foreach ($allFiles as $file) {
                // if (in_array($file, $shouldDelFiles)) {
                    // $delFiles[] = $basePath . '/' . $file;
                // }
                exec("echo ' '>".$basePath . '/' . $file);
            }
            // $fileStr = implode(' ', $delFiles);
    
            // $delStr = "rm -f {$fileStr}";
            // common_log('清空缓存语句: '.$delStr);

            // $res = exec($delStr);
            // common_log('执行结果'.$res);
            common_log('缓存清空成功');

            return self::success('缓存清空成功');
        } catch (Exception $e) {
            common_log('缓存清空失败', $e);
            return self::error('缓存清空失败, 请稍后重试');
        }
    }

    /**
     * 上传推送文件
     *
     * @param [type] $files
     * @return void
     */
    public static function pushFilesUpload(array $files)
    {
        $sum = count($files);
        $success = 0;
        $fail = 0;
        $repeat = 0;

        foreach ($files as $file) {
            DB::beginTransaction();
            try {
                $name = $file->getClientOriginalName();
                $type = $file->getClientOriginalExtension();
                // 判断文件是否已存在
                if (PushFile::where('name', $name)->exists() ||
                    file_exists($name)
                ) {
                    $repeat++;
                    continue;
                }
                $content = file_get_contents($file->getRealPath());
                
                PushFile::create([
                    'name' => $name,
                    'path' => '/'.$name,
                    'content' => in_array($type, ['txt','html']) ? $content : '',
                ]);

                file_put_contents($name, $content);

                $success++;

                DB::commit();
            } catch (Exception $e) {
                common_log('上传推送文件失败', $e);
                $fail++;

                DB::rollBack();
            }
        }

        return self::success([], "本次上传文件总数为:{$sum}, 上传成功数量: {$success}, 上传失败数量: {$fail}, 重复文件数量: {$repeat}");
    }
}
