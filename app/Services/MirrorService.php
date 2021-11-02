<?php

namespace App\Services;


use App\Models\Mirror;
use Illuminate\Support\Str;


class MirrorService
{


    static public function parseDomain($url)
    {

        $components = parse_url($url);

        $domain = data_get($components, 'host');

        $domainComponents = explode('.', $domain);
        $scheme           = $components['scheme'];

        $count = count($domainComponents);

        $mainDomain = data_get($domainComponents, $count - 2) . '.' . data_get($domainComponents, $count - 1);

        return [$scheme, $mainDomain];
    }


    static public function headerEncoding($content)
    {
        /*字符编码 将gbk 转为*/
        if (strpos($content, 'charset=gbk') !== false) {
            header("content-type:text/html;charset=GBK");
        }
        if (strpos($content, 'charset=gb2312') !== false) {
            header("content-type:text/html;charset=gb2312");
        }

    }


    /**
     * author: mtg
     * time: 2021/10/11   11:14
     * function description:将爬取的网站内容链接全部替换为本站链接
     */
    static public function replaceLink($scheme, $mainDomain, $content)
    {


        $content = preg_replace_callback('#<a(.*?)href\="[^"]*?"#i', function ($matches) use ($mainDomain, $scheme) {

            $prefix = $scheme . '://' . Str::random(3);
            return '<a' . $matches[1] . 'href="' . $prefix . '.' . $mainDomain . '"';

        }, $content);

        return $content;
    }

    /**
     * author: mtg
     * time: 2021/6/19   12:28
     * function description:镜像 标题,描述,关键词替换
     * @param Mirror $model
     * @param $content
     * @return string|string[]|null
     */
    static public function dtk(Mirror $model, $content)
    {
        $randFun = function ($field) use ($model) {
            return collect(explode(PHP_EOL, $model->{$field}))->random();
        };

        if ($model->description) {
            $pattern = '#<meta(.*?)name="Description"(.*?)content=".*?/>#i';
            $replace = '<meta name="Description" content="' . $randFun('description') . '"/>';
            $content = self::replace($pattern, $replace, $content);
        }

        if ($model->keywords) {

            $pattern = '#<meta(.*?)name="Keywords"(.*?)content=".*?/>#i';
            $replace = '<meta name="Keywords" content="' . $randFun('keywords') . '"/>';
            $content = self::replace($pattern, $replace, $content);
        }

        if ($model->title) {
            $pattern = '#<title>.*?<\/title>#i';
            $replace = "<title>" . $randFun('title') . "</title>";
            $content = self::replace($pattern, $replace, $content);
        }

        return $content;
    }


    static private function replace($pattern, $replace, $content)
    {
        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, $replace, $content, 1);
        } else {
            return preg_replace('#<head>#', '<head>' . $replace, $content, 1);

        }
    }


}
