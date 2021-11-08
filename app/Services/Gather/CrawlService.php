<?php

namespace App\Services\Gather;


use App\Exceptions\CurlException;
use App\Models\Gather;
use App\Services\FileService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use hamburgscleanest\LaravelGuzzleThrottle\Facades\LaravelGuzzleThrottle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CrawlService extends \App\Services\BaseService
{


    static protected $curlOptions = [
        'CURLOPT_USERAGENT'      => "Mozilla/5.0 (Windows NT 5.1; zh-CN) AppleWebKit/535.12 (KHTML, like Gecko) Chrome/22.0.1229.79 Safari/535.12",
        'CURLOPT_SSL_VERIFYPEER' => 0, //https验证
        'CURLOPT_SSL_VERIFYHOST' => 0,//https验证
        'CURLOPT_HTTPHEADER'     => [

        ],
        'CURLOPT_RETURNTRANSFER' => 1,//设置获取的信息以文件流的形式返回，而不是直接输出
        'CURLOPT_REFERER'        => "http://www.baidu.com", //在HTTP请求中包含一个'referer'头的字符串
        'CURLOPT_TIMEOUT'        => 5,
        'CURLOPT_PROXY'          => '',
        'CURLOPT_COOKIE'         => '',
        'CURLOPT_FOLLOWLOCATION' => true,
        'CURLOPT_MAXREDIRS'      => 5,
    ];


    static protected $domain = "";

    /**
     * @var $client \GuzzleHttp\Client
     */
    protected $client;


    static public function clearOptions()
    {
        self::$curlOptions = [
            'CURLOPT_USERAGENT'      => "Mozilla/5.0 (Windows NT 5.1; zh-CN) AppleWebKit/535.12 (KHTML, like Gecko) Chrome/22.0.1229.79 Safari/535.12",
            'CURLOPT_SSL_VERIFYPEER' => 0, //https验证
            'CURLOPT_SSL_VERIFYHOST' => 0,//https验证
            'CURLOPT_HTTPHEADER'     => [

            ],
            'CURLOPT_RETURNTRANSFER' => 1,//设置获取的信息以文件流的形式返回，而不是直接输出
            'CURLOPT_REFERER'        => "http://www.baidu.com", //在HTTP请求中包含一个'referer'头的字符串
            'CURLOPT_TIMEOUT'        => 5,
            'CURLOPT_PROXY'          => '',
            'CURLOPT_COOKIE'         => ''
        ];
        return new static();

    }

    /**
     * author: mtg
     * time: 2021/6/8   10:15
     * function description:设置curl请求选项
     * @param array $curlOptions
     */
    static public function setOptions(string $domain = "", array $curlOptions = [])
    {
        if ($domain) {
            self::$domain = $domain;
        }
        foreach (self::$curlOptions as $key => $curlOption) {
            if ($value = data_get($curlOptions, $key)) {
                if (is_array($curlOption)) {
                    self::$curlOptions[$key] = array_merge(self::$curlOptions[$key], $value);
                } else {
                    self::$curlOptions[$key] = $value;
                }
            }
        }

        return new static();
    }

    /**
     * author: mtg
     * time: 2021/6/15   11:18
     * function description:通过采集模型设置采集选项
     * @param Gather $model
     */
    static public function setOptionsByModel(Gather $model)
    {
        if ($model->agent) {
            self::setOptions("", [
                'CURLOPT_PROXY' => $model->agent,
            ]);
        }
        if ($model->user_agent) {
            self::setOptions('', [
                'CURLOPT_USERAGENT' => $model->user_agent
            ]);
        }

        if ($model->header) {
            self::setOptions('', [
                'CURLOPT_HTTPHEADER' => collect(explode("\r\n", $model->header))->map(function ($value) {
                    return trim($value);
                })->toArray()

            ]);
        }
        self::$domain = self::getDomain($model->begin_url);


        return new static();
    }


    /**
     * author: mtg
     * time: 2021/6/8   10:23
     * function description:get请求
     * @param string $url
     * @param callable $fulfilled
     * @param callable|null $rejected
     * @return mixed|void
     */
    static public function get(string $url)
    {

        return self::request($url);
    }


    static public function post(string $url, $data, $isJSON = false)
    {

        if ($isJSON) {
            $data = json_encode($data);
            self::setOptions('', [
                'CURLOPT_HTTPHEADER' => [
                    'Content-Type:application/json'
                ]
            ]);
        }


        $res = self::request($url, $data);


        $data = json_decode($res, true);

        if (is_null($data)) {
            return $res;
        }


        return $data;
    }

    /**
     * author: mtg
     * time: 2021/7/3   16:06
     * function description: 将$url对应的文件下载到$fullpath
     * @param string $url
     * @param $fullPath string 包含文件名的路径
     * @return mixed
     */

    static public function download(string $url, $fullPath)
    {
        $resource = self::get($url);

        $path = dirname($fullPath);

        if (!is_dir($path)) {
            FileService::createDir($path);
        }
        file_put_contents($fullPath, $resource);
        unset($resource);
        return $fullPath;
    }


    /**
     * author: mtg
     * time: 2021/9/24   15:45
     * function description:补充完善链接
     * @param $url
     */
    static public function completeURL($url)
    {

        if (Str::startsWith($url, '//')) { //对//www.17k.com/book/3269136.html这种url进行处理


            $url = trim($url, '/');

            if (strpos(self::$domain, 'https') !== false) {
                $url = "https://" . $url;
            } else {
                $url = "http://" . $url;
            }

        }

        if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
            if (!self::$domain) {
                new CurlException("无法拼接url", 502);
            }
            $url = trim(self::$domain, '/') . '/' . trim($url, '/');
        }

        return $url;
    }

    /**
     * author: mtg
     * time: 2021/6/8   10:15
     * function description:
     * @param string $url
     * @return bool|string
     */
    static public function request(string $url, $data = [])
    {

        return retry(3, function () use ($url, $data) {

            $url = self::completeURL($url);

            $ch = curl_init();

            $options = self::$curlOptions;

            gather_log(sprintf("======curl 开始对%s进行请求=======", $url));

            curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt($ch, CURLOPT_USERAGENT, $options['CURLOPT_USERAGENT']);

            curl_setopt($ch, CURLOPT_REFERER, $options['CURLOPT_REFERER']);


            if ($options['CURLOPT_HTTPHEADER']) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $options['CURLOPT_HTTPHEADER']);
            }


            curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

            curl_setopt($ch, CURLOPT_TIMEOUT, $options['CURLOPT_TIMEOUT']);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, $options['CURLOPT_RETURNTRANSFER']);

            curl_setopt($ch, CURLOPT_MAXREDIRS, $options['CURLOPT_MAXREDIRS']);        //设置最大的重定向次数

            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $options['CURLOPT_FOLLOWLOCATION']);  //跟随重定向


            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $options['CURLOPT_SSL_VERIFYPEER']);

            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $options['CURLOPT_SSL_VERIFYHOST']);


            if ($options['CURLOPT_PROXY']) {
                curl_setopt($ch, CURLOPT_PROXY, $options['CURLOPT_PROXY']);

            }
            if ($options['CURLOPT_COOKIE']) {
                curl_setopt($ch, CURLOPT_COOKIE, $options['CURLOPT_COOKIE']);
            }

            if ($data) { //post提交
                curl_setopt($ch, CURLOPT_POST, 1);

                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }


            $res = curl_exec($ch);

            if (curl_errno($ch)) {

                $error  = curl_error($ch);
                $status = curl_errno($ch);

                throw new CurlException($error, $status);
            }


            // gather_log(curl_getinfo($ch, CURLINFO_HEADER_OUT));

            //   gather_log('获得响应数据为', $res);
            gather_log(sprintf("=====curl 对%s进行请求结束======", $url));

            curl_close($ch);

            return $res;
        }, 0.5, function (\Exception $e) use ($url) {
            gather_log(sprintf("curl 请求%s失败,失败状态码为:%s,失败原因为:%s", $url, $e->getCode(), $e->getMessage()));
            if ($e->getCode() === 56 || $e->getCode() === 52) //56 Recv failure: Connection was reset | 52 Empty reply from server
            {
                throw new \Exception("该链接无法访问,可能需要vpn访问");
            }
            if ($e->getCode() == 28) {
                throw new \Exception("访问超时,请稍候访问或将访问超时时间增大");

            }
            if ($e instanceof CurlException) {
                return true;
            }

            return false;
        });


    }

    /**
     * author: mtg
     * time: 2021/6/12   17:03
     * function description: 当url中包含[]时,需要替换为合法url,如http://www.xiuren.org/page-[1-400].html
     * @param string $beginURL
     * @return array|string
     */
    static public function parseURLs(string $beginURL): array
    {
        $URLs = [];
        if (strpos($beginURL, '[') !== false && strpos($beginURL, ']') !== false) {
            preg_match_all('|\[(\d+-\d+)\]|', $beginURL, $args);

            if ($range = data_get($args, '1.0')) {

                list($begin, $end) = explode('-', $range);
                for ($i = $begin; $i <= $end; $i++) {
                    $URLs[] = str_replace("[$range]", $i, $beginURL);
                }
                return $URLs;
            }
        }
        return [$beginURL];

    }

    /**
     * author: mtg
     * time: 2021/6/5   14:19
     * function description:将后台填入的多行正则解析成代码可以使用的正则表达式
     * @param string $str
     * @return Collection
     */
    static public function parsePatterns(string $str): Collection
    {
        $patterns = explode(PHP_EOL, $str);

        $patterns = collect($patterns)->map(function ($value) {
            return '|' . trim($value, "\r") . '|';
        });
        return $patterns;
    }

    /**
     * author: mtg
     * time: 2021/6/5   14:19
     * function description:将后台填入的多行正则解析成代码可以使用的正则表达式,正则表达式为key,正则前后如果包含*,value将为true
     * @param string $str
     * @return Collection
     */
    static public function parseAsteriskPatterns(string $str): Collection
    {
        if (trim($str) == "") {
            return collect([]);
        }
        $patterns = explode(PHP_EOL, $str);

        $patterns = collect($patterns)->mapWithKeys(function ($pattern) {

            $pattern = trim($pattern);

            if (Str::startsWith($pattern, '*') && Str::endsWith($pattern, '*')) {

                return ['|' . trim($pattern, '*') . '|' => true];
            }
            return ['|' . trim($pattern) . '|' => false];

        });
        return $patterns;
    }

    /**
     * author: mtg
     * time: 2021/6/4   18:07
     * function description:获取url的域名部分
     * @param string $url
     * @return mixed
     */
    static public function getDomain(string $url)
    {
        $parts = parse_url($url);
        return data_get($parts, 'scheme') . '://' . data_get($parts, 'host');
    }

    /**
     * author: mtg
     * time: 2021/6/4   18:09
     * function description:获取url的路径部分
     * @param string $url
     * @return array|mixed
     */
    static public function getPath(string $url)
    {
        return data_get(parse_url($url), 'path');
    }


    /**
     * author: mtg
     * time: 2021/6/4   18:51
     * function description:获取html中的所有url
     * @param string $content
     * @return array|mixed
     */
    static public function getHtmlURLs(string $content): Collection
    {
        preg_match_all('|href="([^"]+)"|', $content, $URLsDouble);

        preg_match_all("|href='([^']+)'|", $content, $URLsSingle);


        return collect(data_get($URLsDouble, 1, []))->merge(data_get($URLsSingle, 1, []));
    }


    /**
     * author: mtg
     * time: 2021/6/5   10:43
     * function description:移除不相关的字符,包含html标签,\t,\r,\n等其他特殊字符
     * @param string $html
     */
    static public function stripIrrelevantChars(string $html)
    {
        $html = strip_tags($html);
        $html = trim(str_ireplace(["\r", "\t", "\n", ' ', '　　'], '', $html));
        return $html;
    }

    /**
     * author: mtg
     * time: 2021/6/5   11:19
     * function description:将整篇内容分隔成句子
     * @param string $content
     * @return Collection
     */
    static public function split2Sentence(string $content, $delimiters = ["。"]): Collection
    {

        $sentences = array_reduce($delimiters, function ($carry, $delimiter) {
            return $carry->map(function ($item) use ($delimiter) {
                return explode($delimiter, $item);
            })->flatten();
        }, collect([$content]));

        return $sentences->map(function ($value) {
            return trim($value, ' ');
        });
    }

    /**
     * author: mtg
     * time: 2021/6/7   11:08
     * function description:提取并加入res中所有符合条件的url
     * @param string $res
     * @param string $regulars
     */
    static public function extractAndPushUrls(string $res, string $regulars, callable $filterFun = null)
    {
        $patterns = static::parseAsteriskPatterns($regulars);


        if ($patterns->isEmpty()) {
            return [[], []];
        }
        $URLs = static::getHtmlURLs($res);


        $matchURLs       = [];
        $filterMatchURLs = [];
        foreach ($URLs as $url) {
            $url = str_replace(' ', '', $url);
            $url = str_replace("\t", '', $url);
            foreach ($patterns as $pattern => $isFilter) {

                if (preg_match($pattern, $url)) {
                    $matchURLs[] = $url;
                    if ($filterFun && $filterFun($url, $isFilter)) {
                        $filterMatchURLs [] = $url;
                    }
                }

            }
        }
        return [$matchURLs, array_unique($filterMatchURLs)];

    }


    /**
     * author: mtg
     * time: 2021/9/27   16:20
     * function description:采集的图片,通过路径转换为url
     */
    static public function imagePathToURL($path)
    {
        $temp = explode('images', $path);

        $imageURL = Storage::url('images' . data_get($temp, 1));

        return $imageURL;
    }

    /**
     * author: mtg
     * time: 2021/9/27   17:56
     * function description:采集的图片完整路径转为相对路径
     * @param $path
     * @return string
     */
    static public function imageFullPathToRelative($path)
    {
        $temp = explode('images', $path);

        return '/seo/images/' . trim(data_get($temp, 1), '/');
    }

    /**
     * author: mtg
     * time: 2021/6/4   18:18
     * function description: 闭包绑定当前对象
     * @param callable $callback
     * @return \Closure
     */
    public function bindThis(callable $callback)
    {
        return \Closure::bind($callback, $this, "static");
    }
}
