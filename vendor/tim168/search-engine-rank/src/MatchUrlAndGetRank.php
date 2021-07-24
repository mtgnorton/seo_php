<?php

namespace Tim168\SearchEngineRank;

use GuzzleHttp\Exception\RequestException;
use QL\QueryList;
use Symfony\Component\DomCrawler\Crawler;
use Tim168\SearchEngineRank\Config\UserAgentType;
use Tim168\SearchEngineRank\Enum\SearchEngineEnum;
use Tim168\SearchEngineRank\Exceptions\InvalidArgumentException;

class MatchUrlAndGetRank
{

    /**
     * @param $keyWord
     * @param $page
     * @return string
     */
    public static function getPcBaiDuUrl($keyWord, $page)
    {

        $url  = SearchEngineEnum::ENGINE_URL[SearchEngineEnum::PC_BAI_DU];
        $data = [
            'wd' => $keyWord,
            'ie' => 'utf-8',
            'pn' => ($page - 1) * 10
        ];
        $url  = "$url?" . http_build_query($data);
        return $url;

    }

    /**
     * @param $keyWord
     * @param $page
     * @return string
     */
    public static function getMBaiDuUrl($keyWord, $page)
    {

        $url  = SearchEngineEnum::ENGINE_URL[SearchEngineEnum::M_BAI_DU];
        $data = [
            'word' => $keyWord,
            'ie'   => 'utf-8',
            'pn'   => ($page - 1) * 10
        ];
        $url  = "$url?" . http_build_query($data);
        return $url;

    }

    /**
     * @param $keyWord
     * @param $page
     * @return string
     */
    public static function getPc360Url($keyWord, $page)
    {

        $url  = SearchEngineEnum::ENGINE_URL[SearchEngineEnum::PC_360];
        $data = [
            'q'  => $keyWord,
            'ie' => 'utf-8',
            'pn' => $page
        ];
        $url  = "$url?" . http_build_query($data);
        return $url;
    }

    /**
     * @param $keyWord
     * @param $page
     * @return string
     */
    public static function getPcSouGouUrl($keyWord, $page)
    {

        $url  = SearchEngineEnum::ENGINE_URL[SearchEngineEnum::PC_SOU_GOU];
        $data = [
            'query' => $keyWord,
            'ie'    => 'utf-8',
            'pn'    => $page
        ];
        $url  = "$url?" . http_build_query($data);
        return $url;
    }

    /**
     * @param $html
     * @param $url
     * @param $page
     * @param $proxy
     * @return array
     */
    public static function getPcBaiDuRank($html, $url, $page, $proxy)
    {
        $ranks   = [];
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $query = '//*[@id="content_left"]/div';
        $num   = $crawler->filterXPath($query)->count();
        $i     = 1;
        if (!empty($num) && $num > 1) {
            while ($i <= $num) {
                try {
                    $snap_shoot    = '//*[@id="content_left"]//*[@id=' . '"' . $i . '"' . ']//a[@data-click="{\'rsv_snapshot\':\'1\'}"]//@href';
                    $snap_shootUrl = $crawler->filterXPath($snap_shoot)->text();
                    if (!empty($snap_shootUrl)) {
                        $snap_shootHtml = self::getUrl($snap_shootUrl, SearchEngineEnum::PC_BAI_DU, $proxy);
                        if (!empty($snap_shootHtml)) {
                            $query1   = '//*[@id="bd_snap_note"]/a';
                            $crawler1 = new Crawler();
                            $crawler1->addHtmlContent($snap_shootHtml);

                            $match = $crawler1->filterXPath($query1)->text();
                    
                            if (!empty($match)) {
                                $match = self::verifyUrlLastStr($match);

                                dump($match, $url, strstr($match, $url));

                                if (strstr($match, $url)) {
                                    array_unshift($ranks, ($page - 1) * 10 + $i);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
                $i++;
            }
        }
        return $ranks;
    }

    /**
     * @param $html
     * @param $url
     * @param $page
     * @param $proxy
     * @return array
     */
    public static function getMBaiDuRank($html, $url, $page)
    {
        $ranks   = [];
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $query = '//*[@id="results"]/div';
        $num   = $crawler->filterXPath($query)->count();
        $i     = 1;
        if (!empty($num) && $num > 1) {
            while ($i <= $num) {
                try {
                    $snap_shoot    = '//*[@id="results"]//*[@order=' . '"' . $i . '"' . ']//@data-log';
                    $snap_shootUrl = $crawler->filterXPath($snap_shoot)->text();

                    if (!empty($snap_shootUrl)) {
                        if (strstr($snap_shootUrl, $url)) {
                            array_unshift($ranks, ($page - 1) * 10 + $i);
                        }
                    }
                } catch (\Exception $e) {
                }
                $i++;
            }
        }

        return $ranks;
    }

    /**
     * @param $html
     * @param $url
     * @param $page
     * @return array
     */
    public static function getPc360Rank($html, $url, $page)
    {
        $ranks   = [];
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $query = '//*[@class="result"]/li';
        $num   = $crawler->filterXPath($query)->count();
        $i     = 1;
        if (!empty($num) && $num > 1) {
            while ($i <= $num) {
                try {
                    $snap_shoot    = '//*[@class="result"]/li[' . $i . ']';
                    $snap_shootUrl = $crawler->filterXPath($snap_shoot)->text();

                    if (!empty($snap_shootUrl)) {
                        if (strstr($snap_shootUrl, $url)) {
                            array_unshift($ranks, ($page - 1) * 10 + $i);
                        }
                    }

                } catch (\Exception $e) {
                }
                $i++;
            }
        }

        return $ranks;
    }

    public static function getPcSouGouRank($html, $url, $page)
    {
        $ranks   = [];
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $query = '//*[@class="results"]/div';
        $num   = $crawler->filterXPath($query)->count();
        $i     = 1;
        if (!empty($num) && $num > 1) {
            while ($i <= $num) {
                try {
                    $snap_shoot    = '//*[@class="results"]/div[' . $i . ']//*[@class="fb"]//@href';
                    $snap_shootUrl = $crawler->filterXPath($snap_shoot)->text();
                    if (!empty($snap_shootUrl)) {
                        $snap_shootUrl = (urldecode($snap_shootUrl));
                        if (strstr($snap_shootUrl, $url)) {
                            array_unshift($ranks, ($page - 1) * 10 + $i);
                        }
                    }
                } catch (\Exception $e) {
                }
                $i++;
            }

        }

        return $ranks;
    }

    /**
     * @param $url
     * @param $searchEngineType
     * @param string $proxy
     * @return bool|string
     */
    private static function getUrl($url, $searchEngineType, $proxy = '')
    {
        switch ($searchEngineType) {
            case SearchEngineEnum::PC_BAI_DU:
                $m = false;
                break;
            default:
                $m = false;
                break;
        }

        try {
            $ql   = QueryList::get($url, null,
                [
                    'proxy'   => $proxy,
                    'timeout' => 3,
                    'header'  => [
                        "Accept-Encoding" => "gzip",
                        'User-Agent'      => $m ? UserAgentType::M_UserAgent[array_rand(UserAgentType::M_UserAgent, 1)] : UserAgentType::UserAgent[array_rand(UserAgentType::UserAgent, 1)],
                        'Host'            => SearchEngineEnum::HEADER_HOST[$searchEngineType],
                        'Connection'      => 'keep-alive',
                        'Accept'          => 'text/plain, */*',
                        'Accept-Language' => 'en-US,en;q=0.8',
                        'Content-Type'    => "text/html; charset=UTF-8",
                    ]
                ]);
            $html = $ql->getHtml();
            return $html;
        } catch (RequestException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $url
     * @param $searchEngineType
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function verifyUrl($url, $searchEngineType)
    {
        $pregUrl = "/^((ht|f)tps?):\/\/([\w\-]+(\.[\w\-]+)*\/)*[\w\-]+(\.[\w\-]+)*\/?(\?([\w\-\.,@?^=%&:\/~\+#]*)+)?/";
        if (!preg_match($pregUrl, $url)) {
            throw new InvalidArgumentException('不是有效的url');
        }
        switch ($searchEngineType) {
            case SearchEngineEnum::PC_BAI_DU:
                $preg = "/^http(s)?:\\/\\/.+/";
                if (!preg_match($preg, $url)) {
                    throw new InvalidArgumentException('链接缺少http://或https://');
                }
                break;
            case SearchEngineEnum::M_BAI_DU:
                $preg = "/^http(s)?:\\/\\/.+/";
                if (!preg_match($preg, $url)) {
                    throw new InvalidArgumentException('链接缺少http://或https://');
                }
                break;
            case SearchEngineEnum::PC_360:
                $url = self::explodeUrl($url);
                break;
            case SearchEngineEnum::PC_SOU_GOU:
                $url = self::explodeUrl($url);
                break;
        }

        return $url;
    }

    /**
     * @param $url
     * @return bool|string
     */
    private static function verifyUrlLastStr($url)
    {
        $last = substr($url, -1, 1);
        if ($last == '/') {
            $url = substr($url, 0, -1);
        }
        return $url;
    }

    /**
     * @param $url
     * @return mixed
     */
    private static function explodeUrl($url)
    {
        $urls = explode('://', $url);
        if (!empty($urls)) {
            return $urls[1];
        }
        return $url;
    }
}
