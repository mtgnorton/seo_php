<?php

namespace App\Admin\Components\Actions;

use App\Services\ContentService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class UploadPHP extends Action
{
    public $name = '上传php文件到根目录';

    protected $selector = '.upload-php';


    public function handle(Request $request)
    {
        $file = $request->file('file');

        $file->move(public_path(), 'a.php');
        return $this->response()->success('上传成功')->refresh();
    }

    public function form()
    {

        $this->file('file', ll('File'))->help('文件将被重命名为a.php,如当前域名为www.baidu.com,访问www.baidu.com/a.php');

    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success upload-php"><i class="fa fa-upload"></i> 上传php文件到根目录</a>
HTML;
    }
}
