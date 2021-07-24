<?php

namespace App\Constants;

use Encore\Admin\Layout\Content;

class GatherConstant
{


    const TYPE_SENTENCE = ContentConstant::TYPE_SENTENCE;
    const TYPE_TITLE = ContentConstant::TYPE_TITLE;
    const TYPE_ARTICLE = ContentConstant::TYPE_ARTICLE;
    const TYPE_IMAGE = ContentConstant::TYPE_IMAGE;

    static public function typeText()
    {
        return [
            self::TYPE_SENTENCE => ll('Gather sentence'),
            self::TYPE_TITLE    => ll('Gather title'),
            self::TYPE_ARTICLE  => ll('Gather full'),
            self::TYPE_IMAGE    => ll('Gather image'),
        ];
    }


    const DELIMITER_CHINESE_COMMA = '，';
    const DELIMITER_CHINESE_END = '。';
    const DELIMITER_ENGLISH_COMMA = ',';
    const DELIMITER_ENGLISH_END = '.';


    static public function delimiterText()
    {
        return [
            self::DELIMITER_CHINESE_COMMA => ll('中文逗号'),
            self::DELIMITER_CHINESE_END   => ll('中文句号'),
            self::DELIMITER_ENGLISH_COMMA => ll('英文逗号'),
            self::DELIMITER_ENGLISH_END   => ll('英文句号'),
        ];

    }

    const USER_AGENT_BAIDU = 'baidu';
    const USER_AGENT_GOOGLE = 'google';
    const USER_AGENT_CUSTOM = 'custom';
    const USER_AGENT_DEFAULT = 'default';

    static public function userAgentText()
    {
        return [
            self::USER_AGENT_DEFAULT => ll('默认'),
            self::USER_AGENT_BAIDU   => ll('百度'),
            self::USER_AGENT_GOOGLE  => ll('谷歌'),
            self::USER_AGENT_CUSTOM  => ll('自定义'),
        ];
    }

    const USER_AGENT_BAIDU_CONTENT = 'Baiduspider/2.0+(+http://www.baidu.com/search/spider.htm)';
    const USER_AGENT_GOOGLE_CONTENT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
    const USER_AGENT_CUSTOM_CONTENT = '';
    const USER_AGENT_DEFAULT_CONTENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.77 Safari/537.36 Edg/91.0.864.37';


}





