<?php

namespace App\Admin\Components\Actions\Gathers;

use App\Models\Gather;
use Encore\Admin\Actions\RowAction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class ExportGather extends RowAction
{
    public $name;

    /**
     * 测试内容匹配
     */

    public function __construct()
    {
        $this->name = ll('导出规则');

        parent::__construct();
    }


    public function handle(Gather $model, Request $request)
    {



        return $this->response()->location('/admin/gathers/export/' . $this->getKey())->show("reload", '导出成功');;



    }


    public function dialog()
    {
        $this->confirm('确定导出？');

    }
}
