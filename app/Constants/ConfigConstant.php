<?php

namespace App\Constants;

/**
 * 配置常量
 */
class ConfigConstant
{
    const SIMPLIFIED_TO_TRADITIONAL = 'simp2trad';
    const CHINESE_TO_ENGLISH = 'cn2en';

    /**
     * 转换方式
     *
     * @return array
     */
    public static function transWayText()
    {
        return [
            self::SIMPLIFIED_TO_TRADITIONAL => ll(ucfirst(self::SIMPLIFIED_TO_TRADITIONAL)),
            // self::CHINESE_TO_ENGLISH => ll(ucfirst(self::CHINESE_TO_ENGLISH)),
        ];
    }

    const SENTENCE_TRANSFORM = 'sentence';
    const ALL_SITE_TRANSFORM = 'site';

    /**
     * 转换方式
     *
     * @return array
     */
    public static function transTypeText()
    {
        return [
            self::SENTENCE_TRANSFORM => lp('Sentence', 'Transform'),
            self::ALL_SITE_TRANSFORM => lp('All site', 'Transform'),
        ];
    }

    const USE_SYSTEM = 'open_system';
    const USE_DIY = 'open_diy';
    
    /**
     * 转换方式
     *
     * @return array
     */
    public static function transDistrubOpenTypeText()
    {
        return [
            self::USE_SYSTEM => lp('Use', 'System'),
            self::USE_DIY => lp('Use', 'Diy'),
        ];
    }

    const HEAD = 'header';
    const FOOTER = 'footer';

    /**
     * 转换方式
     *
     * @return array
     */
    public static function transDistrubPositionTypeText()
    {
        return [
            self::HEAD => ll('Header'),
            self::FOOTER => ll('Footer'),
        ];
    }

    const SYNONYM_TRANSFORM_TYPE_SYSTEM = 'system';
    const SYNONYM_TRANSFORM_TYPE_DIY = 'diy';
    const SYNONYM_TRANSFORM_TYPE_BOTH = 'both';

    /**
     * 转换方式
     *
     * @return array
     */
    public static function synonymTransTypeText()
    {
        return [
            self::SYNONYM_TRANSFORM_TYPE_SYSTEM => lp('System'),
            self::SYNONYM_TRANSFORM_TYPE_DIY => lp('Diy'),
            self::SYNONYM_TRANSFORM_TYPE_BOTH => lp('Synonym both'),
        ];
    }

    const PINYIN_CONTENT = 'content';
    const PINYIN_SITE = 'site';

    /**
     * 转换方式
     *
     * @return array
     */
    public static function pinyinTypeText()
    {
        return [
            self::PINYIN_CONTENT => lp('Content'),
            self::PINYIN_SITE => lp('All site'),
        ];
    }
}
