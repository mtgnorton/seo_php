<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ContentCategory;
use App\Models\Diy;
use App\Models\Tag;
use App\Models\Website;
use Fukuball\Jieba\Finalseg;
use Fukuball\Jieba\Jieba;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use sqhlib\Hanzi\HanziConvert;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Keyword;
use App\Models\Sentence;
use App\Services\Pinyin;
use Illuminate\Support\Facades\Storage;

/**
 * 首页正则服务类
 *
 * Class IndexPregService
 * @package App\Services
 */
class IndexPregService extends BaseService
{
    /**
     * 内容模型
     *
     * @var array
     */
    const CONTENT_MODEL = [
        'title'           => 'App\Models\Title',
        'website_name'    => 'App\Models\WebsiteName',
        'column'          => 'App\Models\Column',
        'sentence'        => 'App\Models\Sentence',
        'image'           => 'App\Models\Image',
        'video'           => 'App\Models\Video',
        'keyword'         => 'App\Models\Keyword',
        'article_content' => 'App\Models\Article',
        'article_title'   => 'App\Models\Article',
        'article'         => 'App\Models\Article',
        'diy'             => 'App\Models\Diy',
    ];

    /**
     * 内容字段
     *
     * @var array
     */
    const CONTENT_COLUMN = [
        'title'           => 'content',
        'website_name'    => 'content',
        'column'          => 'content',
        'sentence'        => 'content',
        'image'           => 'url',
        'video'           => 'url',
        'keyword'         => 'content',
        'article_content' => 'content',
        'article_title'   => 'title',
        'diy'             => 'content',
        'article'         => '',
    ];

    const CONTENT_TAG = [
        'title'           => '标题',
        'website_name'    => '网站名称',
        'column'          => '栏目',
        'sentence'        => '句子',
        'image'           => '图片',
        'video'           => '视频',
        'keyword'         => '关键词',
        'article_content' => '文章内容',
        'article_title'   => '文章题目',
    ];

    const FIXED_TAG = [
        '标题',
        '文章题目',
        '文章内容',
        '句子',
    ];

    /**
     * 获取随机字符串
     *
     * @param integer $length 长度
     * @param string $type 类型
     * @return string
     */
    public static function randCode(int $length = 5, int $type = 1)
    {
        $data = [
            1 => "abcdefghijklmnopqrstuvwxyz",
            2 => "123456789",
            3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz",
            4 => "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789",
            5 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
        ];
        // 判断类型是否存在, 如果不存在, 则默认1
        if (array_key_exists($type, $data)) {
            $content = $data[$type];
        } else {
            $content = $data[1];
        }

        $code  = '';
        $count = mb_strlen($content) - 1;

        for ($i = 0; $i < $length; $i++) {
            $code .= $content[mt_rand(0, $count)] ?? '';
        }

        return $code;
    }

    /**
     * 根据类型不同随机获取内容
     *
     * @param string $cType      类型
     * @param int $tag          标签
     * @param string $uriType   页面类型
     * @return mixed
     */
    public static function randContent(
        string $cType,
        string $tag = '',
        $uriType = 'index',
        array &$globalData = []
    ) {
        // dump("1. cType: $cType");
        // 获取内容对应数据库的ID数据
        $result = '';
        $contentId = 0;
        static $staticData = [];
        // 当前网址
        $baseUrl = $staticData['baseUrl'] ?? $staticData['baseUrl'] = request()->url();
        // 缓存时间
        $cacheTime = 86400;

        // 获取站点分类ID
        $webCategoryId = $staticData['webCategoryId'] ??
            $staticData['webCategoryId'] = CommonService::getCategoryId();
        // 获取模板分组ID
        $groupId = $staticData['groupId'] ??
            $staticData['groupId'] = TemplateService::getGroupId();
        // 获取当前站点配置
        $config = $staticData['config'] ??
            $staticData['config'] = conf('site', ConfigService::SITE_PARAMS, $webCategoryId, $groupId);

        // 记录原始tag
        $trueTag = $tag;
        // dump("3. trueTag: $trueTag");
        // 去除tag的{}和数字
        $tempTag = trim($tag, '{}');
        $tag     = preg_replace_callback("/[\D]+(\d+)$/", function ($match) {
            $result = rtrim($match[0], $match[1]);

            return $result;
        }, $tempTag);
        // dump("5. tag: $tag");
        // 记录当前tag值
        $tempTrueTag = $tag;
        // dump("6. tempTrueTag: $tempTrueTag");

        // 记录头部标签
        $headTag   = '';
        $fixedTags = self::FIXED_TAG;

        if (in_array($cType, $fixedTags)) {
            $headTag = $cType;
            // 判断带不带头部
            if (in_array($cType, ['标题', '文章题目'])) {
                $tempHeadTag = '头部' . $headTag;
                if (stripos($tag, $tempHeadTag) !== false) {
                    $headTag = $tempHeadTag;
                } else {
                    $headTag = '';
                }
            }
        }
        // dump("8. headTag: $headTag");

        // 只有列表和详情页会刷新不变
        if (in_array($uriType, ['detail', 'list']) &&
            !empty($headTag)
        ) {
            $isRefresh = isset($_SERVER['HTTP_CACHE_CONTROL']) &&
                    $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0';
            // 如果不是刷新, 且是头部标题, 则对应
            if ($isRefresh == false &&
                in_array($headTag, ['头部标题', '头部文章题目'])
            ) {
                // 获取固定刷新不变的变量值
                $refreshFixedContent = CommonService::getRefreshFixedContent(
                    $baseUrl, $globalData,
                    $headTag, $trueTag
                );
                if ($refreshFixedContent['content'] !== false) {
                    $result = $refreshFixedContent['content'];
                    $contentId = $refreshFixedContent['contentId'];
                }
            }

            if (empty($result)) {
                // 判断页面刷新是否改变
                $isNotChange = CommonService::ifRefreshNotChange($config);
                if ($isNotChange) {
                    // 获取固定刷新不变的变量值
                    $fixedContent = CommonService::getFixedContent(
                        $baseUrl, $globalData, $headTag,
                        $trueTag, $cacheTime
                    );
                    if ($fixedContent['content'] !== false) {
                        $result = $fixedContent['content'];
                        $contentId = $fixedContent['contentId'];
                    }
                }
            }
        }
        // 如果是'头部标签', 则去除 '头部', 如果数据存在, 直接返回
        if (in_array($headTag, ['头部标题', '头部文章题目']) && empty($result)) {
            if (isset($globalData[$trueTag]) && !empty($globalData[$trueTag])) {
                $result = $globalData[$trueTag];
            } else {
                $tag = str_replace($headTag, $cType, $tag);
            }
        }

        // 判断分类和数字的类型
        $typeData = self::getContentInfo($cType, $tag);

        // 获取内容类型标识
        $tags     = self::CONTENT_TAG;
        $flipTags = array_flip($tags);
        if (!array_key_exists($cType, $flipTags)) {
            return '';
        }
        $type     = $flipTags[$cType];
        $trueType = $type;
        // 如果是文章标题或者文章内容, 则变为文章标识
        if (in_array($trueType, ['article_title', 'article_content'])) {
            $trueType = 'article';
        }
        // dump("9. type: $type");
        // dump("10. trueType: $trueType");

        // 如果不是已定义的模型和字段, 则返回空
        $models  = self::CONTENT_MODEL;
        $columns = self::CONTENT_COLUMN;
        if (!array_key_exists($type, $models) ||
            !array_key_exists($type, $columns)
        ) {
            return '';
        }
        // 内容对应模型
        $model = $models[$type];
        // 内容对应数据库字段
        $column = $columns[$type];
        // dump("11. headTag: $headTag");
    
        if (empty($result)) {
            $tagQuery = '';
            // 获取分类标签的查询条件
            if ($typeData['is_category'] != 0) {
                // 获取分类名称
                $baseTag = mb_substr($tag, 0, -mb_strlen($cType));
                // dump("12. baseTag: $baseTag");
    
                // 若是文章内容或文章题目, 则转变为文章
                if (in_array($cType, ['文章题目', '文章内容'])) {
                    $tag = mb_substr($tag, 0, mb_strlen($tag) -2);
                    $cType = '文章';
                }
                // dump("13. tag: $tag");
                // dump("14. cType: $cType");
    
                $contentCategory = CommonService::contentCategories([
                    'name'     => $baseTag,
                    'type'     => $trueType,
                    'group_id' => $groupId,
                ], 1);
                if (empty($contentCategory)) {
                    return '';
                }
    
                if ($contentCategory['parent_id'] == 0) {
                    $tagNames = CommonService::contentCategories(['parent_id' => $contentCategory['id']], 0, 'name');
    
                    if (empty($tagNames)) {
                        return '';
                    }
    
                    $tagQuery = [];
                    foreach ($tagNames as $tagName) {
                        $tagQuery[] = $tagName . $cType;
                    }
                } else {
                    $tagQuery = [$tag];
                }
            }
            // 获取当前分组下该类型的所有分类ID
            $categoryIds = CommonService::contentCategories([
                'type'     => $trueType,
                'group_id' => $groupId,
            ], 0, 'id');
    
            $contentIds = [];
            // 查询数据总数
            if (empty(data_get($staticData,"contentResultIds.{$type}.{$tag}"))
            ) {
                $condition = ['category_id' => $categoryIds];
                if ($tagQuery != '') {
                    $condition['tag'] = $tagQuery;
                }
                $tempResult                                  = CommonService::contents($type, $tag, $model, $groupId, $column, $condition, [], 0, [$column, 'id']);
                $staticData['contentResultIds'][$type][$tag] = $tempResult;
            }
            $contentResultIds = $staticData['contentResultIds'];
            $contentIds       = $contentResultIds[$type][$tag];
            if (empty($contentIds) && $tempTrueTag != '头部标题') {
                return '';
            }
    
            if (!empty($contentIds)) {
                $contentId = array_rand($contentIds);
                $result    = $contentIds[$contentId] ?? '';
            } else {
                $contentId = 0;
                $result    = '';
            }
    
            // 删除已使用过的内容ID
            unset($staticData['contentResultIds'][$type][$tag][$contentId]);
    
            // 如果类型是头部标题, 且获取内容为空, 则获取关键词表中的数据
            if (empty($result) && $tempTrueTag == '头部标题') {
                $categoryIds = CommonService::contentCategories([
                    'type'     => 'keyword',
                    'group_id' => $groupId,
                ], 0, 'id');
    
                if (empty(data_get($staticData,"contentResultIds.{$type}.{$tag}"))) {
                    $condition = ['category_id' => $categoryIds];
                    $titleResult = CommonService::contents('keyword', '关键词', $models['keyword'], $groupId, $column, $condition, [], 0, ['content', 'id']);
                    $staticData['contentResultIds'][$type][$tag] = $titleResult;
                }
                $contentResultIds = $staticData['contentResultIds'];
                $contentIds       = $contentResultIds[$type][$tag];
                if (empty($contentIds)) {
                    return '';
                }
    
                $contentId = array_rand($contentIds);
                $result    = $contentIds[$contentId] ?? '';
    
                // 如果不是自定义库就删除已使用过的内容ID
                unset($staticData['contentResultIds'][$type][$tag][$contentId]);
            }
    
            // 如果是文章标题或文章内容, 则先判断缓存中是否有存的模型ID, 如果有, 直接取出返回
            // dump("16. tag: $tag");
            if ($tempTrueTag == '头部文章题目' || $type == 'article_content') {
                if ($tag == '头部文章题目' &&
                    !empty($globalData['头部文章题目'])
                ) {
                    $result = $globalData['头部文章题目'];
                } else {
                    $articleKey = 'now_article_id';
                    if (empty($globalData[$articleKey])) {
                        $globalData[$articleKey] = $contentId;
                    } else {
                        $articleId = $globalData[$articleKey];
        
                        $articleModel = $model::find($articleId);
                        if (!empty($articleModel)) {
                            $result = $articleModel->$column;
                        }
                    }
        
                    if ($tag == '头部文章题目') {
                        $globalData[$tag] = $result;
                    }
                }
            }
        }

        // 添加图片前缀
        if ($type == 'image') {
            if (!empty($result)) {
                // $result = '/seo/'.$result;
                $result = Storage::url($result);
            }
        } else if ($type == 'sentence') {
            // 记录进摘要
            // 判断是否已记录摘要
            // 判断缓存中是否有长度
            ContentService::putSentenceSummary($result, $globalData);
        } else if ($type == 'article_content') {
            ContentService::putSummary($result, $globalData);
        }

        // 开始站点配置
        $sentenceConfig = $config['sentence_transform'];
        // 判断是否打开句子转换开关
        if ($sentenceConfig['is_open'] == 'on' &&
            $sentenceConfig['transform_type'] == 'sentence' &&
            $type == 'sentence'
        ) {
            // 简繁体转换
            if ($sentenceConfig['transform_way'] == 'simp2trad') {
                $result = HanziConvert::convert($result, true);
            } else if ($sentenceConfig['transform_way'] == 'cn2en') {

            }
        }

        // 判断是否开启标题关联内容
        if ($config['content_relevance'] == 'on' && $uriType == 'detail') {
            $titleVal = $globalData['title_value'] ?? [];
            if ($type == 'sentence') {
                static $sentenceNum = 0;

                $sentenceTimeData = $globalData['title_sentence_times'] ?? [];
                $shouldTimes = $sentenceTimeData[$sentenceNum] ?? 0;

                if ($shouldTimes > 0) {
                    $sentenceArr = mb_str_split($result) ?: [];
                    if (!empty($sentenceArr)) {
                        $sentenceCount = count($sentenceArr) - 1;
                        $insertData    = multiple_rand(0, $sentenceCount, $shouldTimes);

                        $afterContent = '';
                        foreach ($sentenceArr as $skey => $sval) {
                            $afterContent .= $sval;
                            if (in_array($skey, $insertData)) {
                                $aTag                      = array_pop($titleVal);
                                $globalData['title_value'] = $titleVal;
                                $afterContent              .= $aTag;
                            }
                        }

                        $result = $afterContent;
                    }
                }
                $sentenceNum++;
            } else if ($type == 'article_content') {
                // 获取所有P标签内容
                $contentTimes = $globalData['title_content_times'] ?? 0;

                $contentResult = CommonService::getHtmlContent($result);
                //mtg
                $sentenceData = array_fill_keys(array_keys($contentResult), 0);

                if (!empty($sentenceData)) {
                    $sentenceTotal = count($sentenceData);
                    if ($contentTimes > $sentenceTotal) {
                        $eachTimes = bcdiv($contentTimes, $sentenceTotal, 0);
                        $remain    = $contentTimes - bcmul($eachTimes, $sentenceTotal, 0);

                        foreach ($sentenceData as $key => &$value) {
                            $value = $value + $eachTimes;
                            if ($remain > 0) {
                                $value++;
                                $remain--;
                            }
                        }
                    } else {
                        $randKeys = multiple_rand(0, $sentenceTotal - 1, $contentTimes);
                        foreach ($sentenceData as $key => &$value) {
                            if (in_array($key, $randKeys)) {
                                $value++;
                            }
                        }
                    }

                    foreach ($contentResult as $key => $val) {
                        $content     = $val;
                        $shouldTimes = $sentenceData[$key] ?? 0;
                        if (empty($shouldTimes)) {
                            continue;
                        }

                        $sentenceArr = mb_str_split($content) ?: [];
                        if (empty($sentenceArr)) {
                            continue;
                        }

                        $sentenceCount = count($sentenceArr) - 1;
                        $insertData    = multiple_rand(0, $sentenceCount, $shouldTimes);

                        $afterContent = '';
                        foreach ($sentenceArr as $sKey => $sVal) {
                            $afterContent .= $sVal;
                            if (in_array($sKey, $insertData)) {
                                $aTag         = array_pop($titleVal);
                                $afterContent .= $aTag;
                            }
                        }

                        $result = str_replace($content, $afterContent, $result);
                    }
                }
            }
        }

        // 判断是否增加括号
        if ($config['add_bracket'] == 'on') {
            if ($type == 'sentence') {
                $afterContent = '';
                $contentArr   = mb_str_split($result, 2);
                foreach ($contentArr as $value) {
                    $afterContent .= '【' . $value . '】';
                }

                $result = $afterContent;
            } else if ($type == 'article_content') {
                // 每两个字增加一次左右括号
                $contentResult = CommonService::getHtmlContent($result);

                foreach ($contentResult as $resKey => $resVal) {
                    $contentArr   = mb_str_split($resVal, 2);
                    $afterContent = '';
                    foreach ($contentArr as $value) {
                        $afterContent .= '【' . $value . '】';
                    }

                    $result = str_replace($resVal, $afterContent, $result);
                }
            }
        }

        // 判断是否插入拼音
        if ($config['rand_pinyin']['is_open'] == 'on' && $config['rand_pinyin']['type'] == 'content') {
            if ($type == 'sentence') {
                $afterContent = '';
                $contentArr   = mb_str_split($result);
                foreach ($contentArr as $value) {
                    $afterContent .= $value;
                    if ($value !== '【' && $value !== '】') {
                        if (mt_rand(1, 10) <= 2) {
                            // 获取拼音
                            $tempValue = HanziConvert::convert($value);
                            $pinyin    = Pinyin::getAllPy($tempValue)[0] ?? '';
                            if (!empty($pinyin)) {
                                $afterContent .= '(' . $pinyin . ')';
                            }
                        }
                    }
                }

                $result = $afterContent;
            } else if ($type == 'article_content') {
                $contentResult = CommonService::getHtmlContent($result);

                foreach ($contentResult as $resKey => $resVal) {
                    $contentArr   = mb_str_split($resVal);
                    $afterContent = '';
                    foreach ($contentArr as $value) {
                        $afterContent .= $value;
                        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $value)) {
                            if (mt_rand(1, 10) <= 2) {
                                // 获取拼音
                                $tempValue = HanziConvert::convert($value);
                                $pinyin    = Pinyin::getAllPy($tempValue)[0] ?? '';
                                if (!empty($pinyin)) {
                                    $afterContent .= '(' . $pinyin . ')';
                                }
                            }
                        }
                    }

                    $result = str_replace($resVal, $afterContent, $result);
                }
            }
        }

        // 判断是否增加ASCII码
        if ($config['ascii_article'] == 'on') {
            if ($type == 'sentence') {
                $afterContent = '';
                $contentArr   = mb_str_split($result, 2);
                $ascStr       = CommonService::getSpecAscii();
                foreach ($contentArr as $value) {
                    $afterContent .= $value;
                    if (mt_rand(1, 10) <= 2) {
                        $afterContent .= $ascStr;
                    }
                }

                $result = $afterContent;
            } else if ($type == 'article_content') {
                $contentResult = CommonService::getHtmlContent($result);

                foreach ($contentResult as $resKey => $resVal) {

                    $pContent     = $resVal;
                    $afterContent = '';
                    $contentArr   = mb_str_split($pContent, 2);
                    foreach ($contentArr as $value) {
                        $afterContent .= $value;
                        if (mt_rand(1, 10) <= 1) {
                            $ascStr       = CommonService::getSpecAscii();
                            $afterContent .= $ascStr;
                        }
                    }

                    $result = str_replace($pContent, $afterContent, $result);
                }
            }
        }

        // 判断是否关键词内链
        if ($config['keyword_chain']['is_open'] == 'on' && $uriType == 'detail') {
            if ($type == 'sentence') {
                static $sentenceNum = 0;

                // 缓存中插入数据
                $sentenceTimeData  = $globalData['keywords_sentence_times_data'] ?? [];
                $shouldTimes       = $sentenceTimeData[$sentenceNum] ?? 0;

                if ($shouldTimes > 0) {
                    $sentenceArr = mb_str_split($result) ?: [];
                    if (!empty($sentenceArr)) {
                        $sentenceCount = count($sentenceArr) - 1;
                        $insertData    = multiple_rand(0, $sentenceCount, $shouldTimes);

                        $afterContent = '';
                        foreach ($sentenceArr as $skey => $sval) {
                            $afterContent .= $sval;
                            if (in_array($skey, $insertData)) {
                                $keywordsCategoryIds = CommonService::contentCategories([
                                    'type'     => 'keyword',
                                    'group_id' => $groupId,
                                ], 0, 'id');
                                $condition = ['category_id' => $keywordsCategoryIds];
                                $aTag = ContentService::getRandKeywordATag($groupId, $condition);
                                $afterContent .= $aTag;
                            }
                        }

                        $result = $afterContent;
                    }
                }
                $sentenceNum++;
            } else if ($type == 'article_content') {
                // 获取所有P标签内容
                $contentTimes = $globalData['keywords_content_times'] ?? 0;

                $sentenceData  = [];
                $contentResult = CommonService::getHtmlContent($result);

                foreach ($contentResult as $key => $val) {
                    $sentenceData[$key] = 0;
                }

                if (!empty($sentenceData)) {
                    $sentenceTotal = count($sentenceData);
                    if ($contentTimes > $sentenceTotal) {
                        $eachTimes = bcdiv($contentTimes, $sentenceTotal, 0);
                        $remain    = $contentTimes - bcmul($eachTimes, $sentenceTotal, 0);

                        foreach ($sentenceData as $key => &$value) {
                            $value = $value + $eachTimes;
                            if ($remain > 0) {
                                $value++;
                                $remain--;
                            }
                        }
                    } else {
                        $randKeys = multiple_rand(0, $sentenceTotal - 1, $contentTimes);
                        foreach ($sentenceData as $key => &$value) {
                            if (in_array($key, $randKeys)) {
                                $value++;
                            }
                        }
                    }

                    foreach ($contentResult as $key => $val) {
                        $content     = $val;
                        $shouldTimes = $sentenceData[$key] ?? 0;
                        if (empty($shouldTimes)) {
                            continue;
                        }

                        $sentenceArr = mb_str_split($content) ?: [];
                        if (empty($sentenceArr)) {
                            continue;
                        }

                        $sentenceCount = count($sentenceArr) - 1;
                        $insertData    = multiple_rand(0, $sentenceCount, $shouldTimes);

                        $afterContent = '';
                        foreach ($sentenceArr as $sKey => $sVal) {
                            $afterContent .= $sVal;
                            if (in_array($sKey, $insertData)) {
                                $keywordsCategoryIds = CommonService::contentCategories([
                                    'type' => 'keyword',
                                    'group_id' => $groupId,
                                ], 0, 'id');
                                $condition = ['category_id' => $keywordsCategoryIds];
                                $aTag = ContentService::getRandKeywordATag($groupId, $condition);
                                $afterContent .= $aTag;
                            }
                        }

                        // $result = preg_replace('/'.$content.'/', $afterContent, $result, 1);
                        $result = str_replace_once($content, $afterContent, $result);
                    }
                }
            }
        }

        // 判断是否同义词转换
        $synonymConfig = $config['synonym_transform'];
        if ($synonymConfig['is_open'] == 'on' && $synonymConfig['insert_type'] == 'content') {
            // $systemData = Storage::disk('store')->get('synonym.txt');
            // $diyData    = $synonymConfig['content'];
            // if ($synonymConfig['type'] == 'system') {
            //     $storeData = $systemData;
            // } else if ($synonymConfig['type'] == 'diy') {
            //     $storeData = $diyData;
            // } else {
            //     $storeData = [
            //         $systemData,
            //         $diyData
            //     ];
            // }
            if (in_array($type, ['sentence', 'article_content'])) {
                static $diyData = null;
                if (is_null($diyData)) {
                    $diyData = $synonymConfig['content'];
                    $diyData = CommonService::storeStringToArray($diyData);
                }
                $systemData = CommonService::getSynonyms();
                if ($synonymConfig['type'] == 'system') {
                    $storeData = $systemData;
                } else if ($synonymConfig['type'] == 'diy') {
                    $storeData = $diyData;
                } else {
                    $storeData = array_merge($systemData, $diyData);
                }
    
                $result = strtr($result, $storeData);
            }
        }

        // 如果是文章题目并且不是头部文章题目, 则返回id和内容
        // dump('----------------------------------');
        if ($type == 'article_title' && $tag !== '头部文章题目') {
            $result = [
                'id'    => $contentId,
                'value' => $result
            ];
        }

        // 如果是刷新不变, 并且是句子, 则将句子缓存下来
        if (in_array($uriType, ['detail', 'list']) && 
            in_array($type, ['sentence', 'article_title', 'article_content'])
        ) {
            // 防止需要刷新一遍才能记住句子内容
            $isNotChange = CommonService::ifRefreshNotChange($config, 'config');
            if ($isNotChange) {
                if ($type == 'sentence') {
                    $sentenceCount = $globalData['sentence_count'] ?? 0;
                    $sentenceArrKey = 'sentence_arr';
                    $sentenceArr = $globalData[$sentenceArrKey] ?? [];
                    $sentenceArr[] = $contentId;
                    if (count($sentenceArr) <= $sentenceCount) {
                        $globalData[$sentenceArrKey] = $sentenceArr;
                    }
                } else {
                    $globalData['key_article_id'] = $contentId;
                }
            }
        }

        return $result;
    }

    /**
     * 根据全匹配和类型判断字符串匹配类型
     *
     * @param string $type 类型(中文)
     * @param string $tag 匹配的字符串
     * @return array
     */
    public static function getContentInfo(string $type, string $tag)
    {
        $result = [
            'is_category' => 0,
            'is_number'   => 0,
        ];
        if (empty($type) || empty($tag)) {
            return $result;
        }

        // 将标签左右的{}去除
        $tag = trim($tag, '{}');
        // 将标签已type为标识进行分割
        $typeArr = explode($type, $tag);
        $number  = array_pop($typeArr);

        // 判断是否含有数字
        if (!empty($number)) {
            $result['is_number'] = 1;
        }

        array_push($typeArr, '');
        $newTag = implode($type, $typeArr);

        if ($newTag != '头部标题' && $newTag != '头部文章题目' && $newTag !== $type) {
            $result['is_category'] = 1;
        }

        return $result;
    }

    /**
     * 获取系统标签内容
     *
     * @param string $type 类型(中文)
     * @param string $tag 匹配的字符串
     * @return array
     */
    public static function randSystemTag(string $type, string $tag)
    {
        $tag = trim($tag, '{}');

        if (in_array($type, ['随机数字', '随机字母', '固定数字', '固定字母'])) {
            $num = (int)str_replace($type, '', $tag) ?: 5;
        }
        if ($type == '时间') {
            $type    = $tag;
            $timeTop = 60 * 60 * 24 * 7;
            $time    = time() - mt_rand(0, $timeTop);
        }

        switch ($type) {
            case '随机数字':
                $result = self::randCode($num, 2);
                break;
            case '随机字母':
                $result = self::randCode($num, 1);
                break;
            case '固定数字':
                $result = self::randCode($num, 2);
                break;
            case '固定字母':
                $result = self::randCode($num, 1);
                break;
            case '时间':
                $result = date('Y-m-d');
                break;
            case '时间1':
                $result = date('Y-m-d H:i:s');
                break;
            case '时间2':
                $result = date('Y-m-d H:i:s', $time);
                break;
            case '时间3':
                $result = date('Y-m-d', $time);
                break;
            case '当前网址':
                $result = request()->url();
                break;
            default:
                $result = '';
                break;
        }

        return $result;
    }

    /**
     * 判断自定义标签是否含有数字
     *
     * @param string $tag 标签
     * @return void
     */
    public static function diyHasNumber(string $tag)
    {
        // 去除标签中的数字
        // $newTag = preg_replace("/\d+/", '', $tag);
        $result = false;
        $tag    = trim($tag, '{}');
        // common_log('调试: 555');
        preg_replace_callback("/[\D]+(\d+)$/", function ($match) use (&$result) {
            // $result = rtrim($match[0], $match[1]);
            // return $result;
            $result = true;
        }, $tag);

        // if ($newTag !== $tag) {
        //     return true;
        // }

        // return false;
        return $result;
    }

    /**
     * 随机获取自定义标签内容
     *
     * @param string $tag 标签
     * @return void
     */
    public static function randDiyContent(string $tag)
    {
        static $diyIds;
        $trueTag = $tag;
        if (!isset($diyIds[$trueTag])) {
            // 去除标签两边的{}
            $tag = trim($tag, '{}');
            // 去除标签中的数字
            // $tag = preg_replace("/\d+/", '', $tag);
            // common_log('调试: 666');
            $tag = preg_replace_callback("/[\D]+(\d+)$/", function ($match) {
                $result = rtrim($match[0], $match[1]);

                return $result;
            }, $tag);

            $groupId = TemplateService::getGroupId();

            $categoryIds = CommonService::contentCategories([
                'type'     => 'diy',
                'group_id' => $groupId,
            ], 0, 'id');

            $condition        = [
                'category_id' => $categoryIds,
                'tag'         => $tag,
            ];
            $diyResult        = CommonService::contents('diy', $tag, 'App\Models\Diy', $groupId, 'content', $condition, [], 0, ['content', 'id']);
            $diyIds[$trueTag] = $diyResult;
        }

        $ids = $diyIds[$trueTag] ?? [];

        $count = count($ids) - 1;

        if ($count < 0) {
            return '';
        }
        $id     = array_rand($ids);
        $result = $ids[$id] ?? '';
        // 删除已使用过的自定义内容ID
        // unset($diyIds[$trueTag][$id]);

        return $result;
    }
}
