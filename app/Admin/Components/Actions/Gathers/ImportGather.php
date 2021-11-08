<?php

namespace App\Admin\Components\Actions\Gathers;

use App\Models\Gather;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ImportGather extends Action
{
    protected $selector = '.import-gather';

    public function handle(Request $request)
    {
        $file = $request->file('file');

        if (empty($file)) {
            return $this->response()->error('没有上传导入文件')->refresh();

        }

        $extend = $file->getClientOriginalExtension();

        if ($extend != 'gather') {

            return $this->response()->error('导入文件类型错误')->refresh();
        }

        $content = file_get_contents($file);

        try {
            $gather = json_decode(base64_decode($content), true);

            unset($gather['id']);
            $gather['name'] = $gather['name'] . "_导入_" . time();
            Gather::create($gather);

        } catch (\Exception $e) {
            common_log('', null, $gather);
            common_log(full_error_msg($e));
            return $this->response()->error('导入失败,判断采集名称是否重复')->refresh();

        }
        return $this->response()->success('导入成功')->refresh();
    }

    public function form()
    {
        $this->file('file', '请选择文件')->hidePreview()->rules([
            ''
        ]);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-default import-gather">导入规则</a>

HTML;
    }
}
