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

class Copy extends RowAction
{
    public $name;

    /**
     * 测试内容匹配
     */
    public $category;

    public function __construct()
    {
        $this->name = ll('拷贝规则');

        parent::__construct();
    }


    public function handle(Gather $model, Request $request)
    {

        $new       = $model->replicate();
        $oldNames  = explode('_copy_', $new->name);
        $new->name = data_get($oldNames, 0) . "_copy_" . \Illuminate\Support\Str::random(6);
        $new->save();
        return $this->response()->redirect('/admin/gathers')->show("reload", '拷贝成功');
    }


    public function dialog()
    {
        $this->confirm('确定拷贝？');

    }

    public function handleActionPromise()
    {
        $resolve = <<<'SCRIPT'
var actionResolver = function (data) {

            var response = data[0];
            var target   = data[1];
            console.log(response)

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
            if ( response.swal.type == 'fire'){
               Swal.fire({
                              title: "result",
                              icon: "info",
                              html: response.swal.title,
                              showCloseButton: true,
                              showCancelButton: true,
                            })
            }else if(response.swal.type == 'reload'){
              $.admin.swal(response.swal);
           window.location.reload()
            }else{
             $.admin.swal(response.swal);
            }

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
