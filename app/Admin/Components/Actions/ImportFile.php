<?php

namespace App\Admin\Components\Actions;

use App\Services\ContentService;
use Encore\Admin\Actions\Action;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;

class ImportFile extends Action
{
    public $name = '上传文件';

    protected $selector = '.import-diy-file';

    protected $categoryId;

    protected $type;

    protected $help;

    public function __construct($categoryId=0, $type='', $help='')
    {
        parent::__construct();

        $this->categoryId = $categoryId;
        $this->type = $type;
        $this->help = $help;
    }

    public function handle(Request $request)
    {
        $files = $request->file('files');
        $categoryId = $request->input('category_id');
        $type = $request->input('type');

        foreach ($files as $file) {
            $result = ContentService::import($file, $type, $categoryId);
        }

        if ($result['code'] != 0) {
            return $this->response()->error($result['message'])->refresh();
        }

        return $this->response()->success($result['message'])->refresh();
    }

    public function form()
    {
        $this->hidden('category_id')->default($this->categoryId);
        $this->hidden('type')->default($this->type);
        $this->multipleFile('files', ll('File'))->help($this->help)
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
        <a class="btn btn-sm btn-success import-diy-file"><i class="fa fa-upload"></i> 上传文件</a>
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
