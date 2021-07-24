<?php

namespace App\Services;

use App\Models\Category;
use App\Models\SpiderRecord;
use App\Models\Template;
use App\Models\TemplateGroup;
use App\Models\TemplateModule;
use App\Models\TemplateModulePage;
use App\Models\Website;
use Illuminate\Support\Facades\Storage;
use App\Services\TemplateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use sqhlib\Hanzi\HanziConvert;
use Symfony\Component\DomCrawler\Crawler;

/**
 * 前端服务类
 *
 * Class IndexService
 * @package App\Services
 */
class IndexService extends BaseService
{
    const UNEABLED_URL = 'template/404.html';

    const TITLE_COOKIE_KEY = 'back_url_title';

    /**
     * 首页方法
     *
     * @param string $path  路径
     * @param string $uriType 类型
     * @return void
     */
    public static function index(string $path, $uriType = 'index')
    {
        $content = self::readFile($path);

        // 删除部分缓存
        self::deleteCache();

        // 循环标签
        $content = self::repeatTag($content);

        // 插入段落模板干扰
        $categoryId = CommonService::getCategoryId();
        $groupId = TemplateService::getGroupId();
        // $templateId = TemplateService::getWebsiteTemplateId();
        
        $default = ConfigService::SITE_PARAMS['template_disturb'];
        $disturbConfig = conf('site.template_disturb', $default, $categoryId, $groupId);
        if ($disturbConfig['is_open'] == 'on') {
            // 判断内容调取方式
            $disturbContent = '';
            if ($disturbConfig['use_type'] == 'open_system') {
                $disturbContent = ContentService::getTemplateDisturb();
            } else if ($disturbConfig['use_type'] == 'open_diy') {
                $disturbContent = $disturbConfig['content'];
            }

            // 判断插入位置
            if ($disturbConfig['position_type'] == 'header') {
                $content = $disturbContent . $content;
            } else if ($disturbConfig['position_type'] == 'footer') {
                $content .= $disturbContent;
            }
        }

        // 记录标题内容
        $globalData = [];
        $content = preg_replace_callback("#<title>(.*?)</title>#", function ($match) use (&$globalData, $uriType) {
            $titleVal = self::replaceAllTag($match[1], $uriType, $globalData, 'title');
            if (!empty($titleVal)) {
                $titleKey = base64_encode(request()->url() . '_title_value');
                $titleArr = mb_str_split($titleVal, 2);
                shuffle($titleArr);
                
                Cache::put($titleKey, json_encode($titleArr, JSON_UNESCAPED_UNICODE));
            }
    
            return "<title>".$titleVal."</title>";
        }, $content);

        // 记录关键词内链数量
        if ($uriType == 'detail') {
            self::getKeywordTimes($content);
            self::getTitleRelevanceTimes($content);
        }

        // 记录连接和标题的对应
        $content = self::putUrlTitle($content, $uriType, $globalData);

        // 每一次单独替换
        $content = self::replaceAllTag($content, $uriType, $globalData);
        // 替换摘要
        $content = preg_replace_callback('/{摘要}/', function ($match) {
            $summaryKey = base64_encode(request()->url() . '_summary');
            $summaryLengthKey = base64_encode(request()->url() . '_summary_length');
            $summary = '';
            if (Cache::has($summaryKey)) {
                $summary = Cache::pull($summaryKey);
                Cache::forget($summaryLengthKey);
            }

            return $summary;
        }, $content);

        // 站点配置
        $content = self::siteConfig($content, $uriType);

        // 记录url和标题
        // self::addUrlTitle($content);

        // 添加广告
        $content = self::addAd($content);

        // 添加推送js
        $content = self::addPushJs($content);

        return $content;
    }

    /**
     * 替换所有标签
     *
     * @param [type] $content
     * @return void
     */
    public static function replaceAllTag($content, $uriType, &$globalData=[], $callType='')
    {
        // $globalData = [];
        $relatedKey = base64_encode(request()->url() . '_related_words');
        
        $result = preg_replace_callback_array([
            // 头部尾部系列
            '/{(头部标签|尾部标签)}/' => function ($match) {
                $tag = $match[1] ?? '';
                
                $content = TemplateService::getBaseHtml($tag);

                return $content;
            },
            // 系统标签系列
            '/{(随机数字|随机字母|时间|固定数字|固定字母|当前网址)+\d*}/' => function ($match) use (&$globalData, $relatedKey, $callType) {
                $key = $match[0];
                $type = $match[1] ?? '';

                if (in_array($type, ['固定数字', '固定字母'])) {
                    // 判断数组里是否存在
                    if (!array_key_exists($key, $globalData)) {
                        $globalData[$key] = IndexPregService::randSystemTag($type, $key);
                    }
                    // 判断该页面相关词是否已存在
                    if ($callType == 'title') {
                        Cache::put($relatedKey, $globalData[$key]);
                    }

                    return $globalData[$key];
                }

                $tagResult = IndexPregService::randSystemTag($type, $key);
                // 判断该页面相关词是否已存在
                if ($callType == 'title') {
                    Cache::put($relatedKey, $tagResult);
                }

                return $tagResult;
            },
            // 内容系列
            '/{[^{]*(文章题目|标题|网站名称|栏目|句子|图片|视频|关键词|文章内容)+\d*}/' => function ($match) use (&$globalData, $uriType, $relatedKey, $callType) {
                $key = $match[0];
                $type = $match[1] ?? '';

                $typeData = IndexPregService::getContentInfo($type, $key);
    
                if ($typeData['is_number'] != 0) {
                    // 判断数组里是否存在
                    if (!array_key_exists($key, $globalData)) {
                        $value = IndexPregService::randContent($type, $key, $uriType, $globalData);
                        if (is_array($value)) {
                            $value = $value['value'];
                        }
                        $globalData[$key] = $value;
                    }
                    // 判断该页面相关词是否已存在
                    if ($callType == 'title') {
                        Cache::put($relatedKey, $globalData[$key]);
                    }

                    return $globalData[$key];
                } else {
                    $value = IndexPregService::randContent($type, $key, $uriType, $globalData);
                    if (is_array($value)) {
                        $value = $value['value'];
                    }
                    // 判断该页面相关词是否已存在
                    if ($callType == 'title') {
                        Cache::put($relatedKey, $value);
                    }

                    return $value;
                }
            },
            // 其他自定义标签
            '/{([^{\r\n\d]*)\d*}/' => function ($match) use (&$globalData, $callType, $relatedKey) {
                $tag = $match[0];
                $noNumTag = $match[1];
                if ($tag == '{摘要}') {
                    return $tag;
                }
                $isNumber = IndexPregService::diyHasNumber($tag);
                if ($noNumTag == '相关词') {
                    if ($callType == 'title') {
                        return $tag;
                    }

                    // 判断缓存中是否已存在该值
                    if (Cache::has($relatedKey)) {
                        $keyword = Cache::get($relatedKey);
                        if ($isNumber) {
                            // 判断数组里是否存在
                            if (!array_key_exists($tag, $globalData)) {
                                $globalData[$tag] = CommonService::getBaiduDropdownWords($keyword);
                            }

                            return $globalData[$tag];
                        } else {
                            return CommonService::getBaiduDropdownWords($keyword);
                        }
                    } else {
                        return '';
                    }
                }

                if ($isNumber) {
                    // 判断数组里是否存在
                    if (!array_key_exists($tag, $globalData)) {
                        $globalData[$tag] = IndexPregService::randDiyContent($tag);
                    }
                    // 判断该页面相关词是否已存在
                    if ($callType == 'title') {
                        Cache::put($relatedKey, $globalData[$tag]);
                    }

                    return $globalData[$tag];
                } else {
                    $tagResult = IndexPregService::randDiyContent($tag);
                    // 判断该页面相关词是否已存在
                    if ($callType == 'title') {
                        Cache::put($relatedKey, $tagResult);
                    }

                    return $tagResult;
                }
            },
        ], $content);

        return $result;
    }

    /**
     * 获取当前使用模板信息
     *
     * @return void
     */
    public static function template()
    {
        // 获取使用中的模板的路径
        $template = Template::with('category')
                            ->first();

        return $template->category->tag . '/' . 
                $template->group->tag . '/' .
                $template->tag;
    }

    /**
     * 随机获取不同类型下的文件
     *
     * @param string $type  类型
     * @return void
     */
    public static function randFile(string $type)
    {
        $path = self::template();
        if ($path[mb_strlen($path) - 1] !== '/') {
            $path .= '/';
        }
        $fullPath = 'template/' . $path . $type;

        $files = Storage::disk('public')->files($fullPath);
        $randKey = count($files)-1 >=0 ? count($files) -1 : 0;
        $randIndex = mt_rand(0, $randKey);

        $file = $files[$randIndex] ?? '';

        if (Storage::disk('public')->exists($file)) {
            return Storage::disk('public')->get($file);
        } else {
            return '';
        }
    }

    /**
     * 读取文件内容
     *
     * @param string $path  路径
     * @return string
     */
    public static function readFile(string $path)
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->get($path);
        } else {
            return '';
        }
    }

    /**
     * 判断当前uri类型
     *
     * @return void
     */
    public static function getUriType()
    {
        // // 获取当前域名信息
        // $host = $_SERVER["HTTP_HOST"];
        $uri = $_SERVER["REQUEST_URI"];

        // // 根据host的值判断是否已绑定域名
        // $website = Website::where([
        //     'url' => $host,
        //     'is_enabled' => 1
        // ])->first();
        $templateId = TemplateService::getWebsiteTemplateId();

        if (empty($templateId)) {
            return '';
        }

        // 判断是否是网站地图
        $result = preg_match('/^sitemap\d*.xml$/', trim($uri,'/'));
        if ($result) {
            return 'sitemap';
        }

        if ($uri === '/' ||
            $uri === '/index.php' ||
            $uri === '/index.html'
        ) {
            $type = 'index';
        } else {
            $ext = mb_substr($uri, -5, 5);

            if ($ext === '.html') {
                $type = 'detail';
            } else {
                $type = 'list';
            }
        }

        return $type;
    }

    /**
     * 获取当前路由下绑定的路径
     *
     * @return string
     */
    public static function getPathByUri()
    {
        // 获取当前域名信息
        $uri = $_SERVER["REQUEST_URI"];

        $templateId = TemplateService::getWebsiteTemplateId();

        $type = self::getUriType();

        // 判断页面类型
        // 1.首页
        if ($type === 'index') {
            $module = TemplateModule::where([
                'template_id' => $templateId,
                'route_tag' => '/'
            ])->first();

            if (empty($module)) {
                return '';
            }

            $files = Storage::disk('public')->files($module->path);
            
            $randKey = count($files)-1 >=0 ? count($files) -1 : 0;
            $randIndex = mt_rand(0, $randKey);
            $fullPath = $files[$randIndex] ?? '';

            return $fullPath;
        }

        $uriArr = explode('/', trim($uri, '/'));
        $cpUrlArr = $uriArr;
        $count = count($uriArr);

        $moduleId = 0;

        // 循环查询uri
        for ($i = 0; $i < $count; $i++) {
            $tempUri = self::fillStringByLine(implode('/', $uriArr)) . $type . '/';

            // 判断该uri在模板中是否有对应
            $module = TemplateModule::where([
                'template_id' => $templateId,
                'route_tag' => $tempUri
            ])->first();

            if (empty($module)) {
                array_pop($uriArr);
                continue;
            }

            $moduleId = $module->id;
            break;
        }
        
        // 查询一级栏目
        if ($moduleId == 0) {
            foreach ($cpUrlArr as $url) {
                $routeTag = self::fillStringByLine($url);
                $oneModule = TemplateModule::where([
                    'template_id' => $templateId,
                    'route_tag' => $routeTag . $type . '/',
                ])->first();    
                if (!empty($oneModule)) {
                    $moduleId = $oneModule->id;
                    break;
                }
            }
        }

        // 如果没有匹配的页面就返回默认
        if ($moduleId == 0) {
            $moduleId = TemplateService::getDefaultModule($templateId, $type);
            if (empty($moduleId)) {
                return '';
            }
        }

        $pages = TemplateModulePage::where('module_id', $moduleId)
                            ->get()->toArray();

        if (empty($pages)) {
            return '';
        }

        $pageCount = count($pages) - 1;
        $page = $pages[mt_rand(0, $pageCount)] ?? [];

        return $page['full_path'] ?? '';
    }

    /**
     * 用 / 填充字符串左右两边
     *
     * @param string $str   字符串
     * @return void
     */
    public static function fillStringByLine(string $str)
    {
        if ($str === '') {
            return '';
        }

        if ($str[0] !== '/') {
            $str = '/' . $str;
        }

        if ($str[mb_strlen($str) - 1] !== '/') {
            $str .= '/';
        }

        return $str;
    }

    /**
     * 网站地图
     *
     * @return string
     */
    public static function siteMap()
    {
        // 模板ID
        $templateId = TemplateService::getWebsiteTemplateId();

        $tags = TemplateModule::where('template_id', $templateId)
                            ->where('column_tag', '<>', '')
                            ->whereHas('pages')
                            ->groupBy('column_tag')
                            ->pluck('column_tag')
                            ->toArray();

        $newHost = request()->getSchemeAndHttpHost();
        if ($newHost[mb_strlen($newHost) -1] !== '/') {
            $newHost .= '/';
        }
        header("Content-Type: text/xml");
        $map = "\t<urlset>\r\n";
        $date = date('Y-m-d');
        
        for ($i=0; $i<1000; $i++) {
            $tmp = self::getRandUrl($newHost, $tags);
            $map .= "\t\t<url>\n";
            $map .= "\t\t\t<loc>{$tmp}</loc>\r\n";
            $map .= "\t\t\t<priority>{$date}</priority>\r\n";
            $map .= "\t\t\t<lastmod>daily</lastmod>\r\n";
            $map .= "\t\t\t<changefreq>0.8</changefreq>\r\n";
            $map .= "\t\t</url>\n";
        }
        $map .= "\t</urlset>";

        return $map;
    }

    /**
     * 获取随机链接
     *
     * @param string $host  域名
     * @param array $tags  可用栏目
     * @return void
     */
    public static function getRandUrl(string $host, array $tags)
    {
        $ext = '/';
        $rate = mt_rand(0, 1);

        if ($rate == 1) {
            $ext = '.html';
        }

        if (empty($tags)) {
            $tag = '';
        } else {
            $randKey = count($tags)-1 >=0 ? count($tags) -1 : 0;
            $tag = $tags[mt_rand(0, $randKey)] ?? '';
        }

        return $host . $tag . '/' . mt_rand(100000, 999999) . $ext;
    }

    /**
     * 增加cookie中增加标题
     *
     * @param string $content
     * @return void
     */
    public static function addCookieTitle(string $content)
    {
        $title = self::TITLE_COOKIE_KEY;
        $cookieTitle = <<<HTML
    <script>
        // $(function () {
        //     $("a").click(function () {
        //         var title = $(this).html();
        //         var url = $(this).attr('href');
        //         if (url.length > 5) {
        //             var ext = url.substr(url.length-5);
        //             if (ext === '.html') {
        //                 document.cookie = "{$title} = " + title;
        //             }
        //         }
        //     });
        //
        window.onload = function () {
        var aObj = document.querySelector('a');
        if (aObj) {
            aObj.addEventListener('click', function (e) {
                var content = this.innerHTML;
                var rule = /<h9>(.+)<\/h9>/;
                var result = rule.exec(content)
                if (result) {
                    var title = result[1];
                } else {
                    var title = '';
                }
                var url = this.href;
                var key = window.btoa(url + "_{$title}");

                if (url.length > 5) {
                    var ext = url.substr(url.length-5);
                    if (ext === '.html') {
                        document.cookie = key + "=" + title;
                    }
                }
            });
        }
        }
    </script>
HTML;

    return $content . $cookieTitle;
    }

    /**
     * 添加地址标题数据
     *
     * @param string $content
     * @return void
     */
    public static function addUrlTitle(string $content)
    {
        $crawler = new Crawler($content);

        $crawler->filter('a')->each(function (Crawler $node, $i) {
            if (count($node->filter('h9')) > 0) {
                $title = $node->filter('h9')->text();

                $url = url($node->attr('href'));

                if (!empty($url) && !empty($title)) {
                    $key = base64_encode($url);
                    $title = base64_encode($title);
                    
                    Cache::put($key, $title);
                }
            }
        });
    }

    /**
     * 站点配置
     *
     * @param string $content
     * @return string
     */
    public static function siteConfig(string $content, $type)
    {
        // // 获取当前域名信息
        // $host = request()->getHost();
        // // 根据host的值判断是否已绑定域名
        // $website = Website::where([
        //     'url' => $host,
        //     'is_enabled' => 1
        // ])->first();
        $categoryId = CommonService::getCategoryId();
        $groupId = TemplateService::getGroupId();
        // $templateId = TemplateService::getWebsiteTemplateId();

        $siteConfig = conf('site', ConfigService::SITE_PARAMS, $categoryId, $groupId);

        // 1. 句子库转换类型是否开启
        $num = 0;
        $tempData = [];
        $sentenceConfig = $siteConfig['sentence_transform'];
        $title = '';
        // 
        if ($sentenceConfig['is_open'] == 'on' &&
            $sentenceConfig['transform_type'] == 'site'
        ) {
            // 简体转繁体
            if ($sentenceConfig['transform_way'] == 'simp2trad') {
                // 将html中的链接和图片地址数据暂存下来
                $content = preg_replace_callback_array([
                    '/(href|src)[=\"\'\s]+([^\"\']*)[\"\']?[^>]*>/' => function ($preg) use (&$num, &$tempData) {
                        $key = "{temp_variable".$num."}";
                        $value = $preg[0];
                        $tempData[$key] = $value;
                        $num++;
        
                        return $key;
                    },
                    '#<title>(.*?)</title>#' => function ($preg) use (&$num, &$tempData, $sentenceConfig, &$title) {
                        $title = $preg[1];
                        if ($sentenceConfig['is_ignore_dtk'] == 'on') {
                            $key = "{temp_variable".$num."}";
                            $value = $preg[0];
                            $tempData[$key] = $value;
                            $num++;
            
                            return $key;
                        } else {
                            return $preg[0];
                        }
                    },
                    "#<meta *name *= *[\"\'] *keywords *[\"\'] *content *= *[\"\'](.*?)[\"\'] */ *>#i" => function ($preg) use (&$num, &$tempData, $sentenceConfig) {
                        if ($sentenceConfig['is_ignore_dtk'] == 'on') {
                            $key = "{temp_variable".$num."}";
                            $value = $preg[0];
                            $tempData[$key] = $value;
                            $num++;
            
                            return $key;
                        } else {
                            return $preg[0];
                        }
                    },
                    "#<meta *name *= *[\"\'] *description *[\"\'] *content *= *[\"\'](.*?)[\"\'] */ *>#i" => function ($preg) use (&$num, &$tempData, $sentenceConfig) {
                        if ($sentenceConfig['is_ignore_dtk'] == 'on') {
                            $key = "{temp_variable".$num."}";
                            $value = $preg[0];
                            $tempData[$key] = $value;
                            $num++;
            
                            return $key;
                        } else {
                            return $preg[0];
                        }
                    },
                ], $content);
                // 是否忽略DTK
                $content = HanziConvert::convert($content, true);
    
                $content = str_replace(array_keys($tempData), array_values($tempData), $content);
            } else if ($sentenceConfig['transform_way'] == 'cn2en') {

            }
        }

        // // 判断是否开启标题关联内容
        // if ($siteConfig['content_relevance'] == 'on' &&
        //     $type == 'detail'
        // ) {
        //     // 获取标题
        //     preg_match("#<title>(.*?)</title>#", $content, $titleMatch);
        //     if (!empty($titleMatch)) {
        //         $title = $titleMatch[1];
        //     }
        //     if (!empty($title)) {
        //         // 获取标题的分词
        //         $titleArr = mb_str_split($title, 2);
                
        //         // 打乱标题
        //         shuffle($titleArr);

        //         if (empty(!$titleArr)) {
        //             $contentResult = CommonService::getHtmlContent($content);
        //             foreach ($contentResult as $key => $val) {
        //                 $afterContent = '';
        //                 $contentArr = mb_str_split($val);
        //                 if (empty($contentArr)) {
        //                     continue;
        //                 }
        //                 foreach ($contentArr as $value) {
        //                     $afterContent .= $value;
        //                     if (!empty($titleArr)) {
        //                         if (mt_rand(1, 10) <= 2) {
        //                             $titleStr = array_pop($titleArr);
        //                             $afterContent .= $titleStr;
        //                         }
        //                     }
        //                 }
                        
        //                 // 替换
        //                 $content = str_replace($val, $afterContent, $content);
        //             }
        //         }
        //     }
        // }

        // 判断是否开启Unicode编码
        if ($siteConfig['unicode_dtk'] == 'on') {
            // 将html中的链接和图片地址数据暂存下来
            $content = preg_replace_callback_array([
                '#<title>(.*?)</title>#' => function ($preg) {
                    return str_replace($preg[1], CommonService::unicodeEncode($preg[1]), $preg[0]);
                },
                "#<meta *name *= *[\"\'] *keywords *[\"\'] *content *= *[\"\'](.*?)[\"\'] */ *>#i" => function ($preg)  {
                    return str_replace($preg[1], CommonService::unicodeEncode($preg[1]), $preg[0]);
                },
                "#<meta *name *= *[\"\'] *description *[\"\'] *content *= *[\"\'](.*?)[\"\'] */ *>#i" => function ($preg)  {
                    return str_replace($preg[1], CommonService::unicodeEncode($preg[1]), $preg[0]);
                },
            ], $content);
        }

        // 判断是否开启描述ascii转换
        if ($siteConfig['ascii_description'] == 'on') {
            $content = preg_replace_callback('#<meta *name *= *[\"\'] *description *[\"\'] *content *= *[\"\'](.*?)[\"\'] */ *>#i', function ($match) {
                $afterDescription = '';
                $beforeDescription = $match[1];
                $descriptionArr = mb_str_split($beforeDescription, 2);
                foreach ($descriptionArr as $value) {
                    $afterDescription .= $value;
                    if (mt_rand(1, 10) <= 2) {
                        $ascStr = CommonService::getSpecAscii();
                        $afterDescription .= $ascStr;
                    }
                }

                return str_replace($beforeDescription, $afterDescription, $match[0]);
            }, $content);
        }

        // 判断是否插入拼音
        if (!empty($siteConfig['rand_pinyin'])) {
            if ($siteConfig['rand_pinyin']['is_open'] == 'on' && $siteConfig['rand_pinyin']['type'] == 'site') {
                // 获取所有P标签内容
                // $content = preg_replace_callback("/<p>(.*?)<\/p>/", function ($preg) {
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
                // }, $content);

                $contentResult = CommonService::getHtmlContent($content);
                foreach ($contentResult as $key => $val) {
                    $contentArr = mb_str_split($val);
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

                    // $content = preg_replace('/'.$val.'/', $afterContent, $content);
                    $content = str_replace($val, $afterContent, $content);
                }
            }
        }

        // 判断网站是否禁止快照
        if ($siteConfig['forbin_snapshot'] == 'on') {
            $forbinData = ContentService::getForbinSnapshotContent();

            $content = $forbinData . $content;
        }

        // 判断是否同义词转换
        $synonymConfig = $siteConfig['synonym_transform'];
        if ($synonymConfig['is_open'] == 'on') {
            $systemData = Storage::disk('store')->get('synonym.txt');
            $diyData = $synonymConfig['content'];
            if ($synonymConfig['type'] == 'system') {
                $storeData = $systemData;
            } else if ($synonymConfig['type'] == 'diy') {
                $storeData = $diyData;
            } else {
                $storeData = [
                    $systemData,
                    $diyData
                ];
            }

            $synonymArr = CommonService::storeStringToArray($storeData);

            $content = strtr($content, $synonymArr);
        }

        return $content;
    }

    /**
     * 添加广告
     *
     * @param string $content
     * @return void
     */
    public static function addAd(string $content)
    {
        // 判断当前用户是蜘蛛还是用户, 如果是用户, 则插入广告, 蜘蛛则不插入
        if (empty(SpiderService::getSpider())) {
            // 获取当前网站分类ID
            $categoryId = CommonService::getCategoryId();
            $groupId = TemplateService::getGroupId();
            $adConfig = conf('ad', ConfigService::AD_PARAMS, $categoryId, $groupId);
            if (!empty($adConfig)) {
                if ($adConfig['is_open'] == 'on') {
                    // 判断是调用系统广告还是用户自定义广告
                    $adContent = '';
                    if ($adConfig['type'] == 'system') {
                        $adContent = '';
                    } else if ($adConfig['type'] == 'diy') {
                        $path = '/storage/ad/'.$groupId . '.html';
                        if (Storage::disk('public')->exists('ad/'.$groupId . '.html')) {
                            $adContent = <<<HTML
<iframe src="{$path}" style="width:100%; height:100%;position: fixed;top: 0;left:0;z-index: 99999;"/>;
HTML;
                        }

                    }
        
                    if (!empty($adContent)) {
                        // 获取链接内容
                        $urls = $adConfig['urls'];
        
                        if (!empty($urls)) {
                            $urlArr = CommonService::linefeedStringToArray($urls);
                            if (!empty($urlArr)) {
                                $randKey = count($urlArr)-1 >=0 ? count($urlArr) -1 : 0;
                                $url = $urlArr[mt_rand(0, $randKey)] ?? '';
        
                                // 替换{跳转链接}
                                $adContent = preg_replace('/{跳转链接}/', $url, $adContent);
                            }
                        }
        
                        // 判断是否放在头部
                        // if ($adConfig['position'] == 'header') {
                            $content = $adContent . $content;
                        // } else {
                        //     $content = $content . $adContent;
                        // }
                    }
                }
            }
        }



        return $content;
    }

    /**
     * 添加推送的js代码
     *
     * @param string $content
     * @return void
     */
    public static function addPushJs($content)
    {
        $pushConfig = conf('push');
        if ($pushConfig['auto_push']['is_open'] == 'on') {
            // 获取push_js代码
            $pushJs = '<script>'.conf('push.push_js', '').'</script>';
    
            return $content . $pushJs;
        } else {
            return $content;
        }
    }

    /**
     * 获取缓存数据
     *
     * @return string
     */
    public static function getCache()
    {
        // 获取页面类型
        $type = self::getUriType();

        // 如果是sitemap, 则不获取缓存
        if ($type == 'sitemap') {
            return '';
        }

        // 判断系统是否开启缓存
        $categoryId = CommonService::getCategoryId();
        $groupId = TemplateService::getGroupId();
        $cacheStatus = conf('cache.is_open', ConfigService::CACHE_PARAMS['is_open'], $categoryId, $groupId);
        if ($cacheStatus != 'on') {
            return '';
        }

        // 获取模板ID
        $templateId = TemplateService::getWebsiteTemplateId();

        if (empty($templateId)) {
            return '';
        }

        $template = Template::find($templateId);
        if (empty($template) || empty($template->category_id)) {
            return '';
        }
        $groupId = $template->group_id;
        
        $url = request()->url();
        $file = base64_encode($url) . '.txt';
        $path = 'cache/templates/'.$groupId.'/'.$type.'/'.$file;

        if (!Storage::disk('local')->exists($path)) {
            return '';
        }
        $result = Storage::disk('local')->get($path);

        if (empty($result)) {
            return '';
        }

        return $result;
    }

    /**
     * 写入缓存
     *
     * @return void
     */
    public static function setCache($content)
    {
        // 获取页面类型
        $type = self::getUriType();

        // 如果是sitemap, 则不缓存
        if ($type == 'sitemap') {
            return '';
        }

        // 获取配置信息
        $categoryId = CommonService::getCategoryId();
        $groupId = TemplateService::getGroupId();
        $config = conf('cache', ConfigService::CACHE_PARAMS, $categoryId);
        if ($config['is_open'] != 'on') {
            return '';
        }

        // 判断是不是仅蜘蛛爬行产生缓存
        if ($config['spider_open'] == 'on') {
            // 判断当前是用户还是蜘蛛
            $spider = SpiderService::getSpider();

            if (empty($spider)) {
                return '';
            }
        }

        // 获取模板ID
        $templateId = TemplateService::getWebsiteTemplateId();

        if (empty($templateId)) {
            return '';
        }

        $template = Template::find($templateId);
        if (empty($template) || empty($template->category_id)) {
            return '';
        }
        $groupId = $template->group_id;
        
        $url = request()->url();
        $file = base64_encode($url) . '.txt';
        $path = 'cache/templates/'.$groupId.'/'.$type.'/'.$file;

        // 判断是否开启缓存时间
        if ($config['cache_time']['is_open'] == 'on') {
            $cacheTime = $config['cache_time'][$type];
            $time = time() + $cacheTime * 60 * 60;

            $key = 'cacheFileData';

            $cacheData = Cache::pull($key);

            if (empty($cacheData)) {
                Cache::put($key, [$path => $time]);
            } else {
                $cacheData[$path] = $time;
    
                Cache::put($key, $cacheData);
            }
        }

        Storage::disk('local')->put($path, $content);
    }

    /**
     * 获取关键词数量
     *
     * @return void
     */
    public static function getKeywordTimes($content)
    {
        // 获取文章内容的总数
        $contentTotal = 0;
        $sentenceData = [];
        $content = preg_replace_callback('/{[^{]*(句子|文章内容)+\d*}/', function ($bfmatch) use (&$contentTotal, &$sentenceData) {
            if ($bfmatch[1] == '句子') {
                $sentenceData[] = 0;
            } else if ($bfmatch[1] == '文章内容') {
                $contentTotal++;
            }
            
            return $bfmatch[0];
        }, $content);
        $sentenceTotal = count($sentenceData);
        // 将句子总数写入缓存
        $sentenceKey = base64_encode(request()->url() . '_sentence_count');
        Cache::put($sentenceKey, $sentenceTotal);

        // 将句子次数和文章内容次数写入缓存(关键词内链)
        $sentenceTimesKey = base64_encode(request()->url() . '_keywords_sentence_times_data');
        $contentKey = base64_encode(request()->url() . '_keywords_content_times');
        $webCategoryId = CommonService::getCategoryId();
        $groupId = TemplateService::getGroupId();
        // $templateId = TemplateService::getWebsiteTemplateId();
        $keywordTimes = conf('site.keyword_chain.times', 1, $webCategoryId, $groupId);
        $contentTimes = 0;
        $sentenceTimes = 0;
        if ($sentenceTotal > 0) {
            if ($contentTotal > 0) {
                $contentTimes = bcdiv($keywordTimes, 2, 0);
                $sentenceTimes = $keywordTimes - $contentTimes;
            } else {
                $sentenceTimes = $keywordTimes;
            }
        } else {
            if ($contentTotal > 0) {
                $contentTimes = $keywordTimes;
            }
        }
        // 分配每个句子需要加入的内链数量
        if ($sentenceTimes > $sentenceTotal) {
            $eachTimes = bcdiv($sentenceTimes, $sentenceTotal, 0);
            $remain = $sentenceTimes - bcmul($eachTimes, $sentenceTotal, 0);

            foreach ($sentenceData as $key => &$value) {
                $value = $value + $eachTimes;
                if ($remain > 0) {
                    $value++;
                    $remain--;
                }
            }
        } else {
            $randKeys = multiple_rand(0, $sentenceTotal-1, $sentenceTimes);
            foreach ($sentenceData as $key => &$value) {
                if (in_array($key, $randKeys)) {
                     $value++;
                }
            }
        }
        // dd($sentenceData);
        Cache::put($contentKey, $contentTimes);
        Cache::put($sentenceTimesKey, json_encode($sentenceData, JSON_UNESCAPED_UNICODE));
        // dump('sentence: '.$sentenceTimes);
        // dump('content: '.$contentTimes);
    }

    /**
     * 获取关键词数量
     *
     * @return void
     */
    public static function getTitleRelevanceTimes($content)
    {
        // 获取文章内容的总数
        $contentTotal = 0;
        $sentenceData = [];
        $content = preg_replace_callback('/{[^{]*(句子|文章内容)+\d*}/', function ($bfmatch) use (&$contentTotal, &$sentenceData) {
            if ($bfmatch[1] == '句子') {
                $sentenceData[] = 0;
            } else if ($bfmatch[1] == '文章内容') {
                $contentTotal++;
            }
            
            return $bfmatch[0];
        }, $content);
        $sentenceTotal = count($sentenceData);

        // 将句子次数和文章内容次数写入缓存(关键词内链)
        $sentenceTimesKey = base64_encode(request()->url() . '_title_sentence_times');
        $contentKey = base64_encode(request()->url() . '_title_content_times');
        $titleKey = base64_encode(request()->url() . '_title_value');
        $titleVal = json_decode(Cache::get($titleKey, ''), true) ?: [];
        $titleTimes = count($titleVal);
        $contentTimes = 0;
        $sentenceTimes = 0;
        if ($sentenceTotal > 0) {
            if ($contentTotal > 0) {
                $contentTimes = bcdiv($titleTimes, 2, 0);
                $sentenceTimes = $titleTimes - $contentTimes;
            } else {
                $sentenceTimes = $titleTimes;
            }
        } else {
            if ($contentTotal > 0) {
                $contentTimes = $titleTimes;
            }
        }
        
        // 分配每个句子需要加入的内链数量
        if ($sentenceTimes > $sentenceTotal) {
            $eachTimes = bcdiv($sentenceTimes, $sentenceTotal, 0);
            $remain = $sentenceTimes - bcmul($eachTimes, $sentenceTotal, 0);

            foreach ($sentenceData as $key => &$value) {
                $value = $value + $eachTimes;
                if ($remain > 0) {
                    $value++;
                    $remain--;
                }
            }
        } else {
            $randKeys = multiple_rand(0, $sentenceTotal-1, $sentenceTimes);
            foreach ($sentenceData as $key => &$value) {
                if (in_array($key, $randKeys)) {
                     $value++;
                }
            }
        }
        Cache::put($contentKey, $contentTimes);
        Cache::put($sentenceTimesKey, json_encode($sentenceData, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 将地址和标题的对应关系写入缓存
     *
     * @param [type] $content
     * @return void
     */
    public static function putUrlTitle($content, $uriType, &$globalData=[])
    {
        $content = preg_replace_callback("#<a.*?href *= *[\'|\"](.*?)[\'|\"].*?>([\s\S]*?)< */a>#i", function ($preg) use ($uriType, &$globalData) {
            $whole = $preg[0];
            $href = $preg[1];
            $aHtml = $preg[2];
            
            $href = self::replaceAllTag($href, $uriType, $globalData);

            // 更新连接
            $whole = str_replace($preg[1], $href, $whole);

            $articleId = 0;
            $aHtml = preg_replace_callback_array([
                // 系统标签系列
                '/{(随机数字|随机字母|时间|固定数字|固定字母|当前网址)+\d*}/' => function ($match) use (&$globalData) {
                    $key = $match[0];
                    $type = $match[1] ?? '';
    
                    if (in_array($type, ['固定数字', '固定字母'])) {
                        // 判断数组里是否存在
                        if (!array_key_exists($key, $globalData)) {
                            $globalData[$key] = IndexPregService::randSystemTag($type, $key);
                        }
    
                        return $globalData[$key];
                    }
    
                    return IndexPregService::randSystemTag($type, $key);
                },
                // 内容系列
                '/{[^{]*(文章题目|标题|网站名称|栏目|句子|图片|视频|关键词|文章内容)+\d*}/' => function ($match) use (&$globalData, $uriType, &$articleId) {
                    $key = $match[0];
                    $type = $match[1] ?? '';
    
                    $typeData = IndexPregService::getContentInfo($type, $key);
    
                    if ($typeData['is_number'] != 0) {
                        // 判断数组里是否存在
                        if (!array_key_exists($key, $globalData)) {
                            $result = IndexPregService::randContent($type, $key, $uriType, $globalData);
                            if (is_array($result)) {
                                $articleId = $result['id'];
                                $result = $result['value'];
                            }
                            $globalData[$key] = $result;
                        }
    
                        return $globalData[$key];
                    } else {
                        $result = IndexPregService::randContent($type, $key, $uriType, $globalData);
                        if (is_array($result)) {
                            $articleId = $result['id'];
                            $result = $result['value'];
                        }
                        return $result;
                    }
                },
                // 其他自定义标签
                '/{([^{\r\n\d]*)\d*}/' => function ($match) use (&$globalData) {
                    $tag = $match[0];
                    $noNumTag = $match[1];
                    if ($tag == '{摘要}') {
                        return $tag;
                    }
                    $isNumber = IndexPregService::diyHasNumber($tag);
                    if ($noNumTag == '相关词') {
                        $relatedKey = base64_encode(request()->url() . '_related_words');
                        // 判断缓存中是否已存在该值
                        if (Cache::has($relatedKey)) {
                            $keyword = Cache::get($relatedKey);
                            if ($isNumber) {
                                // 判断数组里是否存在
                                if (!array_key_exists($tag, $globalData)) {
                                    $globalData[$tag] = CommonService::getBaiduDropdownWords($keyword);
                                }
    
                                return $globalData[$tag];
                            } else {
                                return CommonService::getBaiduDropdownWords($keyword);
                            }
                        } else {
                            return '';
                        }
                    }
    
                    if ($isNumber) {
                        // 判断数组里是否存在
                        if (!array_key_exists($tag, $globalData)) {
                            $globalData[$tag] = IndexPregService::randDiyContent($tag);
                        }
    
                        return $globalData[$tag];
                    } else {
                        return IndexPregService::randDiyContent($tag);
                    }
                },
            ], $aHtml);

            // 更新标题内容
            $whole = str_replace($preg[2], $aHtml, $whole);

            $tempTitle = strip_tags($aHtml);
            $titleArr = CommonService::linefeedStringToArray($tempTitle);
            $title = '';
            if (!empty($titleArr)) {
                foreach ($titleArr as $titleKey => &$titleVal) {
                    $titleVal = trim($titleVal);
                    if (empty($titleVal)) {
                        unset($titleArr[$titleKey]);
                    }
                }

                $title = implode('', $titleArr);
            }

            if (!empty($title)) {
                $url = url($href);
                $key = base64_encode($url);
                $title = base64_encode($title);

                Cache::put($key, $title);
            }

            if (!empty($articleId)) {
                $url = url($href) . '_article_id';
                $articleKey = base64_encode($url);

                Cache::put($articleKey, $articleId);
            }

            return $whole;
        }, $content);

        return $content;
    }

    /**
     * 循环循环标签中间的内容
     *
     * @param string $content
     * @return string
     */
    public static function repeatTag(string $content)
    {
        // $content = preg_replace_callback('#{循环开始 *?times *?= *?[\'\"](.*?)[\'\"] *?data *?= *?[\'\"](.*?)[\'\"] *?}([\s\S]*?){循环结束}#', function ($match) {
        //     $times = trim($match[1]) ?: 1;
        //     $dataStr = trim($match[2]);
        //     $repeatContent = $match[3];

        //     // 获取参数
        //     $resultData = [];
        //     $tempData = explode(';', $dataStr);
        //     foreach ($tempData as $temp) {
        //         $childData = explode(':', $temp);
        //         if (empty($childData)) {
        //             continue;
        //         }

        //         $resultData['{'.$childData[0].'}'] = array_reverse(explode(',', $childData[1] ?? ''));
        //     }

        //     $result = '';
        //     for ($i=1; $i <= $times; $i++) {
        //         $keys = array_keys($resultData);
        //         $keyStr = implode('|', $keys);
                
        //         $pattern = '#(num\+\+|'.$keyStr.')#';
        //         $result .= preg_replace_callback($pattern, function ($match) use ($i, &$resultData) {
        //             $pregKey = $match[0] ?? '';
        //             if (empty($pregKey)) {
        //                 return $pregKey;
        //             }
        //             // 如果是num++, 则替换当前$i
        //             if ($pregKey == 'num++') {
        //                 return $i;
        //             }
        //             if (array_key_exists($pregKey, $resultData)) {
        //                 return array_pop($resultData[$pregKey]);
        //             }

        //             return '';
        //         }, $repeatContent);
        //     }

        //     return $result;
        // }, $content);

        // return $content;
        $content = preg_replace_callback('#{ *?循环开始(.*?)}([\s\S]*?){循环结束}#', function($match) {
            $repeatContent = $match[2];
            $params = $match[1] ?? '';
            preg_match('# *?times *?= *?[\'\"](.*?)[\'\"] *?#', $params, $timesMatch);
            preg_match('# *?data *?= *?[\'\"](.*?)[\'\"] *?#', $params, $dataMatch);

            $times = trim($timesMatch[1] ?? 1);
            $dataStr = trim($dataMatch[1] ?? '');

            // 获取参数
            $resultData = [];
            $tempData = explode(';', $dataStr);
            foreach ($tempData as $temp) {
                $childData = explode(':', $temp);
                if (empty($childData)) {
                    continue;
                }

                $childArr = explode(',', $childData[1] ?? '');
                $resultData['{'.trim($childData[0]).'}'] = array_filter($childArr);
            }

            $result = '';
            for ($i=1; $i <= $times; $i++) {
                $keys = array_keys($resultData);
                $keyStr = implode('|', $keys);
                $nowData = [];
                foreach ($resultData as $key => &$val) {
                    $tempVal = trim(array_shift($val));
                    $nowData[$key] = $tempVal;
                    $val[] = $tempVal;
                }
                
                $pattern = '#(num\+\+|'.$keyStr.')#';
                $result .= preg_replace_callback($pattern, function ($match) use ($i, &$nowData) {
                    $pregKey = $match[0] ?? '';
                    if (empty($pregKey)) {
                        return $pregKey;
                    }
                    // 如果是num++, 则替换当前$i
                    if ($pregKey == 'num++') {
                        return $i;
                    }
                    if (array_key_exists($pregKey, $nowData)) {
                        return $nowData[$pregKey];
                    }

                    return '';
                }, $repeatContent);
            }

            return $result;
        }, $content);

        return $content;
    }

    /**
     * 清空需要清空的缓存
     *
     * @return void
     */
    public static function deleteCache()
    {
        $cacheKey = [];
        // 1. 清空已使用内容ID
        $types = IndexPregService::CONTENT_TAG;
        foreach ($types as $type => $value) {
            $cacheKey[] = base64_encode(request()->url() . '_' . $type . '_used_ids');
        }
        // 2. 清空已使用自定义ID
        $cacheKey[] = base64_encode(request()->url() . '_diy_used_ids');

        foreach ($cacheKey as $key) {
            if (Cache::has($key)) {
                Cache::forget($key);
            }
        }
    }
}
