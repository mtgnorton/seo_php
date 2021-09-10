<?php

namespace App\Constants;

/**
 * 内容常量
 */
class ContentConstant
{
    const TYPE_ARTICLE = 'article';
    const TYPE_TITLE = 'title';
    const TYPE_WEBSITE_NAME = 'website_name';
    const TYPE_COLUMN = 'column';
    const TYPE_SENTENCE = 'sentence';
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_KEYWORD = 'keyword';
    const TYPE_DIY = 'diy';

    public static function typeText()
    {
        return [
            self::TYPE_ARTICLE      => ll(self::TYPE_ARTICLE),
            self::TYPE_TITLE        => ll(self::TYPE_TITLE),
            self::TYPE_WEBSITE_NAME => ll(self::TYPE_WEBSITE_NAME),
            self::TYPE_COLUMN       => ll(self::TYPE_COLUMN),
            self::TYPE_SENTENCE     => ll(self::TYPE_SENTENCE),
            self::TYPE_IMAGE        => ll(self::TYPE_IMAGE),
            self::TYPE_VIDEO        => ll(self::TYPE_VIDEO),
            self::TYPE_KEYWORD      => ll(self::TYPE_KEYWORD),
            self::TYPE_DIY          => ll(self::TYPE_DIY),
        ];
    }

    static public function storageTypeText(...$types)
    {
        if (count($types) == 0) {
            return [
                self::TYPE_ARTICLE  => ll(self::TYPE_ARTICLE),
                self::TYPE_TITLE    => ll(self::TYPE_TITLE),
                self::TYPE_SENTENCE => ll(self::TYPE_SENTENCE),
                self::TYPE_IMAGE    => ll(self::TYPE_IMAGE),
            ];
        }

        $rs = [];
        foreach ($types as $type) {
            $rs[$type] = ll($type);
        }
        return $rs;

    }

    public static function tagText()
    {
        return [
            self::TYPE_ARTICLE      => ll(ucfirst(self::TYPE_ARTICLE)),
            self::TYPE_TITLE        => ll(ucfirst(self::TYPE_TITLE)),
            self::TYPE_WEBSITE_NAME => ll(ucfirst(self::TYPE_WEBSITE_NAME)),
            self::TYPE_COLUMN       => ll(ucfirst(self::TYPE_COLUMN)),
            self::TYPE_SENTENCE     => ll(ucfirst(self::TYPE_SENTENCE)),
            self::TYPE_IMAGE        => ll(ucfirst(self::TYPE_IMAGE)),
            self::TYPE_VIDEO        => ll(ucfirst(self::TYPE_VIDEO)),
            self::TYPE_KEYWORD      => ll(ucfirst(self::TYPE_KEYWORD)),
        ];
    }

    public static function cacheKeyText()
    {
        return [
            self::TYPE_ARTICLE      => RedisCacheKeyConstant::CACHE_CONTENT_ARTICLE,
            self::TYPE_TITLE        => RedisCacheKeyConstant::CACHE_CONTENT_TITLE,
            self::TYPE_WEBSITE_NAME => RedisCacheKeyConstant::CACHE_CONTENT_WEBSITE_NAME,
            self::TYPE_COLUMN       => RedisCacheKeyConstant::CACHE_CONTENT_COLUMN,
            self::TYPE_SENTENCE     => RedisCacheKeyConstant::CACHE_CONTENT_SENTENCE,
            self::TYPE_IMAGE        => RedisCacheKeyConstant::CACHE_CONTENT_IMAGE,
            self::TYPE_VIDEO        => RedisCacheKeyConstant::CACHE_CONTENT_VIDEO,
            self::TYPE_KEYWORD      => RedisCacheKeyConstant::CACHE_CONTENT_KEYWORD,
            self::TYPE_DIY          => RedisCacheKeyConstant::CACHE_CONTENT_DIY,
        ];
    }
}
