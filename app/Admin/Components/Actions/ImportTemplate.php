<?php

namespace App\Admin\Components\Actions;

use App\Jobs\ProcessImportTemplate;
use App\Services\CategoryService;
use App\Services\TemplateService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImportTemplate extends Action
{
    public $name = '导入模板';

    protected $selector = '.import-template';

    protected $categoryId;

    protected $groupId;

    protected $typeId;

    public function __construct($categoryId=0, $groupId=0, $typeId=0)
    {
        parent::__construct();

        $this->categoryId = $categoryId;
        $this->groupId = $groupId;
        $this->typeId = $typeId;
    }

    public function handle(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        
        $data = $request->except([
            'import_data','_token', '_action'
        ]);

        $file = $request->import_data;
        $name = $file->getClientOriginalName();
        $path = '/importTemplate/'.$name;
        $fileData = [
            'path' => $path,
            'name' => $name,
            'mimeType' => $file->getClientMimeType(),
        ];
        $file->storeAs('/importTemplate', $name, 'public');

        ProcessImportTemplate::dispatch($fileData, $data);

        // $result = TemplateService::importTemplate($data);

        // if ($result['code'] == 0) {
        //     return $this->response()->success('导入成功')->refresh();
        // }

        // return $this->response()->error('导入失败');
        return $this->response()->refresh()->success('导入成功, 请耐心等待五分钟左右即可导入完毕, 具体导入时间与zip包大小有关')->timeout(30000);
    }

    public function form()
    {
        $this->text('name', ll('Template name'))
            ->rules('required', [
                'required' => lp('Template name', 'Cannot be empty')
            ]);
        $this->text('tag', ll('Template tag'))
            ->rules('required', [
                'required' => lp('Template tag', 'Cannot be empty')
            ])->help(lp('English or pinyin', ',', 'Template tag help'));
        $this->select('type_id', ll('Template type'))
            ->options(TemplateService::typeOptions())
            ->default($this->typeId)
            ->rules('required', [
                'required' => lp('Template type', 'Cannot be empty')
            ]);
        $this->select('group_id', ll('Template group'))
            ->options(TemplateService::groupOptions(['category_id' => $this->categoryId]))
            ->value($this->groupId)
            ->rules('required', [
                'required' => lp('Category', 'Cannot be empty')
            ]);
        $this->file('import_data', ll('Import zip data'))
            ->options([
                'showPreview' => true,
                'maxFileCount' => 0,
                'maxFileSize' => 1024*150,
                'msgSizeTooLarge' => ll('File too large'),
            ])->rules('mimes:zip|required',[
                'mimes' => ll('Type must zip'),
                'required' => ll('File does not exist'),
            ])
            ->help(ll('Import template help'));
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-primary import-template"><i class="fa fa-arrow-down"></i> 导入模板</a>
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
