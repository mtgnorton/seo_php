<?php

namespace App\Admin\Components\Actions\Gathers;

use App\Constants\GatherConstant;
use App\Jobs\GatherJob;
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

class DispatchGather extends RowAction
{
    public $name;

    /**
     * 测试内容匹配
     */
    public $category;

    public function __construct()
    {
        $this->name = ll('立即后台采集');

        parent::__construct();
    }


    public function handle(Gather $model, Request $request)
    {

        if ($model->crontab_type == GatherConstant::CRONTAB_NO) {
            return $this->response()->info('请先设置定时任务采集相关参数');
        }

        GatherJob::dispatch($model);

        return $this->response()->redirect('/admin/gathers')->show("reload", '加入成功');
    }


    public function dialog()
    {
        $this->confirm('确定采集？');

    }


}
