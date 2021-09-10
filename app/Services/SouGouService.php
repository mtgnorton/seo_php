<?php

namespace App\Services;

use App\Services\Gather\CrawlService;
use Imagick;
use SVG\SVG;

class SouGouService
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
        while ($retryAmount < 3) {

            gather_log("开始第" . ($retryAmount + 1) . "次搜狗推送尝试");

            self::getVerifyCode($cookie);

            $rs = self::identifyCode();


            if ($rs['state'] == 1) {
                $submitRs = self::submit($links, $rs['code'], $cookie);
                $msg      = data_get($submitRs, 'msg', '推送失败');

                if (strpos($msg, 'success') !== false) { //成功直接返回
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


    public static function submit($data, $code, $cookie)
    {

        if (is_string($data)) {
            $data = array_filter(explode(PHP_EOL, $data));
        }


        $data = [
            'code'      => $code,
            'email'     => conf('sougoupush.email'),
            'reason'    => "",
            'site_type' => 1,
            'sites'     => $data,
            'urls'      => implode('\n', $data)
        ];

        $url = 'https://zhanzhang.sogou.com/api/feedback/addMultiShensu';

        $rs = CrawlService::setOptions('', [
            'CURLOPT_COOKIE' => $cookie
        ])->post($url, $data, true);

        gather_log('推送完成,推送结果为:', $rs);

        return $rs;
    }


    public static function identifyCode()
    {

        $api = self::getVerifyCodeObj();

        // 通过文件进行验证码识别,请使用自己的图片文件替换
        $file_name = self::verifyCodePath();
        // 具体类型可以查看官方网站的价格页选择具体的类型，不清楚类型的，可以咨询客服
        $predict_type = 30400;
        $img_data     = file_get_contents($file_name); // 通过文件路径获取图片数据
        // 识别图片：
        // 多网站类型时，需要增加src_url参数，具体请参考api文档: http://docs.fateadm.com/web/#/1?page_id=6
        // echo $api->PredictExtend($predict_type,$img_data);       // 识别接口，只返回识别结果
        $rsp = $api->Predict($predict_type, $img_data);  // 识别接口，返回识别结果的详细信息

        $msg   = data_get($rsp, 'ErrMsg');
        $id    = data_get($rsp, 'RequestId');
        $state = 0;
        $code  = data_get(json_decode(data_get($rsp, 'RspData')), 'result');

        gather_log('验证码识别完成,结果为:', compact('code', 'msg', 'id'));

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

        $url = 'https://zhanzhang.sogou.com/api/user/info';//个人中心

        $res = CrawlService::setOptions('', [
                'CURLOPT_COOKIE' => $cookie
            ]
        )->get($url);


        $res = json_decode($res, true);

        $username = data_get($res, 'data.user_name');


        gather_log(sprintf('cookie判断,用户名为:%s', $username));


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
        $app_id  = conf('sougoupush.app_id');
        $app_key = conf('sougoupush.app_key');
        //pd账号id&key
        $pd_id  = conf('sougoupush.pd_id');
        $pd_key = conf('sougoupush.pd_key');

        $api = new IdentifyService($app_id, $app_key, $pd_id, $pd_key);
        return $api;
    }


    public static function getVerifyCode($cookie)
    {

        $rs = CrawlService::setOptions('', [
            'CURLOPT_COOKIE' => $cookie
        ])->get('https://zhanzhang.sogou.com/api/user/generateVerifCode?timer=1626945845906');


        $im = new Imagick();
        /*loop to colImagickor each state as needed, something like*/
        $idColorArray = array(
            "AL" => "339966"
        , "AK"   => "0099FF"

        , "WI"   => "FF4B00"
        , "WY"   => "A3609B"
        );

        $svg = '<?xml version="1.0"?>' . $rs;
        foreach ($idColorArray as $state => $color) {
            $svg = preg_replace(
                '/id="' . $state . '" style="fill:#([0-9a-f]{6})/'
                , 'id="' . $state . '" style="fill:#' . $color
                , $svg
            );
        }

        $im->readImageBlob($svg);
        /*png settings*/
        $im->setImageFormat("png24");
        // $im->resizeImage(720, 445, imagick::FILTER_LANCZOS, 1);  /*Optional, if you need to resize*/

        $im->writeImage(self::verifyCodePath());/*(or .jpg)*/
        $im->clear();
        gather_log('获取验证码成功');
    }

    static public function verifyCodePath(): string
    {

        return './storage/aa.png';
    }

    static public function cookie()
    {
        $cookies = conf('sougoupush.cookies');
        $cookies = explode(PHP_EOL, $cookies);

        return collect($cookies)->random();
    }


}
