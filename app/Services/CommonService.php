<?php

namespace App\Services;

use App\Constants\SpiderConstant;
use App\Models\OperationLog;
use App\Models\Website;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * 通用服务类
 *
 * Class CommonService
 * @package App\Services
 */
class CommonService extends BaseService
{
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
                'status'    => false,
                'message'   => $message,
                'display'   => [],
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

        for ($i = 0; $size >= 1024 && $i < count($units) -1; $i++ ) {
            $size = bcdiv($size, 1024, 2);
        }

        return $size . $units[$i];
    }

    /**
     * 获取自定义方法跳转连接
     *
     * @param string $url           连接地址
     * @param string $name          连接名称
     * @param string $buttonType    按钮类型
     * @param integer $interval     间隔
     * @return void
     */
    public static function getActionJumpUrl(
        string $url,
        string $name,
        string $buttonType = 'default',
        int $interval = 10
    ) {
        return "<a class='btn btn-sm btn-{$buttonType} form-history-bac' style='float: right;margin-left: {$interval}px;' href='{$url}'><i class='fa fa-mail-forward'></i>&nbsp;{$name}</a>";
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
        preg_match_all('/./u',$str,$matches);
     
        $unicodeStr = "";
        foreach($matches[0] as $m){
            //拼接
            $unicodeStr .= "&#".base_convert(bin2hex(iconv('UTF-8',"UCS-4",$m)),16,10);
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
        if (!empty($matches))
        {
            $name = '';
            for ($j = 0; $j < count($matches[0]); $j++)
            {
                $str = $matches[0][$j];
                if (strpos($str, '\\u') === 0)
                {
                    $code = base_convert(substr($str, 2, 2), 16, 10);
                    $code2 = base_convert(substr($str, 4), 16, 10);
                    $c = chr($code).chr($code2);
                    $c = iconv('UCS-2', 'UTF-8', $c);
                    $name .= $c;
                }
                else
                {
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
     * @param array|string $stores  如果是多个, 就是字符串数组
     * @param string $separator     分隔符
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

    /**
     * 将换行的字符串或数组转为数组返回
     *
     * @param string|array $data
     * @return array
     */
    public static function linefeedStringToArray($data, $pattern='/\r\n/')
    {
        $tempArr = [];

        if (is_array($data)) {
            foreach ($data as $store) {
                $storeArr = preg_split($pattern, $store, -1, PREG_SPLIT_NO_EMPTY);
                $tempArr = array_merge($tempArr, $storeArr);
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
    public static function getCategoryId($host='')
    {
        // 获取当前域名信息
        if (empty($host)) {
            $host = request()->getHost();
        }

        $hostArr = explode('.', $host);

        $newHostArr = [];
        for ($i=0; $i<2; $i++) {
            array_unshift($newHostArr, array_pop($hostArr));
        }

        $newHost = implode('.', $newHostArr);
        $wwwNewHost = 'www.' . $newHost;

        // 判断不带www的域名是否存在
        $newHostWeb = Website::where('url', $newHost)->first();
        if (!empty($newHostWeb)) {
            return $newHostWeb->category_id;
        }
        // 判断带www的是否存在
        $wwwNewHostWeb = Website::where('url', $wwwNewHost)->first();
        if (!empty($wwwNewHostWeb)) {
            return $wwwNewHostWeb->category_id;
        }
        // 判断完整的域名数据库中是否存在
        $hostWeb = Website::where('url', $host)->first();
        if (!empty($hostWeb)) {
            return $hostWeb->category_id;
        }

        return 0;
    }

    /**
     * 获取官网公告
     *
     * @return void
     */
    public static function getNotices($perpage=5, $page=1)
    {
        $officialDomain = config('seo.official_domain');
        $url = $officialDomain."/index/version/getNotice?perpage={$perpage}&page={$page}";

        $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n")); 
        $context = stream_context_create($opts); 
        try {
            $result = file_get_contents($url,false,$context);
    
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
        $textractor = new Textractor();
        $contentResult = $textractor->parse($content);
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
        $key = 'baidu_dropdown_words_' . $keyword;

        if (Cache::has($key) && !empty(Cache::get($key))) {
            $words = json_decode(Cache::get($key), true);
        } else {
            try {
                $url = "https://www.baidu.com/sugrec?pre=1&p=3&ie=utf-8&json=1&prod=pc&from=pc_web&wd=".$keyword."&req=2&bs=2&csor=1&cb=jQuery110204561448478299086_1626659204769&_=1626659204771";
    
                $tempRes = file_get_contents($url);
                // 正则匹配内容
                preg_match('#\[{(.*?)}\]#', $tempRes, $data);
    
                $jsonData = $data[0];
                $arrData = json_decode($jsonData, true);
    
                $words = array_column($arrData, 'q');
    
                Cache::put($key, json_encode($words), now()->addMinutes(10));
            } catch (Exception $e) {
                $words = [];
            }
        }

        if (empty($words)) {
            return '';
        }

        $count = count($words) - 1;
        $result = $words[mt_rand(0, $count)] ?: '';

        return $result;
    }
}
