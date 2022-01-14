<?php

namespace App\Utils\AiContent;

use App\Services\CommonService;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class BaseAiContent
{
    /**
     * 代理信息
     *
     * @var array
     */
    private $proxyData;

    /**
     * user-agent头信息
     *
     * @var string
     */
    private $ua;

    /**
     * 可以结尾的标点符号
     *
     * @var array
     */
    private $marks = [
        ',','，','。',
        ';','；','"','”',
        '~','》','!','！',
        '?','？'
    ];

    public function __construct()
    {
        $this->proxyData = $this->getRandProxyAccount();
        $this->ua = $this->getRandPCUA();
    }

    /**
     * 发起ip池抓取搜索引擎
     *
     * @param string $url           要访问的网址
     * @return array
     */
    public function curlSearch($url, $header=[])
    {
        try {
            $proxyData = $this->proxyData;
            // 代理IP
            $proxyIp = $proxyData['ip'] ?? '';
            // 代理端口
            $proxyPort = $proxyData['port'] ?? '';
            // 用户名
            $user = $proxyData['user'] ?? '';
            // 密码
            $pass = $proxyData['pass'] ?? '';
            // 代理厂家
            $name = $proxyData['name'] ?? '未知';
    
            $loginpassw = $user . ':' . $pass;
    
            $ch = curl_init();
            if (empty($header)) {
                $header = [
                    'User-Agent: '.$this->ua
                ];
            }
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort);
            curl_setopt($ch, CURLOPT_PROXY, $proxyIp);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    
            $data = curl_exec($ch);

            if (empty($data)) {
                common_log('厂家: '.$name.', 抓取搜索引擎数据失败, 失败原因为: '.curl_error($ch), null, [], 'ai-content');
            }
            
            curl_close($ch);

            return [
                'data' => $data ?: '',
                'name' => $name ?? '未知',
            ];
        } catch (Exception $e) {
            common_log('厂家: '.$name??'未知'.', 抓取搜索引擎失败, 失败地址为: '.$url, $e, [], 'ai-content');

            return '';
        }
    }

    /**
     * 随机获取代理池账号
     *
     * @return array
     */
    public function getRandProxyAccount()
    {
        $defaultData = [
            'ip' => '',
            'port' => '',
            'user' => '',
            'pass' => '',
            'name' => '未知'
        ];
        $proxyAccounts = CommonService::PROXY_ACCOUNTS;

        $result = Arr::random($proxyAccounts) ?: $defaultData;

        return $result;
    }

    /**
     * 获取随机PC user-agent头
     *
     * @return string
     */
    public function getRandPCUA()
    {
        $file = Storage::disk('store')->get('pcua.txt');

        $asciiData = CommonService::linefeedStringToArray($file);

        $result = Arr::random($asciiData) ?: '';

        return $result;
    }

    /**
     * 去除字符串最后一个字符后的内容
     *
     * @param string $content
     * @return void
     */
    public function detachLastMarkWords($content='')
    {
        $marks = $this->marks;
        $reverseArr = array_reverse(mb_str_split($content));

        foreach ($reverseArr as $key => $val) {
            if (in_array($val, $marks)) {
                break;
            }

            unset($reverseArr[$key]);
        }

        $result = implode('', array_reverse($reverseArr));

        return $result;
    }
}
