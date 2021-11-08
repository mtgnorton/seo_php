<?php

namespace App\Services;

use App\Models\Config;

/**
 * 配置服务类
 */
class ConfigService extends BaseService
{
    /**
     * 默认站点配置数据
     */
    const SITE_PARAMS = [
        'sentence_transform' => [
            'transform_way' => '',
            'transform_type' => '',
            'is_open' => 'off',
            'is_ignore_dtk' => 'off',
        ],
        'content_relevance' => 'off',
        'unicode_dtk' => 'off',
        'ascii_article' => 'off',
        'ascii_description' => 'off',
        'add_bracket' => 'off',
        'keyword_chain' => [
            'is_open' => 'off',
            'times' => '',
        ],
        'forbin_snapshot' => 'off',
        'synonym_transform' => [
            'is_open' => 'off',
            'type' => 'system',
            'insert_type' => '',
            'content' => '',
        ],
        'rand_pinyin' =>[
            'is_open' => 'off',
            'type' => 'site'
        ],
        'template_disturb' => [
            'is_open' => 'off',
            'use_type' => 'off',
            'position_type' => 'off',
            'content' => 'off',
        ],
        'is_refresh_change' => 'off',
        'is_category' => 'off'
    ];

    /**
     * 默认广告配置数据
     */
    const AD_PARAMS = [
        'is_open' => 'off',
        'type' => 'all',
    ];

    /**
     * 默认广告配置数据
     */
    const CACHE_PARAMS = [
        'is_open' => 'off',
        'spider_open' => 'off',
        'cache_time' => [
            'is_open' => 'off',
            'index' => '',
            'list' => '',
            'detail' => '',
        ],
    ];

    /**
     * 新增默认站点配置数据
     *
     * @param App\Models\Group $group
     * @return void
     */
    public static function addDefaultSite($group)
    {
        $baseData = [
            'module' => 'site',
            'category_id' => $group->category_id,
            'group_id' => $group->id,
        ];

        self::add($baseData, self::SITE_PARAMS);
    }

    /**
     * 新增默认广告配置数据
     *
     * @param App\Models\Group $group
     * @return void
     */
    public static function addDefaultAd($group)
    {
        $baseData = [
            'module' => 'ad',
            'category_id' => $group->category_id,
            'group_id' => $group->id,
        ];

        self::add($baseData, self::AD_PARAMS);
    }

    /**
     * 新增默认广告配置数据
     *
     * @param App\Models\Group $group
     * @return void
     */
    public static function addDefaultCache($group)
    {
        $baseData = [
            'module' => 'cache',
            'category_id' => $group->category_id,
            'group_id' => $group->id,
        ];

        self::add($baseData, self::CACHE_PARAMS);
    }

    /**
     * 加入数据库
     *
     * @param array $baseData
     * @param array $data
     * @return void
     */
    public static function add($baseData, $data)
    {

        foreach ($data as $key => $value) {
            $extraData = [
                'key' => $key,
                'value' => $value,
            ];
            if (is_array($value)) {
                $extraData['is_json'] = 1;
            }
            $data = array_merge($baseData, $extraData);
            Config::create($data);
        }
    }
}
