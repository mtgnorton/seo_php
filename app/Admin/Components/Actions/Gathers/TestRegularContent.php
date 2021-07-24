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


        $res = CrawlService::setOptionsByModel($model)->get($model->test_url);

        $gather = new GatherService($model);

        $matches = $gather->gatherRegularContentsAndProcess($res);


        /**
         * @var $matches Collection
         */

        $html = $matches->flatten()->reduce(function ($carry, $item) {
            return $carry . "<p>$item</p>";
        }, "<div>");

        $html .= "</div>";

        return $this->response()->swal()->show("fire", $html);
    }


    public function dialog()
    {
        $this->confirm('确定测试？');

    }



}
