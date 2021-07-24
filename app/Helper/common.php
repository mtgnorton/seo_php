<?php

use App\Constants\RedisCacheKeyConstant;
use App\Models\Config;
use App\Services\Gather\CrawlService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\MessageBag;

if (!function_exists('ll')) {
    /**
     * 获取general文件的翻译
     *
     * @param string|null $key
     * @param array $replace
     * @param string|null $locale
     * @return  \Illuminate\Contracts\Translation\Translator|string|array|null
     * @author  mtg
     */
    function ll($key, $replace = [], $locale = null)
    {
        $key = trim($key);

        if (strpos($key, '.') !== false) {
            return __($key, $replace, $locale);
        }
        $content = __("general." . $key, $replace, $locale);

        return Str::replaceFirst('general.', '', $content);
    }
}

if (!function_exists('lp')) {
    /**
     * 获取general文件的翻译
     *
     * @param string|null $key
     * @param array $replace
     * @param string|null $locale
     * @return  \Illuminate\Contracts\Translation\Translator|string|array|null
     * @author  mtg
     */
    function lp(...$vars)
    {
        $str = '';

        foreach ($vars as $var) {
            $str .= ll($var);
        }

        return $str;
    }
}

if (!function_exists('get_image_file_by_url')) {
    /**
     * 根据链接获取图片文件对象
     *
     * @param string $imageUrl 图片链接
     * @return Illuminate\Http\UploadedFile|bool
     */
    function get_image_file_by_url(string $imageUrl)
    {
        try {
            if (strpos($imageUrl, 'http://') === 0 ||
                strpos($imageUrl, 'https://') === 0
            ) {
                $client = new Client(['verify' => false]);
                $base   = $client->get($imageUrl);

                $typeData = $base->getHeader('content-type');

                if (empty($typeData) || empty($typeData[0])) {
                    return '';
                }

                $type = $typeData[0];

                switch ($type) {
                    case 'image/gif' :
                        $ext = '.gif';
                        break;
                    case 'image/jpeg' :
                        $ext = '.jpg';
                        break;
                    case 'image/png' :
                        $ext = '.png';
                        break;
                    default :
                        $ext = '.jpg';
                }

                $data = $base->getBody()->getContents();
            } else {
                // 获取文件后缀
                return '';
            }

            $imageName = '2aa21001c71d45a3b08c5e0352c29e4d' . $ext;
            $path      = public_path('seo/' . $imageName);

            Storage::put($imageName, $data);

            $file = new UploadedFile($path, $imageName);

            return $file;
        } catch (Exception $e) {
            common_log('上传图片失败', $e);

            return '';
        }
    }
}

if (!function_exists('back_success')) {
    /**
     * 成功返回
     *
     * @param string $title 标题
     * @param string $message 信息
     * @return void
     */
    function back_success(string $title = '', string $message = '')
    {
        $success = new MessageBag([
            'title'   => $title,
            'message' => $message,
        ]);

        return back()->with(compact('success'));
    }
}

if (!function_exists('back_error')) {
    /**
     * 失败返回
     *
     * @param string $title 标题
     * @param string $message 信息
     * @return void
     */
    function back_error(string $title = '', string $message = '')
    {
        $error = new MessageBag([
            'title'   => $title,
            'message' => $message,
        ]);

        return back()->with(compact('error'));
    }
}

if (!function_exists('common_log')) {
    /**
     * 失败返回
     *
     * @param string $message 日志信息
     *
     * @return void
     */
    function common_log(
        $message = '',
        Exception $e = null,
        $context = [],
        $channel = ''
    )
    {
        if (!is_array($context)) {
            $context = array($context);
        }

        // 判断异常对象是否为null
        if ($e && is_string($message)) {
            $message .= ' 错误原因为: ' . $e->getMessage() . ' | 错误位置在 ' . $e->getFile() . ' 第' . $e->getLine() . '行';
        }

        if ($channel) {
            Log::channel($channel)->info($message, $context);
        } else {
            Log::info($message, $context);
        }
    }
}

if (!function_exists('gather_log')) {
    /**
     * author: mtg
     * time: 2021/6/7   17:47
     * function description:采集日志
     * @param string $message
     * @param array $context
     * @param Exception|null $e
     */
    function gather_log(
        $message = '',
        $context = [],
        Exception $e = null
    )
    {
        common_log($message, $e, $context, 'gather');
    }
}

if (!function_exists('conf')) {

    /**
     * 获取配置信息数据
     *
     * @param string|array $keys 如 site_name或['site_name','bucket']或['site.site_name','storage.bucket']
     * @param string|null $default 默认值
     * @param int $categoryId 网站分类ID
     * @param int $templateId 模板ID
     * @return void
     */
    function conf($keys, $default = null, $categoryId = 0, $groupId = 0, $templateId = 0)
    {
        if (!is_array($keys)) {
            $keys = array($keys);
        }

        $condition = [];
        if (!empty($categoryId)) {
            $condition['category_id'] = $categoryId;
        }
        if (!empty($groupId)) {
            $condition['group_id'] = $groupId;
        }
        if (!empty($templateId)) {
            $condition['template_id'] = $templateId;
        }

        $configData = Cache::get(RedisCacheKeyConstant::CACHE_CONFIGS_KEY);

        if (!$configData) {

            $configData = Config::all();
            // dd($configData);
            Cache::set(RedisCacheKeyConstant::CACHE_CONFIGS_KEY, $configData);
        }
     
        $configData =  collect($configData->toArray())
                    ->when(!empty($categoryId), function ($collection) use ($categoryId) {
                        return $collection->where('category_id', '=', $categoryId);
                    })->when(!empty($groupId), function ($collection) use ($groupId) {
                        return $collection->where('group_id', '=', $groupId);
                    })->when(!empty($templateId), function ($collection) use ($templateId) {
                        return $collection->where('template_id', '=', $templateId);
                    })->all();
                    
        $configs = [];
        if (!empty($configData)) {
            foreach ($configData as $value) {
                $configs[$value['module']][$value['key']] = $value['value'];
            }
        }


        $result = [];
        foreach ($keys as $index => $key) {
            $result[$index] = data_get($configs, $key) ?: $default;
        }


        $result = count($result) == 1 ? current($result) : $result;

        return $result;
    }
}

if (!function_exists('multiple_rand')) {
    /**
     * 随机获取多个随机数
     *
     * @param integer $begin 开始
     * @param integer $end 结束
     * @param integer $limit 截取数量
     * @return array
     */
    function multiple_rand(int $begin = 0, int $end = 10, int $limit = 5)
    {
        $randArr = range($begin, $end);
        shuffle($randArr);

        return array_slice($randArr, 0, $limit);
    }
}

//首先修改nginx配置文件,修改或添加gzip off;proxy_buffering off;  fastcgi_keep_conn on;
function force_response(...$data)
{

    echo str_repeat(" ", 40960); //确保足够的字符，立即输出，Linux服务器中不需要这句

    foreach ($data as $item) {
        echo($item);
    }
    echo "<br>";
    ob_flush();
    flush();
}


function force_notify($content = "", $title = "提示", $type = "success")
{

    $info = <<<EOT

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

/**
 * author: mtg
 * time: 2021/6/23   16:24
 * function description:动态修改env文件
 * @param array $data
 */
function modify_env(array $data)
{
    $envPath = base_path() . DIRECTORY_SEPARATOR . '.env';

    $contentArray = collect(file($envPath, FILE_IGNORE_NEW_LINES));

    $contentArray->transform(function ($item) use ($data) {
        foreach ($data as $key => $value) {
            if (str_contains($item, $key)) {
                return $key . '=' . $value;
            }
        }

        return $item;
    });

    $content = implode($contentArray->toArray(), "\n");

    \File::put($envPath, $content);
}


function is_win(): bool
{
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}


/**
 * author: mtg
 * time: 2021/6/30   11:49
 * function description:获取远程的最新系统版本
 */
function get_remote_latest_version()
{
    $info = get_remote_latest_version_info();
    return data_get($info, 'version');
}

/**
 * author: mtg
 * time: 2021/7/3   16:16
 * function description: 获取远程的最新系统版本信息
 */
function get_remote_latest_version_info()
{
    $info = Cache::get('system_latest_version_info');

    if (!$info) {
        $url = trim(config('seo.official_domain'), '/') . '/index/version/getVersion';


        $data = CrawlService::get($url);
        $data = json_decode($data, true);

        $info = data_get($data, 'data');

        Cache::set('system_latest_version_info', $info, 3600);
    }

    return $info;
}

/**
 * author: mtg
 * time: 2021/7/5   10:01
 * function description:
 * @return mixed
 */
function get_remote_all_version_info()
{

    //http://www.xiaoyangyun.net/index/version/getHistoryVersion

    $info = Cache::get('system_history_version_info');
    if (!$info) {
        $url = trim(config('seo.official_domain'), '/') . '/index/version/getHistoryVersion';

        $data = CrawlService::get($url);
        $data = json_decode($data, true);

        $info = data_get($data, 'data');

        Cache::set('system_history_version_info', $info, 60);
    }

    return $info;
}


/**
 * author: mtg
 * time: 2021/6/25   11:34
 * function description: 获取异常完整的错误信息
 * @param Exception $e
 * @return string
 */
function full_error_msg(Exception $e)
{
    $trace = $e->getTraceAsString();
    $trace = str_replace("#", "<br/>#", $trace);
    return $errorMsg = $e->getMessage() . "  <br/> file:" . $e->getFile() . "<br/>   line:" . $e->getLine() . "<br/>   trace:" . $trace;

}


/**
 * Created by PhpStorm.
 * User: lukin
 * Date: 27/02/2017
 * Time: 21:27
 */
if (!function_exists('clear_space')) {
    /**
     * 清除空白
     *
     * @param string $content
     * @return string
     */
    function clear_space($content)
    {
        if (strlen($content) == 0) return $content;
        $r = $content;
        $r = str_replace(array(chr(9), chr(10), chr(13)), '', $r);
        while (strpos($r, chr(32) . chr(32)) !== false || strpos($r, '&nbsp;') !== false) {
            $r = str_replace(array(
                '&nbsp;',
                chr(32) . chr(32),
            ),
                chr(32),
                $r
            );
        }
        return $r;
    }
}

if (!function_exists('mid')) {
    /**
     * 内容截取，支持正则
     *
     * $start,$end,$clear 支持正则表达式，“@”斜杠开头为正则模式
     * $clear 支持数组
     *
     * @param string $content 内容
     * @param string $start 开始代码
     * @param string $end 结束代码
     * @param string|array $clear 清除内容
     * @return string
     */
    function mid($content, $start, $end = null, $clear = null)
    {
        if (empty($content) || empty($start)) return null;
        if (strncmp($start, '@', 1) === 0) {
            if (preg_match($start, $content, $args)) {
                $start = $args[0];
            }
        }

        $start_len = strlen($start);
        $result    = null;
        // 找到开始的位置
        $start_pos = stripos($content, $start);
        if ($start_pos === false) return null;
        // 获取剩余内容
        $remain_content = substr($content, -(strlen($content) - $start_pos - $start_len));
        if ($end === null) {
            $length = null;
        } else {
            // 正则查找结束符
            if ($end && strncmp($end, '@', 1) === 0) {
                if (preg_match($end, $remain_content, $args, PREG_OFFSET_CAPTURE)) {
                    if ($args[0][1] == strlen($remain_content)) {
                        $end = null;
                    } else {
                        $end = $args[0][0];
                    }
                }
            }
            if ($end == null) {
                $length = null;
            } else {
                $length = stripos($remain_content, $end);
            }
        }

        if ($start_pos !== false) {
            if ($length === null) {
                $result = trim(substr($content, $start_pos + $start_len));
            } else {
                $result = trim(substr($content, $start_pos + $start_len, $length));
            }
        }

        if ($result && $clear) {
            if (is_array($clear)) {
                foreach ($clear as $v) {
                    if (strncmp($v, '@', 1) === 0) {
                        $result = preg_replace($v, '', $result);
                    } else {
                        if (strpos($result, $v) !== false) {
                            $result = str_replace($v, '', $result);
                        }
                    }
                }
            } else {
                if (strncmp($clear, '@', 1) === 0) {
                    $result = preg_replace($clear, '', $result);
                } else {
                    if (strpos($result, $clear) !== false) {
                        $result = str_replace($clear, '', $result);
                    }
                }
            }
        }
        return $result;
    }
}


if (!function_exists('close_tags')) {
    /**
     * 关闭html标签
     *
     * @param string $html
     * @return mixed|string
     */
    function close_tags($html)
    {
        if (preg_match_all("/<\/?(\w+)(?:(?:\s+(?:\w|\w[\w-]*\w)(?:\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)\/?>/i", $html, $matches, PREG_OFFSET_CAPTURE)) {
            $stacks = array();
            foreach ($matches[0] as $i => $match) {
                $tagName = $matches[1][$i][0];
                if ($match[0]{strlen($match[0]) - 2} != '/') {
                    // 出栈
                    if ($match[0]{1} == '/') {
                        $data = array_pop($stacks);
                        if ($data) {
                            // 出栈要找到自己对应的 tagName
                            while ($tagName != $data[0]) {
                                $data = array_pop($stacks);
                            }
                            // 清理标签内没有内容的标签
                            $start     = $data[1];
                            $length    = $match[1] - $data[1] + strlen($match[0]);
                            $innerHTML = substr($html, $start, $length);
                            if (!preg_match('@<(
                                img|map|area|audio|embed|input|keygen|object|select|output|progress
                                )\s*@ix', $innerHTML) && strlen(trim(strip_tags($innerHTML))) == 0
                            ) {
                                // 清理标签
                                $html = substr_replace($html, str_repeat(' ', $length), $start, $length);
                            }
                        } else {
                            // 移除烂掉得标签
                            $length = strlen($match[0]);
                            $html   = substr_replace($html, str_repeat(' ', $length), $match[1], $length);
                        }
                    } else {
                        // 入栈
                        $stacks[] = array($tagName, $match[1], $match[0]);
                    }
                }
            }

            // 如果栈里还有内容，则补全标签
            foreach ($stacks as $stack) {
                $html .= '</' . $stack[0] . '>';
            }
        }
        return $html;
    }
}


/**
 * author: mtg
 * time: 2021/7/21   10:23
 * function description: 授权到期时间戳
 * @return int
 */
function auth_effective_time(): int
{
    $beginTime     = conf('auth.use_begin_time');
    $effectiveDays = conf('auth.effective_days');
    $expiredTime   = $beginTime + $effectiveDays * 24 * 60 * 60;
    return $expiredTime;

}


/**
 * author: mtg
 * time: 2021/7/22   14:55
 * function description: 伪原创
 * @param $text
 * @return string
 */
function fake_origin($text)
{
    $english = translate($text, 'en');
    return translate($english, 'zh-CN');
}

/**
 * author: mtg
 * time: 2021/7/22   14:50
 * function description:谷歌翻译接口
 * @param $text
 * @param string $to 'zh-CN'或'en'
 * @return string
 */
function translate($text, $to = 'zh-CN')
{
    $waitTranslateText = $text;
    $translateRs       = '';

    while (mb_strlen($waitTranslateText) > 0) {

        $thisTimeText = mb_substr($waitTranslateText, 0, 1500);

        $waitTranslateText = mb_substr($waitTranslateText, 1500);

        $thisTimeText = urlencode($thisTimeText);
        $url          = 'https://translate.google.cn/translate_a/single?client=gtx&dt=t&ie=UTF-8&oe=UTF-8&sl=auto&tl=' . $to . '&q=' . $thisTimeText;


        $res = CrawlService::get($url);

        $thisTimeRs = json_decode($res);

        if ($rs = data_get($thisTimeRs, 0)) {
            if (is_array($rs)) {
                foreach ($rs as $item) {
                    $translateRs .= data_get($item, '0');
                }
            }
        }
        sleep(1);


    }

    return $translateRs;

}

/**
 * author: mtg
 * time: 2021/7/21   17:35
 * function description:混合中文字符分隔
 * @param $str
 * @param int $length
 * @param bool $append
 * @return false|string
 */
function sub_str($str, $length = 0, $append = true)
{
    $str       = trim($str);
    $strLength = strlen($str);

    if ($length == 0 || $length >= $strLength) {
        return $str;  //截取长度等于0或大于等于本字符串的长度，返回字符串本身
    } elseif ($length < 0)  //如果截取长度为负数
    {
        $length = $strLength + $length;//那么截取长度就等于字符串长度减去截取长度
        if ($length < 0) {
            $length = $strLength;//如果截取长度的绝对值大于字符串本身长度，则截取长度取字符串本身的长度
        }
    }

    if (function_exists('mb_substr')) {
        $str = mb_substr($str, 0, $length, 'utf-8');
    } elseif (function_exists('iconv_substr')) {
        $str = iconv_substr($str, 0, $length, 'utf-8');
    } else {
        //$str = trim_right(substr($str, 0, $length));
        $str = substr($str, 0, $length);
    }

    if ($append && $str != $str) {
        $str .= '...';
    }

    return $str;
}
