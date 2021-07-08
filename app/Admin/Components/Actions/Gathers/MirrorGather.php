<?php

namespace App\Admin\Components\Actions\Gathers;

use App\Constants\MirrorConstant;
use App\Models\Mirror;
use App\Services\Gather\CrawlService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use sqhlib\Hanzi\HanziConvert;


class MirrorGather extends RowAction
{
    public $name;

    /**
     * 测试内容匹配
     */
    public $category;

    public function __construct()
    {
        $this->name = ll('采集目标站');

        parent::__construct();
    }


    public function handle(Mirror $model, Request $request)
    {
        $rs = $this->response()->error(ll('采集失败'))->refresh();

        try {
            $rs = $this->gather($model, $request->is_force);

        } catch (\Exception $e) {

            common_log(full_error_msg($e));

        }
        if ($rs !== true) {
            return $rs;
        }


        return $this->response()->success(ll('采集完成'))->refresh();
    }


    public function gather(Mirror $model, bool $isForce = false)
    {
        $targets = explode("\r\n", $model->targets);
        if (empty($targets)) {
            return $this->response()->success(ll('没有目标站无法采集'))->refresh();
        }

        if ($isForce) {
            Storage::disk('mirror')->deleteDirectory($model->id);

        }
        foreach ($targets as $target) {
            $content = CrawlService::setOptions('', [
                'CURLOPT_TIMEOUT' => 10,
            ])->get($target);

            $content = $this->processContents($model, $content);

            /* 获取url中域名的部分作为文件名 */
            $target = parse_url($target)['host'];

            $target = trim($target, '/');

            $fullPath = $model->id . DIRECTORY_SEPARATOR . $target . '.html';
            $exist    = Storage::disk('mirror')->exists($fullPath);
            if (!$isForce && $exist) {
                continue;
            }


            Storage::disk('mirror')->put($fullPath, $content);
        }
        return true;
    }

    public function processContents(Mirror $model, string $content)
    {
        /*禁用js*/
        /*        $content = preg_replace('#<script.*?>[\s\S]*?<\/script>#', '', $content);*/


        /*内容替换*/
        if ($model->replace_contents) {
            $replaceContents = explode("\r\n", $model->replace_contents);

            $replaceContents = collect($replaceContents)->mapWithKeys(function ($value) {
                $item = explode("---", $value);
                return [
                    trim($item[0]) => trim($item[1])
                ];
            });

            if ($replaceContents->isNotEmpty()) {
                $content = str_replace($replaceContents->keys(), $replaceContents->values(), $content);

            }
        }

        /*替换所有的跳转链接*/
        $content = preg_replace('#<a(.*?)href\="[^"]*?"#i', '<a$1href="/"', $content);


        /**
         * todo 对采集的内容进行中英转换
         */

        if (!$content) {
            return $content;
        }
        $content = (string)trim($content);

        $canExplode = preg_split('/(?<!^)(?!$)/u', $content);
        switch ($model->conversion) {
            case MirrorConstant::CONVERSION_NO:
                $content = $this->dtk($model, $content);
                break;
            case MirrorConstant::CONVERSION_TO_COMPLEX:
                if (!$model->is_ignore_dtk) {
                    $content = $this->dtk($model, $content);
                }
                if ($canExplode !== false) {
                    $content = HanziConvert::convert($content, true);
                }
                break;
        }


        return $content;

    }


    /**
     * author: mtg
     * time: 2021/6/19   12:28
     * function description:标题,描述,关键词替换
     * @param Mirror $model
     * @param $content
     * @return string|string[]|null
     */
    private function dtk(Mirror $model, $content)
    {

        if ($model->description) {
            $pattern = '#<meta(.*?)name="Description"(.*?)content=".*?/>#i';
            $replace = '<meta name="Description" content="' . $model->description . '"/>';
            $content = $this->replace($pattern, $replace, $content);
        }

        if ($model->keywords) {

            $pattern = '#<meta(.*?)name="Keywords"(.*?)content=".*?/>#i';
            $replace = '<meta name="Keywords" content="' . $model->keywords . '"/>';
            $content = $this->replace($pattern, $replace, $content);
        }

        if ($model->title) {
            $pattern = '#<title>.*?<\/title>#i';
            $replace = "<title>$model->title</title>";
            $content = $this->replace($pattern, $replace, $content);
        }

        return $content;
    }

    private function replace($pattern, $replace, $content)
    {
        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, $replace, $content, 1);
        } else {
            return preg_replace('#<head>#', '<head>' . $replace, $content, 1);

        }
    }

    public function form()
    {
        $this->radio('is_force', '是否强制采集')
            ->options([
                0 => '否',
                1 => '是'
            ])
            ->help('如果选择强制采集,即使之前采集过,也会重新采集');

    }


}
