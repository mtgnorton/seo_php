<?php

namespace App\Admin\Components\Actions;

use App\Services\CommonService;
use App\Services\ContentService;
use Encore\Admin\Actions\Action;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;

class PushFile extends Action
{
    public $name = '上传文件';

    protected $selector = '.upload-push-file';

    public function handle(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $files = $request->file('files');

        $result = CommonService::pushFilesUpload($files);

        if ($result['code'] != 0) {
            return $this->response()->error('上传成功')->refresh();
        }

        return $this->response()->success($result['message'])->refresh();
    }

    public function form()
    {
        $this->multipleFile('files', ll('File'))
                ->options([
                    'showPreview' => false,
                    'maxFileCount' => 0
                ])->rules('required', [
                    'required' => ll('File does not exist')
                ]);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success upload-push-file"><i class="fa fa-upload"></i> 上传文件</a>
HTML;
    }

    /**
     * @return string
     */
    public function handleActionPromise()
    {
        $resolve = <<<'SCRIPT'
var actionResolver = function (data) {

            var response = data[0];
            var target   = data[1];

            if (typeof response !== 'object') {
                return $.admin.swal({type: 'error', title: 'Oops!'});
            }

            var then = function (then) {
                if (then.action == 'refresh') {
                    $.admin.reload();
                }

                if (then.action == 'download') {
                    window.open(then.value, '_blank');
                }

                if (then.action == 'redirect') {
                    $.admin.redirect(then.value);
                }

                if (then.action == 'location') {
                    window.location = then.value;
                }

                if (then.action == 'open') {
                    window.open(then.value, '_blank');
                }
            };

            if (typeof response.html === 'string') {
                target.html(response.html);
            }

            if (typeof response.swal === 'object') {
                $.admin.swal(response.swal);
            }

            if (typeof response.toastr === 'object' && response.toastr.type) {
                $.admin.toastr[response.toastr.type](response.toastr.content, '', response.toastr.options);
            }

            if (response.then) {
              then(response.then);
            }
        };

        var actionCatcher = function (request) {
            if (request && typeof request.responseJSON === 'object') {
                $.admin.toastr.error(request.responseJSON.message, '', {positionClass:"toast-bottom-center", timeOut: 10000}).css("width","500px")
            }
        };
SCRIPT;

        Admin::script($resolve);

        return <<<'SCRIPT'
process.then(actionResolver).catch(actionCatcher);
SCRIPT;
    }
}
