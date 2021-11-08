<?php

namespace App\Admin\Components\Actions\Gathers;

use App\Constants\GatherConstant;
use App\Models\Gather;
use App\Services\ContentService;
use App\Services\Gather\CrawlService;
use App\Services\GatherService;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Psy\Util\Str;

class TestRegularContent extends RowAction
{
    public $name;

    /**
     * 测试内容匹配
     */
    public $category;

    public function __construct()
    {
        $this->name = ll('测试内容匹配');

        parent::__construct();
    }


    public function handle(Gather $model, Request $request)
    {

        if (empty($model->test_url)) {
            return $this->response()->swal()->error('测试链接为空');
        }

        try {
            $res = CrawlService::setOptionsByModel($model)->get($model->test_url);

            if (is_gbk_html($res)) {
                $res = iconv("gbk", "utf-8//IGNORE", $res);
            }

            $gather = new GatherService($model);

            $matches = $gather->gatherRegularContentsAndProcess($res);


            if ($matches->isEmpty()) {
                return $this->response()->swal()->show("fire", '未获取到匹配,网页的返回内容如下<br>' . $res);
            }


            /**
             * @var $matches Collection
             */

            $html = $matches->flatten()->reduce(function ($carry, $item) use ($model) {


                if ($model->type == GatherConstant::TYPE_IMAGE) {
                    $imageURL = CrawlService::imagePathToURL($item);
                    return "<img src='$imageURL'>";
                }
                return $carry . "<p>$item</p>";
            }, "<div>");

            $html .= "</div>";
        } catch (\Exception $e) {

            gather_log('测试内容匹配失败');
            gather_log(full_error_msg($e));
        }


        try {
            if ($model->type == GatherConstant::TYPE_ARTICLE) {
                $articleTitles = $gather->extractArticleTitles($res)->join("|");

                $html = "<p style='font-weight:bold'>匹配的标题为:$articleTitles</p><p>匹配的内容如下:</p>" . $html;
            }
        } catch (\Exception $e) {
            gather_log('测试内容标题匹配失败');
            gather_log(full_error_msg($e));
        }


        return $this->response()->swal()->show("fire", $html);
    }


    public function dialog()
    {
        $this->confirm('确定测试？');

    }


}
