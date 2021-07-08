<?php

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
            $path      = public_path('uploads/' . $imageName);

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
    function conf($keys, $default = null, $categoryId = 0, $templateId = 0)
    {
        if (!is_array($keys)) {
            $keys = array($keys);
        }

        $condition = [];
        if (!empty($categoryId)) {
            $condition['category_id'] = $categoryId;
        }
        if (!empty($templateId)) {
            $condition['template_id'] = $templateId;
        }
        $configData = Config::where($condition)->get();

        $configs = [];
        if (!$configData->isEmpty()) {
            foreach ($configData as $value) {
                $configs[$value['module']][$value['key']] = $value->value;
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

//        Cache::set('system_latest_version_info', $info, 3600);
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
    $info = Cache::get('system_history_version_info');
    if (!$info) {
        $url = trim(config('seo.official_domain'), '/') . '/index/version/getHistoryVersion';

        $data = CrawlService::get($url);
        $data = json_decode($data, true);

        $info = data_get($data, 'data');

//        Cache::set('system_history_version_info', $info, 3600);
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
