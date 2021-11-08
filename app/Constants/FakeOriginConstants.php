<?php

namespace App\Constants;

class FakeOriginConstants
{

    const TYPE_SYNONYM = 'synonym';
    const TYPE_GOOGLE = 'google';
    const TYPE_5118 = '5118';


    static public function typeText()
    {
        return [
            self::TYPE_SYNONYM => '智能AI转换',
            // self::TYPE_GOOGLE  => '免费-谷歌',
            self::TYPE_5118    => '收费-5118.com',

        ];
    }

    static public function errText($code)
    {

        $map = [
            '100101' => '调用次数不够,请充值',
            '100102' => '服务每秒调用量超限',
            '100103' => '服务每小时调用量超限',
            '100104' => '服务每天调用量超限',
            '100201' => 'url无法解析',
            '100202' => '请求缺少apikey',
            '100203' => '无效的apikey',
            '100204' => 'api不存在',
            '100205' => 'api已经关闭',
            '100206' => '服务商响应status非200',
            '100207' => '服务商未正确接入',
            '100208' => '请求方式不支持',
            '100301' => 'Api商城 内部错误',
            '100302' => '请求服务商过程中错误',
            '100303' => '系统繁忙稍候再试',
            '200107' => '服务器异常或超时',
            '200201' => '传进参数为空',
            '200500' => '内容长度不能超过150个字符',
        ];

        return data_get($map, $code, '伪原创未知错误');
    }


    const ARTICLE_IMAGE_LOCAL = 'article_image_local';

    const ARTICLE_IMAGE_REMOTE = 'article_image_remote';

    static public function articleImageText()
    {
        return [
            self::ARTICLE_IMAGE_LOCAL  => '是',
            // self::TYPE_GOOGLE  => '免费-谷歌',
            self::ARTICLE_IMAGE_REMOTE => '否',

        ];
    }
}
