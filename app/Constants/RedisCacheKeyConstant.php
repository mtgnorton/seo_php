<?php

namespace App\Constants;

class RedisCacheKeyConstant
{


    /**
     * 配置缓存key
     */
    const  CACHE_CONFIGS_KEY = 'configs_data';


    /**
     * 限制并发访问数量
     */
    const REDIS_LIMIT = 'redis_limit';
    /**
     * 搜狗最后推送时间缓存key
     */
    const SOUGOU_PUSH_TIME_KEY = 'sougou_last_push_time';

    /**
     * 搜狗推送错误缓存
     */
    const SOUGOU_PUSH_ERROR = 'sougou_last_push_error';

    const SOUGOU_PUSH_AMOUNT = 'sougou_push_amount';
    /**
     * @var string 蜘蛛记录redis key
     */
    const REDIS_SPIDER_RECORDS = 'spider_records';

    /**
     * @var string  百度推送总数
     */
    const BAIDU_PUSH_AMOUNT = 'baidu_push_amount';

    /**
     * @var string  蜘蛛头部标识
     */
    const CACHE_SPIDER_USER_AGENTS = 'spider_user_agents';

    /**
     * @var string  域名缓存
     */
    const CACHE_WEBSITES = 'all_websites';

    /**
     * @var string  内容分类缓存
     */
    const CACHE_CONTENT_CATEGORIES = 'all_content_categories';

    /**
     * @var string  模板缓存
     */
    const CACHE_TEMPLATES = 'all_templates';

    /**
     * @var string  物料库: 文章,标题,网站名称,栏目,句子,图片,视频,关键词,自定义缓存
     */
    const CACHE_CONTENT_ARTICLE = 'all_articles';
    const CACHE_CONTENT_TITLE = 'all_titles';
    const CACHE_CONTENT_WEBSITE_NAME = 'website_name';
    const CACHE_CONTENT_COLUMN = 'all_columns';
    const CACHE_CONTENT_SENTENCE = 'all_sentences';
    const CACHE_CONTENT_IMAGE = 'all_images';
    const CACHE_CONTENT_VIDEO = 'all_videos';
    const CACHE_CONTENT_KEYWORD = 'all_keywords';
    const CACHE_CONTENT_DIY = 'all_diys';

    /**
     * 内容库内容上传时的key
     */
    const CACHE_CONTENT_INSERT_KEY = 'content_insert';

    /**
     * 蜘蛛的数量
     */
    const REDIS_SPIDER_COUNT = 'spider_count_';
    /**
     * 蜘蛛的访问比率
     */
    const REDIS_SPIDER_PIE = 'spider_pie_';
    /**
     * 蜘蛛的访问时段
     */
    const REDIS_SPIDER_HOUR = 'spider_hour_';

    /**
     * 删除的分类
     */
    const CACHE_DELETE_CATEGORY = 'delete_category_';
    /**
     * 删除的分组
     */
    const CACHE_DELETE_GROUP = 'delete_group_';
    /**
     * 删除的模板
     */
    const CACHE_DELETE_TEMPLATE = 'delete_template_';
    /**
     * 删除的内容分类
     */
    const CACHE_DELETE_CONTENT_TEMPLATE = 'delete_content_template_';
}
