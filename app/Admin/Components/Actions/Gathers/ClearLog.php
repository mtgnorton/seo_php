<?php

namespace App\Admin\Components\Actions\Gathers;

use App\Models\Gather;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClearLog extends Action
{
    protected $selector = '.clear-log';

    public function handle(Request $request)
    {
        DB::statement('truncate table gather_crontab_logs');

        return $this->response()->success('清空成功')->refresh();
    }


    public function dialog()
    {
        $this->confirm('确定清空？');

    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-default clear-log">清空日志</a>

HTML;
    }
}
