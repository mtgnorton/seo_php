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
use Illuminate\Support\Facades\Session;
use sqhlib\Hanzi\HanziConvert;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Keyword;
use App\Services\Pinyin;

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
        'title' => 'App\Models\Title',
        'website_name' => 'App\Models\WebsiteName',
        'column' => 'App\Models\Column',
        'sentence' => 'App\Models\Sentence',
        'image' => 'App\Models\Image',
        'video' => 'App\Models\Video',
        'keyword' => 'App\Models\Keyword',
        'article_content' => 'App\Models\Article',
        'article_title' => 'App\Models\Article',
    ];

    /**
     * 内容字段
     *
     * @var array
     */
    const CONTENT_COLUMN = [
        'title' => 'content',
        'website_name' => 'content',
        'column' => 'content',
        'sentence' => 'content',
        'image' => 'url',
        'video' => 'url',
        'keyword' => 'content',
        'article_content' => 'content',
        'article_title' => 'title',
    ];

    const CONTENT_TAG = [
        'title' => '标题',
        'website_name' => '网站名称',
        'column' => '栏目',
        'sentence' => '句子',
        'image' => '图片',
        'video' => '视频',
        'keyword' => '关键词',
        'article_content' => '文章内容',
        'article_title' => '文章题目',
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
     * @param integer $length   长度
     * @param string $type      类型
     * @return string
     */
    public static function randCode(int $length=5, int $type=1)
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

        $code = '';
        $count = mb_strlen($content) - 1;

        for ($i=0; $i < $length; $i++) {
            $code .= $content[mt_rand(0, $count)] ?? '';
        }

        return $code;
    }

    /**
     * 根据类型不同随机获取内容
     *
     * @param string $type      类型
     * @param int $tag          标签
     * @return void
     */
    public static function randOldContent(string $type, string $tag = '')
    {
        $models = self::CONTENT_MODEL;
        $columns = self::CONTENT_COLUMN;

        if (!array_key_exists($type, self::CONTENT_MODEL)) {
            return '';
        }
        $model = $models[$type];
        $column = $columns[$type];

        $ids = $model::pluck('id');
        // 如果传来标签, 则去除标签两边{}和数字
        if (!empty($tag)) {
            // 去除标签两边的{}
            $tag = trim($tag, '{}');
            // 去除标签中的数字
            $tag = preg_replace("/\\d+/", '', $tag);

            $ids = $model::where('tag', $tag)->pluck('id');
        }

        $count = count($ids) - 1 >= 0 ? count($ids) - 1 >= 0 : 0;

        if ($count < 0) {
            return '';
        }

        $contentModel = $model::find($ids[mt_rand(0, $count)]);
        $result = $contentModel->$column;
        if ($type == 'image') {
            $result = '/seo/'.$contentModel->$column;
        }

        return $result;
    }

    /**
     * 根据类型不同随机获取内容
     *
     * @param string $cType      类型
     * @param int $tag          标签
     * @param string $uriType   页面类型
     * @return void
     */
    public static function randContent(
        string $cType,
        string $tag = '',
        $uriType = 'index',
        array &$globalData = []
    ) {
        static $titleIds = [];
        static $websiteNameIds = [];
        static $columnIds = [];
        static $sentenceIds = [];
        static $imageIds = [];
        static $videoIds = [];
        static $keywordIds = [];
        static $articleIds = [];
        // 判断是不是页面刷新
        $isRefresh = isset($_SERVER['HTTP_CACHE_CONTROL']) &&
                $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0';
        $webCategoryId = CommonService::getCategoryId();
        $groupId = TemplateService::getGroupId();
        // $templateId = TemplateService::getWebsiteTemplateId();
        $config = conf('site', ConfigService::SITE_PARAMS, $webCategoryId, $groupId);
        $isRefreshConfig = $config['is_refresh_change'] ?? 'off';
        $ifNotChange = ($isRefreshConfig == 'off') || ($isRefresh == false);
        // dd($isRefresh, $isRefreshConfig);
        $tag = preg_replace("/\\d+/", '', trim($tag, '{}'));

        // // 头部标题
        // if ($tag == '头部标题' &&
        //     ($uriType == 'detail' || $uriType == 'list') &&
        //     $isRefresh &&
        //     $isRefreshConfig
        // ) {
        //     $key = base64_encode(urldecode(request()->url()));

        //     if (Cache::has($key)) {
        //         $value = Cache::get($key);
        //         $value = base64_decode($value);
        //         $globalData[$tag] = $value;

        //         return $value;
        //     }
        // }

        // 如果是列表页或者详情页, 并且当前页面刷新不变化
        $baseTag = str_replace($cType, '', $tag);
        $headTag = '';
        $fixedTags = self::FIXED_TAG;

        if (in_array($cType, $fixedTags)) {
            $headTag = $cType;
            if (in_array($headTag, ['标题', '文章题目'])) {
                // 判断带不带头部
                $tempHeadTag = '头部' . $headTag;
                if (stripos($tag, $tempHeadTag) !== false) {
                    $headTag = $tempHeadTag;
                }
            }
        }

        if ($uriType == 'detail' || $uriType == 'list') {
            if ($ifNotChange) {
                if ($headTag == '头部标题') {
                    $key = base64_encode(urldecode(request()->url()));

                    if (Cache::has($key)) {
                        $value = Cache::get($key);
                        $value = base64_decode($value);
                        $globalData[$tag] = $value;

                        return $value;
                    }
                } else if ($headTag == '头部文章题目') {
                    $key = base64_encode(urldecode(request()->url() . '_article_id'));

                    if (Cache::has($key)) {
                        $articleId = Cache::get($key);
                        $article = Article::find($articleId);
                        if (!empty($article)) {
                            $value = $article->title;
                            $globalData[$tag] = $value;

                            return $value;
                        }
                    }
                } else if ($headTag == '文章内容') {
                    $key = base64_encode(urldecode(request()->url() . '_article_id'));

                    if (Cache::has($key)) {
                        $articleId = Cache::get($key);
                        $article = Article::find($articleId);
                        if (!empty($article)) {
                            $value = $article->content;
                            $globalData[$tag] = $value;

                            return $value;
                        }
                    }
                } else if ($headTag == '句子') {
                    $sentenceKey = base64_encode(request()->url() . '_sentence_count');
                    $sentenceTotal = Cache::get($sentenceKey, 0);

                    // 获取缓存中的句子数组
                    $key = base64_encode(urldecode(request()->url() . '_sentences'));
                    if (Cache::has($key)) {
                        $tempSentence = Cache::get($key);
                        $sentenceArr = json_decode(base64_decode($tempSentence, true));

                        if (count($sentenceArr) == $sentenceTotal) {
                            $value = array_shift($sentenceArr);
                            $sentenceArr[] = $value;
                            Cache::put($key, base64_encode(json_encode($sentenceArr, JSON_UNESCAPED_UNICODE)));

                            return $value;
                        }
                    }
                }
            } else {
                $key = base64_encode(urldecode(request()->url() . '_sentences'));
                if (Cache::has($key)) {
                    Cache::forget($key);
                }
            }
        }

        // 判断分类和数字的类型
        $typeData = self::getContentInfo($cType, $tag);

        $models = self::CONTENT_MODEL;
        $columns = self::CONTENT_COLUMN;
        $tags = self::CONTENT_TAG;
        $flipTags = array_flip($tags);
        if (!array_key_exists($cType, $flipTags)) {
            return '';
        }
        $type = $flipTags[$cType];
        $trueType = $type;
        if ($type == 'article_title' || $type == 'article_content') {
            $trueType = 'article';
        }

        if (!array_key_exists($type, self::CONTENT_MODEL)) {
            return '';
        }
        $model = $models[$type];
        $column = $columns[$type];

        // $ids = $model::pluck('id');

        static $countModels = [];

        if (is_null(data_get($countModels, $model, null))) { //7.24优化
            $countModels[$model] = $model::count();
        }
        $count = $countModels[$model];

        if ($count <= 0) {
            return '';
        }
        $tagQuery = '';
        // 如果传来标签, 则去除标签两边{}和数字
        if ($headTag == '头部标题' || $headTag == '头部文章题目') {
            if (isset($globalData[$tag]) && !empty($globalData[$tag])) {
                return $globalData[$tag];
            } else {
                $tag = str_replace($headTag, $cType, $tag);
            }
        }

        if ($typeData['is_category'] != 0) {
            // // 去除标签两边的{}
            // $tag = trim($tag, '{}');
            // // 去除标签中的数字
            // $tag = preg_replace("/\\d+$/", '', $tag);
            
            $baseTag = str_replace($cType, '', $tag);

            if ($cType == '文章题目' || $cType == '文章内容') {
                $tag = mb_substr($tag, 0, mb_strlen($tag) -2);
                $cType = mb_substr($cType, 0, mb_strlen($cType) -2);
            }

            // 查询该标签分类中是不是顶级分类
            $contentCategory = ContentCategory::where([
                'name' => $baseTag,
                'type' => $trueType,
                // 'category_id' => $webCategoryId
                'group_id' => $groupId,
            ])->first();

            if (empty($contentCategory)) {
                return '';
            }

            if ($contentCategory->parent_id == 0) {
                $tagNames = $contentCategory->children()->pluck('name')->toArray();

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

            // if ($model::where('tag', $tag)->count() > 0) {
            //     $tagQuery = $tag;
            // } else {
            //     return '';
            // }
                
        }

        // $count = count($ids) - 1;

        // if ($count < 0) {
        //     return '';
        // }

        // $contentModel = $model::find($ids[mt_rand(0, $count)]);
        // if ($tagQuery === '') {
        //     $contentModel = $model::inRandomOrder()->first();
        // } else {
        //     $contentModel = $model::where('tag', $tagQuery)->inRandomOrder()->first();
        //     if (empty($contentModel)) {
        //         $contentModel = $model::inRandomOrder()->first();
        //     }
        // }

        // 随机获取数据
        // $table = (new $model)->getTable();

        // // 获取当前域名信息
        // $host = $_SERVER["HTTP_HOST"];

        // // 根据host的值判断是否已绑定域名
        // $website = Website::where([
        //     'url' => $host,
        //     'is_enabled' => 1
        // ])->first();

        // $categoryIds = $website->category->contentCategories()->pluck('id')->toArray();
        $categoryIds = ContentCategory::where([
            // 'category_id' => $webCategoryId,
            'type' => $trueType,
            'group_id' => $groupId,
            // 'type' => $type
        ])->pluck('id')->toArray();

        // $idsStr = implode(',', $categoryIds);

        // $where = " where category_id in ({$idsStr})";

        // $contentModel = $model::join(
        //     DB::raw("
        //         (
        //             SELECT ROUND(
        //                 RAND() * (
        //                     (SELECT MAX(id) FROM `{$table}`{$where})-(SELECT MIN(id) FROM `{$table}`{$where})
        //                 )+(
        //                     SELECT MIN(id) FROM `{$table}`{$where}
        //                 )
        //             ) AS xid
        //         ) as t2
        //     "),
        //     $table.'.id', '>=', 't2.xid'
        // )->first();
        $contentIds = [];

        // if ($tagQuery === '') {
        //     $where .= "";
        // } else {
        //     $where .= " and tag = '{$tagQuery}'";
        // }

        // 查询数据总数
        // $baseCount = 1000;

        // 获取已获取的内容ID
        $usedIdsKey = base64_encode(request()->url() . '_' . $type . '_used_ids');
        $usedIds = json_decode(Cache::get($usedIdsKey, ''), true) ?: [];
        if ($type == 'title') {
            if (!isset($titleIds[$tag])) {
                // $titleCount = $model::whereIn('category_id', $categoryIds)->count();
                // if ($titleCount < $baseCount) {
                    $query = $model::whereIn('category_id', $categoryIds);
                    if ($tagQuery != '') {
                        $query->whereIn('tag', $tagQuery);
                    }
                    if (!empty($usedIds)) {
                        $query->whereNotIn('id', $usedIds);
                    }
                    $titleIds[$tag] = $query->pluck('id')
                                    ->toArray();
                // } else {
                //     $page = mt_rand(0, bcdiv($titleCount, $baseCount) - 1) * $baseCount;

                //     $query = $model::whereIn('category_id', $categoryIds);
                //     if ($tagQuery != '') {
                //         $query->whereIn('tag', $tagQuery);
                //     }
                //     $titleIds = $query->offset($page)
                //                         ->limit($baseCount)
                //                         ->pluck('id')
                //                         ->toArray();
                // }
                $contentIds = $titleIds[$tag];
            } else {
                $contentIds = $titleIds[$tag];
            }
        } else if ($type == 'website_name') {
            if (!isset($websiteNameIds[$tag])) {
                // $websiteNameCount = $model::whereIn('category_id', $categoryIds)->count();
                // if ($websiteNameCount < $baseCount) {
                    $query = $model::whereIn('category_id', $categoryIds);
                    if ($tagQuery != '') {
                        $query->whereIn('tag', $tagQuery);
                    }
                    if (!empty($usedIds)) {
                        $query->whereNotIn('id', $usedIds);
                    }
                    $websiteNameIds[$tag] = $query->pluck('id')
                                    ->toArray();
                // } else {
                //     $page = mt_rand(0, bcdiv($websiteNameCount, $baseCount) - 1) * $baseCount;

                //     $query = $model::whereIn('category_id', $categoryIds);
                //     if ($tagQuery != '') {
                //         $query->whereIn('tag', $tagQuery);
                //     }
                //     $websiteNameIds[$tag] = $query->offset($page)
                //                         ->limit($baseCount)
                //                         ->pluck('id')
                //                         ->toArray();
                // }
                $contentIds = $websiteNameIds[$tag];
            } else {
                $contentIds = $websiteNameIds[$tag];
            }
        } else if ($type == 'column') {
            if (!isset($columnIds[$tag])) {
                // $columnCount = $model::whereIn('category_id', $categoryIds)->count();
                // if ($columnCount < $baseCount) {
                    $query = $model::whereIn('category_id', $categoryIds);
                    if ($tagQuery != '') {
                        $query->whereIn('tag', $tagQuery);
                    }
                    if (!empty($usedIds)) {
                        $query->whereNotIn('id', $usedIds);
                    }
                    $columnIds[$tag] = $query->pluck('id')
                                    ->toArray();
                // } else {
                //     $page = mt_rand(0, bcdiv($columnCount, $baseCount) - 1) * $baseCount;

                //     $query = $model::whereIn('category_id', $categoryIds);
                //     if ($tagQuery != '') {
                //         $query->whereIn('tag', $tagQuery);
                //     }
                //     $columnIds[$tag] = $query->offset($page)
                //                         ->limit($baseCount)
                //                         ->pluck('id')
                //                         ->toArray();
                // }
                $contentIds = $columnIds[$tag];
            } else {
                $contentIds = $columnIds[$tag];
            }
        } else if ($type == 'sentence') {
            if (!isset($sentenceIds[$tag])) {
                // $sentenceCount = $model::whereIn('category_id', $categoryIds)->count();
                // if ($sentenceCount < $baseCount) {
                    $query = $model::whereIn('category_id', $categoryIds);
                    if ($tagQuery != '') {
                        $query->whereIn('tag', $tagQuery);
                    }
                    if (!empty($usedIds)) {
                        $query->whereNotIn('id', $usedIds);
                    }
                    $sentenceIds[$tag] = $query->pluck('id')
                                    ->toArray();
                // } else {
                    // $page = mt_rand(0, bcdiv($sentenceCount, $baseCount) - 1) * $baseCount;
                //     $page = 0;

                //     $query = $model::whereIn('category_id', $categoryIds);
                //     if ($tagQuery != '') {
                //         $query->whereIn('tag', $tagQuery);
                //     }
                //     $sentenceIds[$tag] = $query->offset($page)
                //                         ->limit($baseCount)
                //                         ->pluck('id')
                //                         ->toArray();
                // }
                $contentIds = $sentenceIds[$tag];
            } else {
                $contentIds = $sentenceIds[$tag];
            }
        } else if ($type == 'image') {
            if (!isset($imageIds[$tag])) {
                // $imageCount = $model::whereIn('category_id', $categoryIds)->count();
                // if ($imageCount < $baseCount) {
                    $query = $model::whereIn('category_id', $categoryIds);
                    if ($tagQuery != '') {
                        $query->whereIn('tag', $tagQuery);
                    }
                    if (!empty($usedIds)) {
                        $query->whereNotIn('id', $usedIds);
                    }
                    $imageIds[$tag] = $query->pluck('id')
                                    ->toArray();
                // } else {
                //     $page = mt_rand(0, bcdiv($imageCount, $baseCount) - 1) * $baseCount;

                //     $query = $model::whereIn('category_id', $categoryIds);
                //     if ($tagQuery != '') {
                //         $query->whereIn('tag', $tagQuery);
                //     }
                //     $imageIds[$tag] = $query->offset($page)
                //                         ->limit($baseCount)
                //                         ->pluck('id')
                //                         ->toArray();
                // }
                $contentIds = $imageIds[$tag];
            } else {
                $contentIds = $imageIds[$tag];
            }
        } else if ($type == 'video') {
            if (!isset($videoIds[$tag])) {
                // $videoCount = $model::whereIn('category_id', $categoryIds)->count();
                // if ($videoCount < $baseCount) {
                    $query = $model::whereIn('category_id', $categoryIds);
                    if ($tagQuery != '') {
                        $query->whereIn('tag', $tagQuery);
                    }
                    if (!empty($usedIds)) {
                        $query->whereNotIn('id', $usedIds);
                    }
                    $videoIds[$tag] = $query->pluck('id')
                                    ->toArray();
                // } else {
                //     $page = mt_rand(0, bcdiv($videoCount, $baseCount) - 1) * $baseCount;

                //     $query = $model::whereIn('category_id', $categoryIds);
                //     if ($tagQuery != '') {
                //         $query->whereIn('tag', $tagQuery);
                //     }
                //     $videoIds[$tag] = $query->offset($page)
                //                         ->limit($baseCount)
                //                         ->pluck('id')
                //                         ->toArray();
                // }
                $contentIds = $videoIds[$tag];
            } else {
                $contentIds = $videoIds[$tag];
            }
        } else if ($type == 'keyword') {
            if (!isset($keywordIds[$tag])) {
                // $keywordCount = $model::whereIn('category_id', $categoryIds)->count();
                // if ($keywordCount < $baseCount) {
                    $query = $model::whereIn('category_id', $categoryIds);
                    if ($tagQuery != '') {
                        $query->whereIn('tag', $tagQuery);
                    }
                    if (!empty($usedIds)) {
                        $query->whereNotIn('id', $usedIds);
                    }
                    $keywordIds[$tag] = $query->pluck('id')
                                    ->toArray();
                // } else {
                //     $page = mt_rand(0, bcdiv($keywordCount, $baseCount) - 1) * $baseCount;

                //     $query = $model::whereIn('category_id', $categoryIds);
                //     if ($tagQuery != '') {
                //         $query->whereIn('tag', $tagQuery);
                //     }
                //     $keywordIds[$tag] = $query->offset($page)
                //                         ->limit($baseCount)
                //                         ->pluck('id')
                //                         ->toArray();
                // }
                $contentIds = $keywordIds[$tag];
            } else {
                $contentIds = $keywordIds[$tag];
            }
        } else if ($type == 'article_content' || $type == 'article_title') {
            if (!isset($articleIds[$tag])) {
                // $articleCount = $model::whereIn('category_id', $categoryIds)->count();
                // if ($articleCount < $baseCount) {
                    $query = $model::whereIn('category_id', $categoryIds);
                    if ($tagQuery != '') {
                        $query->whereIn('tag', $tagQuery);
                    }
                    if (!empty($usedIds)) {
                        $query->whereNotIn('id', $usedIds);
                    }
                    $articleIds[$tag] = $query->pluck('id')
                                    ->toArray();
                // } else {
                //     $page = mt_rand(0, bcdiv($articleCount, $baseCount) - 1) * $baseCount;

                //     $query = $model::whereIn('category_id', $categoryIds);
                //     if ($tagQuery != '') {
                //         $query->whereIn('tag', $tagQuery);
                //     }
                //     $articleIds[$tag] = $query->offset($page)
                //                         ->limit($baseCount)
                //                         ->pluck('id')
                //                         ->toArray();
                // }
                $contentIds = $articleIds[$tag];
            } else {
                $contentIds = $articleIds[$tag];
            }
        }

        // $contentIds = $model::whereIn('category_id', $categoryIds)
        //                         ->pluck('id')->toArray();
        $contentCount = count($contentIds) - 1 < 0 ? 0 : count($contentIds) - 1;

        $contentId = $contentIds[mt_rand(0, $contentCount)] ?? 0;

        // 将新增的ID写入缓存
        $usedIds[] = $contentId;
        Cache::put($usedIdsKey, json_encode($usedIds, JSON_UNESCAPED_UNICODE), 3);

        $contentModel = $model::find($contentId);

        $result = $contentModel->$column ?? '';
        // 如果是文章标题或文章内容, 则先判断缓存中是否有存的模型ID, 如果有, 直接取出返回
        if ($tag == '头部文章题目' || $type == 'article_content') {
            if ($tag == '头部文章题目' &&
                isset($globalData['头部文章题目']) &&
                $globalData['头部文章题目']
            ) {
                return $globalData['头部文章题目'];
            }
            $articleKey = base64_encode(request()->url() . '_article');
            if (Cache::has($articleKey)) {
                $articleId = Cache::pull($articleKey);

                $articleModel = $model::find($articleId);
                if (!empty($articleModel)) {
                    $result = $articleModel->$column;
                }
            } else {
                Cache::put($articleKey, $contentModel->id ?? 0);
            }
            if ($tag == '头部文章题目') {
                $globalData['头部文章题目'] = $result;
            }
        }

        if ($type == 'image') {
            $result = '/seo/'.$result;
        } else if ($type == 'sentence') {
            // 记录进摘要
            // 判断是否已记录摘要
            // 判断缓存中是否有长度
            $summaryLengthKey = base64_encode(request()->url() . '_summary_length');
            if (Cache::has($summaryLengthKey)) {
                $summaryLength = Cache::get($summaryLengthKey);
            } else {
                $summaryLength = mt_rand(50, 60);

                Cache::put($summaryLengthKey, $summaryLength);
            }
            $summaryKey = base64_encode(request()->url() . '_summary');
            if (Cache::has($summaryKey)) {
                $oldSummary = Cache::get($summaryKey);
                // 判断已存的摘要长度是否足够
                $length = mb_strlen($oldSummary);
                if ($length < $summaryLength) {
                    // 判断加上当前句子后的长度是否足够摘要长度
                    $nowLength = mb_strlen($result);
                    if ($nowLength + $length > $summaryLength) {
                        $newSummary = mb_substr($result, 0, $summaryLength - $length);
                        $summary = $oldSummary . $newSummary;

                        Cache::put($summaryKey, $summary);
                    } else {
                        $summary = $oldSummary . $result;

                        Cache::put($summaryKey, $summary);
                    }
                }
            } else {
                // 判断当前句子的长度是否足够
                $length = mb_strlen($result);
                if ($length >= $summaryLength) {
                    // 截取摘要长度的字符串放进句子中
                    $summary = mb_substr($result, 0, $summaryLength);

                    Cache::put($summaryKey, $summary);
                } else {
                    Cache::put($summaryKey, $result);
                }
            }
        } else if ($type == 'article_content') {
            // 获取内容中的p标签内容
            $nodeValues = CommonService::getHtmlContent($result);

            $nodeValues = implode('', $nodeValues);

            $summaryKey = base64_encode(request()->url() . '_summary');
            $summaryLength = mt_rand(50, 60);
            if (!Cache::has($summaryKey)) {
                $summary = mb_substr($nodeValues, 0, $summaryLength);

                Cache::put($summaryKey, $summary);
            }
        }

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
            $titleKey = base64_encode(request()->url() . '_title_value');
            $titleVal = json_decode(Cache::get($titleKey, ''), true) ?: [];

            if ($type == 'sentence') {
                $numKey = base64_encode(request()->url() . '_title_sentence_num');
                $sentenceTimesKey = base64_encode(request()->url() . '_title_sentence_times');
                if (Cache::has($numKey)) {
                    $sentenceNum = Cache::get($numKey);
                } else {
                    Cache::put($numKey, 0);
                    $sentenceNum = 0;
                }

                // 缓存中插入数据
                $sentenceTimeData = json_decode(Cache::get($sentenceTimesKey, ''), true) ?: [];
                $sentenceTimeCount = count($sentenceTimeData);
                $shouldTimes = $sentenceTimeData[$sentenceNum] ?? 0;

                if ($shouldTimes > 0) {
                    $sentenceArr = mb_str_split($result);
                    if (!empty($sentenceArr)) {
                        $sentenceCount = count($sentenceArr) - 1;
                        $insertData = multiple_rand(0, $sentenceCount, $shouldTimes);

                        $afterContent = '';
                        foreach ($sentenceArr as $skey => $sval) {
                            $afterContent .= $sval;
                            if (in_array($skey, $insertData)) {
                                $aTag = array_pop($titleVal);
                                $afterContent .= $aTag;
                            }
                        }

                        $result = $afterContent;
                    }
                }
                $sentenceNum++;
                if ($sentenceNum >= $sentenceTimeCount) {
                    Cache::forget($numKey);
                    Cache::forget($sentenceTimesKey);
                } else {
                    Cache::put($numKey, $sentenceNum);
                }
            } else if ($type == 'article_content') {
                // 获取所有P标签内容
                $timesKey = base64_encode(request()->url() . '_title_content_times');
                $contentTimes = Cache::get($timesKey, 0);

                $sentenceData = [];
                $contentResult = CommonService::getHtmlContent($result);

                foreach ($contentResult as $key => $val) {
                    $sentenceData[$key] = 0;
                }

                if (!empty($sentenceData)) {
                    $sentenceTotal = count($sentenceData);
                    if ($contentTimes > $sentenceTotal) {
                        $eachTimes = bcdiv($contentTimes, $sentenceTotal, 0);
                        $remain = $contentTimes - bcmul($eachTimes, $sentenceTotal, 0);

                        foreach ($sentenceData as $key => &$value) {
                            $value = $value + $eachTimes;
                            if ($remain > 0) {
                                $value++;
                                $remain--;
                            }
                        }
                    } else {
                        $randKeys = multiple_rand(0, $sentenceTotal-1, $contentTimes);
                        foreach ($sentenceData as $key => &$value) {
                            if (in_array($key, $randKeys)) {
                                $value++;
                            }
                        }
                    }

                    foreach ($contentResult as $key => $val) {
                        $content = $val;
                        $shouldTimes = $sentenceData[$key] ?? 0;
                        if (empty($shouldTimes)) {
                            continue;
                        }

                        $sentenceArr = mb_str_split($content);
                        if (empty($sentenceArr)) {
                            continue;
                        }

                        $sentenceCount = count($sentenceArr) - 1;
                        $insertData = multiple_rand(0, $sentenceCount, $shouldTimes);

                        $afterContent = '';
                        foreach ($sentenceArr as $sKey => $sVal) {
                            $afterContent .= $sVal;
                            if (in_array($sKey, $insertData)) {
                                $aTag = array_pop($titleVal);
                                $afterContent .= $aTag;
                            }
                        }

                        $result = preg_replace('/'.$content.'/', $afterContent, $result, 1);
                    }
                }

                Cache::forget($timesKey);
            }

            if (empty($titleVal)) {
                Cache::forget($titleKey);
            }
        }

        // 判断是否增加括号
        if ($config['add_bracket'] == 'on') {
            if ($type == 'sentence') {
                $afterContent = '';
                $contentArr = mb_str_split($result, 2);
                foreach ($contentArr as $value) {
                    $afterContent .= '【'.$value.'】';
                }

                $result = $afterContent;
            } else if ($type == 'article_content') {
                // 每两个字增加一次左右括号
                // $result = preg_replace_callback("/<p>(.*?)<\/p>/", function ($preg) {
                //     $pContent = $preg[1];
                //     $afterContent = '';
                //     $contentArr = mb_str_split($pContent, 2);
                //     foreach ($contentArr as $value) {
                //         $afterContent .= '【'.$value.'】';
                //     }

                //     return "<p>".$afterContent."</p>";
                // }, $result);
                $contentResult = CommonService::getHtmlContent($result);

                foreach ($contentResult as $resKey => $resVal) {
                    $result = preg_replace_callback('/'.$resVal.'/', function ($preg) {
                        $contentArr = mb_str_split($preg[0], 2);
                        $afterContent = '';
                        foreach ($contentArr as $value) {
                            $afterContent .= '【'.$value.'】';
                        }

                        return $afterContent;
                    }, $result, 1);
                }
            }
        }

        // 判断是否插入拼音
        if ($config['rand_pinyin']['is_open'] == 'on' && $config['rand_pinyin']['type'] == 'content') {
            if ($type == 'sentence') {
                $afterContent = '';
                $contentArr = mb_str_split($result);
                foreach ($contentArr as $value) {
                    $afterContent .= $value;
                    if ($value !== '【' && $value !== '】') {
                        if (mt_rand(1, 10) <= 2) {
                            // 获取拼音
                            $tempValue = HanziConvert::convert($value);
                            $pinyin = Pinyin::getAllPy($tempValue)[0] ?? '';
                            if (!empty($pinyin)) {
                                $afterContent .= '('.$pinyin.')';
                            }
                        }
                    }
                }

                $result = $afterContent;
            } else if ($type == 'article_content') {
                // 获取所有P标签内容
                // $result = preg_replace_callback("/<p>(.*?)<\/p>/", function ($preg) {
                //     $pContent = $preg[1];
                //     $afterContent = '';
                //     $contentArr = mb_str_split($pContent);
                //     foreach ($contentArr as $value) {
                //         $afterContent .= $value;
                //         if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $value)) {
                //             if (mt_rand(1, 10) <= 2) {
                //                 // 获取拼音
                //                 $pinyin = Pinyin::getAllPy($value)[0] ?? '';
                //                 if (!empty($pinyin)) {
                //                     $afterContent .= '('.$pinyin.')';
                //                 }
                //             }
                //         }
                //     }

                //     return "<p>".$afterContent."</p>";
                // }, $result);
                $contentResult = CommonService::getHtmlContent($result);

                foreach ($contentResult as $resKey => $resVal) {
                    $result = preg_replace_callback('#'.$resVal.'#i', function ($preg) {
                        $contentArr = mb_str_split($preg[0]);
                        $afterContent = '';
                        foreach ($contentArr as $value) {
                            $afterContent .= $value;
                            if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $value)) {
                                if (mt_rand(1, 10) <= 2) {
                                    // 获取拼音
                                    $tempValue = HanziConvert::convert($value);
                                    $pinyin = Pinyin::getAllPy($tempValue)[0] ?? '';
                                    if (!empty($pinyin)) {
                                        $afterContent .= '('.$pinyin.')';
                                    }
                                }
                            }
                        }

                        return $afterContent;
                    }, $result, 1);
                }
            }
        }

        // 判断是否增加ASCII码
        if ($config['ascii_article'] == 'on') {
            if ($type == 'sentence') {
                $afterContent = '';
                $contentArr = mb_str_split($result, 2);
                $ascStr = CommonService::getSpecAscii();
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
                    $result = preg_replace_callback('/'.$resVal.'/', function ($preg) {
                        $pContent = $preg[0];
                        $afterContent = '';
                        $contentArr = mb_str_split($pContent, 2);
                        foreach ($contentArr as $value) {
                            $afterContent .= $value;
                            if (mt_rand(1, 10) <= 1) {
                                $ascStr = CommonService::getSpecAscii();
                                $afterContent .= $ascStr;
                            }
                        }

                        return $afterContent;
                    }, $result, 1);
                }
            }
        }

        // 判断是否关键词内链
        if ($config['keyword_chain']['is_open'] == 'on' && $uriType == 'detail') {
            if ($type == 'sentence') {
                $numKey = base64_encode(request()->url() . '_keywords_sentence_num');
                $sentenceTimesKey = base64_encode(request()->url() . '_keywords_sentence_times_data');
                if (Cache::has($numKey)) {
                    $sentenceNum = Cache::get($numKey);
                } else {
                    Cache::put($numKey, 0);
                    $sentenceNum = 0;
                }

                // 缓存中插入数据
                $sentenceTimeData = json_decode(Cache::get($sentenceTimesKey, ''), true) ?: [];
                $sentenceTimeCount = count($sentenceTimeData);
                $shouldTimes = $sentenceTimeData[$sentenceNum] ?? 0;

                if ($shouldTimes > 0) {
                    $sentenceArr = mb_str_split($result);
                    if (!empty($sentenceArr)) {
                        $sentenceCount = count($sentenceArr) - 1;
                        $insertData = multiple_rand(0, $sentenceCount, $shouldTimes);

                        $afterContent = '';
                        foreach ($sentenceArr as $skey => $sval) {
                            $afterContent .= $sval;
                            if (in_array($skey, $insertData)) {
                                $aTag = ContentService::getRandKeywordATag();
                                $afterContent .= $aTag;
                            }
                        }

                        $result = $afterContent;
                    }
                }
                $sentenceNum++;
                if ($sentenceNum >= $sentenceTimeCount) {
                    Cache::forget($numKey);
                    Cache::forget($sentenceTimesKey);
                } else {
                    Cache::put($numKey, $sentenceNum);
                }
            } else if ($type == 'article_content') {
                // 获取所有P标签内容
                $timesKey = base64_encode(request()->url() . '_keywords_content_times');
                $contentTimes = Cache::get($timesKey, 0);

                $sentenceData = [];
                $contentResult = CommonService::getHtmlContent($result);

                foreach ($contentResult as $key => $val) {
                    $sentenceData[$key] = 0;
                }

                if (!empty($sentenceData)) {
                    $sentenceTotal = count($sentenceData);
                    if ($contentTimes > $sentenceTotal) {
                        $eachTimes = bcdiv($contentTimes, $sentenceTotal, 0);
                        $remain = $contentTimes - bcmul($eachTimes, $sentenceTotal, 0);

                        foreach ($sentenceData as $key => &$value) {
                            $value = $value + $eachTimes;
                            if ($remain > 0) {
                                $value++;
                                $remain--;
                            }
                        }
                    } else {
                        $randKeys = multiple_rand(0, $sentenceTotal-1, $contentTimes);
                        foreach ($sentenceData as $key => &$value) {
                            if (in_array($key, $randKeys)) {
                                $value++;
                            }
                        }
                    }

                    foreach ($contentResult as $key => $val) {
                        $content = $val;
                        $shouldTimes = $sentenceData[$key] ?? 0;
                        if (empty($shouldTimes)) {
                            continue;
                        }

                        $sentenceArr = mb_str_split($content);
                        if (empty($sentenceArr)) {
                            continue;
                        }

                        $sentenceCount = count($sentenceArr) - 1;
                        $insertData = multiple_rand(0, $sentenceCount, $shouldTimes);

                        $afterContent = '';
                        foreach ($sentenceArr as $sKey => $sVal) {
                            $afterContent .= $sVal;
                            if (in_array($sKey, $insertData)) {
                                $aTag = ContentService::getRandKeywordATag();
                                $afterContent .= $aTag;
                            }
                        }

                        $result = preg_replace('/'.$content.'/', $afterContent, $result, 1);
                    }
                }

                Cache::forget($timesKey);
            }
        }

        // 如果是文章题目并且不是头部文章题目, 则返回id和内容
        if ($type == 'article_title' && $tag !== '头部文章题目') {
            $result = [
                'id' => $contentId,
                'value' => $result
            ];
        }

        // 如果是刷新不变, 并且是句子, 则将句子缓存下来
        if ($uriType == 'detail' || $uriType == 'list') {
            if ($ifNotChange && $type == 'sentence') {
                $key = base64_encode(urldecode(request()->url() . '_sentences'));

                if (Cache::has($key)) {
                    $value = Cache::get($key);
                    $sentenceArr = json_decode(base64_decode($value, true));
                    $sentenceArr[] = $result;
                } else {
                    $sentenceArr = [$result];
                }

                Cache::put($key, base64_encode(json_encode($sentenceArr, JSON_UNESCAPED_UNICODE)));
            }
        }

        return $result;
    }

    /**
     * 根据全匹配和类型判断字符串匹配类型
     *
     * @param string $type  类型(中文)
     * @param string $tag   匹配的字符串
     * @return array
     */
    public static function getContentInfo(string $type, string $tag)
    {
        $result = [
            'is_category' => 0,
            'is_number' => 0,
        ];
        if (empty($type) || empty($tag)) {
            return $result;
        }

        // 将标签左右的{}去除
        $tag = trim($tag, '{}');
        // 将标签已type为标识进行分割
        $typeArr = explode($type, $tag);
        $number = array_pop($typeArr);

        // 判断是否含有数字
        if (!empty($number)) {
            $result['is_number'] = 1;
        }

        array_push($typeArr, '');
        $newTag = implode($type, $typeArr);

        if ($newTag !== $type) {
            $result['is_category'] = 1;
        }

        return $result;
    }

    /**
     * 获取系统标签内容
     *
     * @param string $type  类型(中文)
     * @param string $tag   匹配的字符串
     * @return array
     */
    public static function randSystemTag(string $type, string $tag)
    {
        $tag = trim($tag, '{}');

        if (in_array($type, ['随机数字', '随机字母', '固定数字', '固定字母'])) {
            $num = (int)str_replace($type, '', $tag) ?: 5;
        }
        if ($type == '时间') {
            $type = $tag;
            $timeTop = 60*60*24*7;
            $time = time() - mt_rand(0, $timeTop);
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
     * @param string $tag   标签
     * @return void
     */
    public static function diyHasNumber(string $tag)
    {
        // 去除标签中的数字
        $newTag = preg_replace("/\\d+/", '', $tag);

        if ($newTag !== $tag) {
            return true;
        }

        return false;
    }

    /**
     * 随机获取自定义标签内容
     *
     * @param string $tag   标签
     * @return void
     */
    public static function randDiyContent(string $tag)
    {
        // 去除标签两边的{}
        $tag = trim($tag, '{}');
        // 去除标签中的数字
        $tag = preg_replace("/\\d+/", '', $tag);

        // $tagModel = Tag::where([
        //     'tag' => $tag
        // ])->first();

        // if (empty($tagModel)) {
        //     return '';
        // }

        // $ids = Diy::where('tag_id', $tagModel->id)->pluck('id');

        $groupId = TemplateService::getGroupId();
        $categoryIds = ContentCategory::where([
            'type' => 'diy',
            'group_id' => $groupId,
        ])->pluck('id')
        ->toArray();
        $usedIdsKey = base64_encode(request()->url() . '_diy_used_ids');
        $usedIds = json_decode(Cache::get($usedIdsKey, ''), true) ?: [];

        $query = Diy::where('tag', $tag)
                ->whereIn('category_id', $categoryIds);
        if (!empty($usedIds)) {
            $query->whereNotIn('id', $usedIds);
        }
        $ids = $query->pluck('id');
        $count = count($ids) - 1;

        if ($count < 0) {
            return '';
        }
        $id = $ids[mt_rand(0, $count)];
        $usedIds[] = $id;
        Cache::put($usedIdsKey, json_encode($usedIds, JSON_UNESCAPED_UNICODE), 3);

        return Diy::find($id)->content;
    }
}
