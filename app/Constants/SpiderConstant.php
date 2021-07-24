<?php

namespace App\Constants;

class SpiderConstant
{
    const SPIDER_BAIDU = 'baidu'; // 百度
    const SPIDER_BAIDU_RENDER = 'baiduRender'; // 百度渲染
    const SPIDER_GOOGLE = 'google'; // 谷歌
    const SPIDER_QIHOO = 'qihoo'; // 奇虎360
    const SPIDER_SOUGOU = 'sougou'; // 搜狗
    const SPIDER_SHENMA = 'shenma'; // 神马
    const SPIDER_TOUTIAO = 'toutiao'; // 今日头条
    const SPIDER_OTHER = 'other';
    const SPIDER_SOUSOU = 'sousou'; // 搜搜
    const SPIDER_SOUSOU_IMAGE = 'sousouImage'; // 搜搜
    const SPIDER_YAHOO = 'yahoo'; // 雅虎
    const SPIDER_YOUDAO = 'youdao'; // 有道
    const SPIDER_EASOU = 'easou'; // 易搜
    const SPIDER_EXABOT = 'exabot'; // 法国一个蜘蛛
    const SPIDER_BING = 'bing'; // bing必应
    const SPIDER_ALEXA = 'alexa'; // Alexa
    const SPIDER_AOL = 'aol'; // Alexa
    const SPIDER_IASK = 'iask'; // Alexa
    const SPIDER_ALTAVISTA = 'altavista'; // Alexa
    const SPIDER_LYCOS = 'lycos'; // Alexa
    const SPIDER_ALLTHEWEB = 'alltheweb'; // Alexa
    const SPIDER_INKTOMI = 'inktomi';
    const SPIDER_GIGABLAST = 'gigablast';

    /**
     * 转换方式
     *
     * @return array
     */
    public static function typeText()
    {
        return [
            '' => ll('All'),
            self::SPIDER_BAIDU => lp(ucfirst(self::SPIDER_BAIDU), 'Spider'),
            self::SPIDER_GOOGLE => lp(ucfirst(self::SPIDER_GOOGLE), 'Spider'),
            self::SPIDER_QIHOO => lp(ucfirst(self::SPIDER_QIHOO), 'Spider'),
            self::SPIDER_SOUGOU => lp(ucfirst(self::SPIDER_SOUGOU), 'Spider'),
            self::SPIDER_SHENMA => lp(ucfirst(self::SPIDER_SHENMA), 'Spider'),
            self::SPIDER_TOUTIAO => lp(ucfirst(self::SPIDER_TOUTIAO), 'Spider'),
            self::SPIDER_OTHER => lp(ucfirst(self::SPIDER_OTHER), 'Spider'),
        ];
    }

    /**
     * 蜘蛛规则
     *
     * @return void
     */
    public static function ruleText()
    {
        return [
            self::SPIDER_BAIDU => ['Baiduspider'],
            self::SPIDER_GOOGLE => ['Googlebot'],
            self::SPIDER_QIHOO => ['360Spider'],
            self::SPIDER_SOUGOU => ['Sogou'],
            self::SPIDER_SHENMA => ['YisouSpider'],
            self::SPIDER_TOUTIAO => ['Bytespider'],
            self::SPIDER_OTHER => [
                'bot', 'crawl', 'spider',
                'slurp', 'sohu-search', 'lycos',
                'robozilla'
            ],
        ];
    }

    /**
     * 转换方式
     *
     * @return array
     */
    public static function allTypeText()
    {
        return [
            self::SPIDER_BAIDU => lp(ucfirst(self::SPIDER_BAIDU), 'Spider'),
            self::SPIDER_GOOGLE => lp(ucfirst(self::SPIDER_GOOGLE), 'Spider'),
            self::SPIDER_QIHOO => lp(ucfirst(self::SPIDER_QIHOO), 'Spider'),
            self::SPIDER_SOUGOU => lp(ucfirst(self::SPIDER_SOUGOU), 'Spider'),
            self::SPIDER_SHENMA => lp(ucfirst(self::SPIDER_SHENMA), 'Spider'),
            self::SPIDER_SOUSOU => lp(ucfirst(self::SPIDER_SOUSOU), 'Spider'),
            self::SPIDER_INKTOMI => lp(ucfirst(self::SPIDER_INKTOMI), 'Spider'),
            self::SPIDER_TOUTIAO => lp(ucfirst(self::SPIDER_TOUTIAO), 'Spider'),
            self::SPIDER_YOUDAO => lp(ucfirst(self::SPIDER_YOUDAO), 'Spider'),
            self::SPIDER_EASOU => lp(ucfirst(self::SPIDER_EASOU), 'Spider'),
            self::SPIDER_ALEXA => lp(ucfirst(self::SPIDER_ALEXA), 'Spider'),
            self::SPIDER_AOL => lp(ucfirst(self::SPIDER_AOL), 'Spider'),
            self::SPIDER_LYCOS => lp(ucfirst(self::SPIDER_LYCOS), 'Spider'),
            self::SPIDER_YAHOO => lp(ucfirst(self::SPIDER_YAHOO), 'Spider'),
            self::SPIDER_EXABOT => lp(ucfirst(self::SPIDER_EXABOT), 'Spider'),
            self::SPIDER_IASK => lp(ucfirst(self::SPIDER_IASK), 'Spider'),
            self::SPIDER_ALLTHEWEB => lp(ucfirst(self::SPIDER_ALLTHEWEB), 'Spider'),
            self::SPIDER_BING => lp(ucfirst(self::SPIDER_BING), 'Spider'),
            self::SPIDER_BAIDU_RENDER => lp(ucfirst(self::SPIDER_BAIDU_RENDER), 'Spider'),
            self::SPIDER_GIGABLAST => lp(ucfirst(self::SPIDER_GIGABLAST), 'Spider'),
            self::SPIDER_ALTAVISTA => lp(ucfirst(self::SPIDER_ALTAVISTA), 'Spider'),
            self::SPIDER_SOUSOU_IMAGE => lp(ucfirst(self::SPIDER_SOUSOU_IMAGE), 'Spider'),
        ];
    }

    /**
     * 蜘蛛规则
     *
     * @return void
     */
    public static function allRuleText()
    {
        return [
            self::SPIDER_BAIDU => ['Baiduspider'],
            self::SPIDER_GOOGLE => ['Googlebot'],
            self::SPIDER_QIHOO => ['360Spider'],
            self::SPIDER_SOUGOU => ['Sogou'],
            self::SPIDER_SHENMA => ['YisouSpider'],
            self::SPIDER_TOUTIAO => ['Bytespider'],
            self::SPIDER_SOUSOU => ['Sosospider', 'Sosoimagespider'],
            self::SPIDER_YAHOO => ['Yahoo'],
            self::SPIDER_YOUDAO => ['YoudaoBot'],
            self::SPIDER_EASOU => ['EasouSpider'],
            self::SPIDER_BAIDU_RENDER => ['Baiduspider-render'],
            self::SPIDER_SOUSOU_IMAGE => ['Sosoimagespider'],
            self::SPIDER_EXABOT => ['Exabot'],
            self::SPIDER_BING => ['bingbot'],
            self::SPIDER_ALEXA => ['ia_archiver'],
            self::SPIDER_AOL => ['sqworm'],
            self::SPIDER_IASK => ['iaskspider'],
            self::SPIDER_ALTAVISTA => ['scooter'],
            self::SPIDER_LYCOS => ['lycos_spider_'],
            self::SPIDER_ALLTHEWEB => ['lycos_spider_'],
            self::SPIDER_INKTOMI => ['Slurp'],
            self::SPIDER_GIGABLAST => ['Gigabot'],
        ];
    }
}
