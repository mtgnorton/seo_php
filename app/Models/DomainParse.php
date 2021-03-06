<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;

class DomainParse extends Model
{
    public function paginate()
    {
        $perPage = Request::get('per_page', 10);

        $page = Request::get('page', 1);


        $domains = $this->getDomains();


        $data = $domains->map(function ($domain, $key) {
            return [
                'id'     => $key + 1,
                'domain' => $domain,
            ];
        })->reverse()->forPage($page, $perPage)->toArray();

        $collection = static::hydrate($data);

        $paginator = new LengthAwarePaginator($collection, $domains->count(), $perPage);

        $paginator->setPath(url()->current());

        return $paginator;
    }


    /**
     * author: mtg
     * time: 2021/6/29   15:54
     * function description:获取已经解析的域名
     * @return \Illuminate\Support\Collection
     */
    static public function getDomains()
    {

        $content = self::getVhostContent();
        preg_match('#server_name (.*?);#i', $content, $matches);

        $domains = explode(" ", data_get($matches, 1));

        return collect($domains)->filter();

    }


    static public function batchAddDomains(array $domains)
    {
        $domains = array_filter($domains);
        $domains = implode(' ', $domains);
        $content = self::getVhostContent();
        $content = preg_replace('#server_name (.*?);#i', 'server_name $1 ' . $domains . ';', $content);
        self::write($content);
    }

    /**
     * author: mtg
     * time: 2021/6/29   16:35
     * function description:添加一个域名
     * @param string $domain
     */
    static public function addDomain(string $domain)
    {
        $content = self::getVhostContent();
        $content = preg_replace('#server_name (.*?);#i', 'server_name $1 ' . $domain . ';', $content);
        self::write($content);

    }

    /**
     * author: mtg
     * time: 2021/6/29   15:58
     * function description:域名替换等同于更新域名
     * @param string $new
     * @param $old
     */
    static public function replaceDomain(string $new, $old)
    {
        $content = self::getVhostContent();
        $content = str_replace(trim($old), trim($new), $content);

        self::write($content);
    }


    static public function deleteDomain($domain)
    {
        $content = self::getVhostContent();
        $content = str_replace(trim($domain), '', $content);
        self::write($content);
    }


    static public function write($content)
    {
        $nginxVhostPath = config('seo.nginx_vhost_path');
        file_put_contents($nginxVhostPath, $content);
        self::reloadNginx();
    }

    static public function getVhostContent()
    {
        $nginxVhostPath = config('seo.nginx_vhost_path');

        return $content = file_get_contents($nginxVhostPath);

    }

    static public function reloadNginx()
    {
        $rs = exec('sudo nginx -s reload 2>&1', $out, $status);

    }

    // 获取单项数据展示在form中
    static public function findOrFail($id)
    {
        return (new self())->newFromBuilder([
            'id'     => $id,
            'domain' => self::getDomains()->get($id - 1)
        ]);
    }

    public static function with($relations)
    {
        return new static;
    }

    // 覆盖`orderBy`来收集排序的字段和方向
    public function orderBy($column, $direction = 'asc')
    {
        return $this;
    }

    // 覆盖`where`来收集筛选的字段和条件
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {

    }

    public function delete()
    {
        self::deleteDomain($this->domain);
        return true;
    }


    static public function batchDelete($ids)
    {
        $domains = self::getDomains();
        foreach ($ids as $id) {
            self::deleteDomain($domains->get($id - 1));
        }
    }
}



