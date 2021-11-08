<?php

namespace App\Services;

use App\Services\Gather\CrawlService;
use Imagick;
use SVG\SVG;

class QihooSerivice
{


    public static function flow($links = [])
    {

        $state = 0;
        $msg   = "cookie 失效";

        $cookie      = self::cookie(); //获取随机cookie
        $isEffective = self::judgeCookie($cookie);

        if (!$isEffective) {
            return [
                'state' => $state,
                'msg'   => $msg . $cookie
            ];
        }

        $retryAmount = 0;
        while ($retryAmount < 1) {

            gather_log("开始第" . ($retryAmount + 1) . "次360推送尝试");

            self::getVerifyCode($cookie);

            $rs = self::identifyCode();


            if ($rs['state'] == 1) {
                $submitRs = self::submit($links, $rs['code'], $cookie);

                $msg = data_get($submitRs, 'info', '推送失败');

                if (strpos($msg, '提交成功') !== false) { //成功直接返回
                    $state = 1;
                    $rs    = compact('state', 'msg');
                    return $rs;
                } else {
                    if (strpos($msg, '验证码') !== false) {
                        self::markCodeError($rs['id']);
                    }
                    $rs = compact('state', 'msg');
                }

            } else {
                $msg = data_get($rs, 'msg');
                $rs  = compact('msg', 'state');
            }
            $retryAmount++;

        }

        return $rs;

    }


    public static function submit($links, $code, $cookie)
    {

        if (is_string($links)) {
            $links = array_filter(explode(PHP_EOL, $links));
        }

        $temp = array_fill_keys([0, 1, 2, 3, 4], '');


        $links = array_merge($links, $temp);

        $links = array_filter($links);

        $data = [
            'checkcode' => $code,
            'url'       => $links,
            '__hash__'  => '89cdda93e6adc7b13430432969faf6ca_8fab878d9e4334ed28d388c95917ab6e',
        ];
        gather_log('360开始推送,推送参数为:', $data);


        $url = 'https://zhanzhang.so.com/?m=PageInclude&a=upload';

//        $url = 'http://seo.local/test';
        $rs = CrawlService::setOptions('', [
            'CURLOPT_COOKIE'     => $cookie,
            'CURLOPT_HTTPHEADER' => [
                'Referer:https://zhanzhang.so.com/sitetool/page_include'
            ]
        ])->post($url, http_build_query($data), false);

        gather_log('360推送完成,推送结果为:', $rs);

        return $rs;
    }


    public static function identifyCode()
    {

        $api = self::getVerifyCodeObj();

        // 通过文件进行验证码识别,请使用自己的图片文件替换
        $file_name = self::verifyCodePath();
        // 具体类型可以查看官方网站的价格页选择具体的类型，不清楚类型的，可以咨询客服
        $predict_type = 20400;
        $img_data     = file_get_contents($file_name); // 通过文件路径获取图片数据
        // 识别图片：
        // 多网站类型时，需要增加src_url参数，具体请参考api文档: http://docs.fateadm.com/web/#/1?page_id=6
        // echo $api->PredictExtend($predict_type,$img_data);       // 识别接口，只返回识别结果
        $rsp = $api->Predict($predict_type, $img_data);  // 识别接口，返回识别结果的详细信息

        $msg   = data_get($rsp, 'ErrMsg');
        $id    = data_get($rsp, 'RequestId');
        $state = 0;
        $code  = data_get(json_decode(data_get($rsp, 'RspData')), 'result');

        gather_log('360验证码识别完成,结果为:', compact('code', 'msg', 'id'));

        if (data_get($rsp, 'RetCode') < 1 && $code) { //没有问题
            $state = 1;
            return compact('state', 'code', 'msg', 'id');
        }


        return compact('state', 'code', 'msg', 'id');
    }

    /**
     * author: mtg
     * time: 2021/7/26   15:49
     * function description:判断用户的cookie是否有效
     * @return false
     */
    public static function judgeCookie($cookie)
    {

        $url = 'https://zhanzhang.so.com/sitetool/site_manage';//个人中心

        $res = CrawlService::setOptions('', [
                'CURLOPT_COOKIE' => $cookie
            ]
        )->get($url);


        preg_match('/USERNAME: "([^"]*+)",/', $res, $match);


        $username = data_get($match, '1');

        gather_log(sprintf('cookie判断,用户名为:%s', $username));

        sleep(3);
        return !!$username;


    }

    public static function markCodeError($id)
    {
        $api = self::getVerifyCodeObj();

        //识别的结果如果与预期不符，可以调用这个接口将预期不符的订单退款
        //退款仅在正常识别出结果后，无法通过网站验证的情况，请勿非法或者滥用，否则可能进行封号处理

        $rsp = $api->Justice($id);
        gather_log('标记验证码错误,响应结果为:', $rsp);
    }

    public static function getVerifyCodeObj()
    {
        $app_id  = conf('qihoopush.app_id');
        $app_key = conf('qihoopush.app_key');
        //pd账号id&key
        $pd_id  = conf('qihoopush.pd_id');
        $pd_key = conf('qihoopush.pd_key');

        $api = new IdentifyService($app_id, $app_key, $pd_id, $pd_key);
        return $api;
    }


    public static function getVerifyCode($cookie)
    {

        $rs = CrawlService::setOptions('', [
            'CURLOPT_COOKIE'     => $cookie,
            'CURLOPT_HTTPHEADER' => [
                'Referer:https://zhanzhang.so.com/sitetool/page_include'
            ]])->download('https://zhanzhang.so.com/index.php?a=checkcode&m=Utils&t=1633658194800', app()->basePath('storage/bb.png'));


        gather_log('获取360验证码成功');
        sleep(3);
    }

    static public function verifyCodePath(): string
    {

        return './storage/bb.png';
    }

    static public function cookie()
    {
        return 'PHPSESSID=1l021j1mqk06po12afb5k8hr66; QiHooGUID=F7A12FA0119856ED85B3238FA3FB5DF7.1633655686808; __guid=137774715.4409531837863191600.1633655686941.861; Qs_lvt_100433=1633655686; test_cookie_enable=null; Q=u%3D360H766983187%26n%3D%26le%3DZGR2ZQNjZQZ0AFH0ZUSkYzAioD%3D%3D%26m%3D%26qid%3D766983187%26im%3D1_t01923d359dad425928%26src%3Dpcw_so_zhanzhang%26t%3D1; __NS_Q=u%3D360H766983187%26n%3D%26le%3DZGR2ZQNjZQZ0AFH0ZUSkYzAioD%3D%3D%26m%3D%26qid%3D766983187%26im%3D1_t01923d359dad425928%26src%3Dpcw_so_zhanzhang%26t%3D1; T=s%3Db8a5497d684d351b241f06ce1580d7e3%26t%3D1633655696%26lm%3D%26lf%3D1%26sk%3Dfa5b6ed61498eccc0a3539da751cb855%26mt%3D1633655696%26rc%3D%26v%3D2.0%26a%3D1; __NS_T=s%3Db8a5497d684d351b241f06ce1580d7e3%26t%3D1633655696%26lm%3D%26lf%3D1%26sk%3Dfa5b6ed61498eccc0a3539da751cb855%26mt%3D1633655696%26rc%3D%26v%3D2.0%26a%3D1; Qs_pv_100433=2690532486950330000%2C3311754788975448600%2C2443731625296281000';
        $cookies = conf('sougoupush.cookies');
        $cookies = explode(PHP_EOL, $cookies);

        return collect($cookies)->random();
    }


}
