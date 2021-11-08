<?php

namespace App\Admin\Components\Actions\Gathers;

use App\Models\Gather;
use App\Services\ContentService;
use App\Services\Gather\CrawlService;
use Encore\Admin\Actions\RowAction;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TestRegularUrl extends RowAction
{
    public $name;

    /**
     * 测试网址匹配
     */
    public $category;

    public function __construct()
    {
        $this->name = ll('测试网址匹配');

        parent::__construct();
    }


    public function handle(Gather $model, Request $request)
    {


        $patterns = CrawlService::parseAsteriskPatterns($model->regular_url);


        if ($patterns->isEmpty()) {
            return $this->response()->info('匹配网址为空');

        }


        $res = CrawlService::setOptionsByModel($model)->get(CrawlService::parseURLs($model->begin_url)[0]);


        /**
         * @var $this CrawlService
         */
        $urls = CrawlService::getHtmlURLs($res);


        $matches = $urls
            ->filter(function ($item) use ($patterns) {

                foreach ($patterns as $pattern => $value) {

                    if (preg_match($pattern, $item)) {
                        return true;
                    }
                }
                return false;
            });


        if ($matches->isEmpty()) {
            return $this->response()->swal()->show("fire", '未获取到匹配,网页的返回内容如下<br>' . $res);
        }


        /**
         * @var $matches Collection
         */
        $html = $matches->reduce(function ($carry, $value) {
            return $carry . "<p>$value</p>";
        }, "<div>");
        $html .= "</div>";


        return $this->response()->swal()->show("fire", $html);

    }


    public function dialog()
    {
        $this->confirm('确定测试？');

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

            if (typeof response.swal === 'object' && response.swal.type != 'fire') {
                $.admin.swal(response.swal);

            }
        if (typeof response.swal === 'object' && response.swal.type == 'fire') {
                          Swal.fire({
                              title: "result",
                              icon: "info",
                              html: response.swal.title,
                              showCloseButton: true,
                              showCancelButton: true,
                            })


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
