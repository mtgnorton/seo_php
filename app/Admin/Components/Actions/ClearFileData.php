<?php

namespace App\Admin\Components\Actions;

use App\Services\CategoryService;
use App\Services\ContentService;
use Encore\Admin\Actions\Action;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;

class ClearFileData extends Action
{
    public $name = '清空残留数据';

    protected $selector = '.clear-file-data';

    protected $categoryId;

    protected $type;

    public function __construct($categoryId=0, $type='')
    {
        parent::__construct();

        $this->categoryId = $categoryId;
        $this->type = $type;
    }

    public function handle(Request $request)
    {
        set_time_limit(0);
        $categoryId = $request->input('category_id');

        $result = CategoryService::clearData($categoryId);

        if ($result['code'] != 0) {
            return $this->response()->error($result['message'])->refresh();
        }

        return $this->response()->success($result['message'])->refresh();
    }

    public function form()
    {
        $this->hidden('category_id')->default($this->categoryId);
        $this->radio('status', lp('Is clear file data'))
                ->options([])
                ->default('on')
                ->help(ll('Clear file data help'));
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-danger clear-file-data"><i class="fa fa-trash-o"></i> 清除残留数据</a>
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
