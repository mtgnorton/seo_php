<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\Gathers\Copy;
use App\Admin\Components\Actions\Gathers\MirrorGather;
use App\Admin\Components\Actions\Gathers\TestRegularContent;
use App\Admin\Components\Actions\Gathers\TestRegularUrl;
use App\Admin\Components\Actions\UploadPHP;
use App\Admin\Forms\Ad;
use App\Constants\GatherConstant;
use App\Constants\MirrorConstant;
use App\Models\Category;
use App\Models\Mirror;
use App\Services\MirrorService;
use App\Services\Textractor;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Callout;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\Storage;

class MirrorController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Mirror';

    protected $icon = "http://seo.local/asset/imgs/icon/7.png";

    public function __construct()
    {
        $this->title = ll('Mirror');
    }

    public function index(Content $content)
    {

        $grid = $this->grid();

        $titleHeader = <<<EOT
        <div class="box-header with-border">
            <div class="conmon-icon-title">
            <img class="title-icon" src="$this->icon" class="default" alt="">
            <label class="title-word">$this->title</label>
        </div>
EOT;

        if (is_object($grid)) {
            $grid = $grid->render();
        }


        $grid = str_replace('<div class="box-header with-border">', $titleHeader, $grid);


        $css = <<<EOT
.content-header{
display:none;
}
EOT;


        Admin::style($css);
        return $content
            ->title($this->title())
            ->row(new Callout("
                镜像部分网站可能因为js原因,无法正常显示,请预览后确定是否可用<br/>
            ", "注意事项", 'info'))
            ->body($grid);
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        $js = <<<EOT
$('.modal-footer .btn-primary').click( function (){
    $(this).text('采集中')

     setTimeout(function() {
        $(this).attr('disabled',true);
    }, 1);

});
EOT;

        \Admin::script($js);

        $grid = new Grid(new Mirror());
        $grid->actions(function ($actions) {
            $actions->add(new MirrorGather());
        });
        $grid->column('id', ll('Id'));
        $grid->column('category_id', ll('所属分类'))->display(function ($value) {
            $c = Category::find($value);
            if ($c) {
                return $c->name;
            } else {
                return '暂无分类,请重新设置';
            }
        });
        // $grid->tools(function (Grid\Tools $tools) {
        //     $tools->append(new UploadPHP());
        // });
        $grid->column('is_disabled', ll('是否禁用'))->switch();
        $grid->column('targets', ll('Targets'))->expand(function ($model) {

            $targets = explode("\r\n", $model->targets);

            $rs = [];

            foreach ($targets as $target) {
                $encodeTarget = urlencode($target);
                $rs[]         = [
                    $target, "<a href='/admin/mirrors/preview/id/{$model->id}/{$encodeTarget}' target='_blank'>预览</a>"
                ];
            }
            return new Table(['目标站', '预览'], $rs);
        });
//        $grid->column('title', ll('Title'));
//        $grid->column('keywords', ll('Keywords'));
//        $grid->column('description', ll('Description'));
        $grid->column('conversion', ll('Conversion'))->using(MirrorConstant::conversionText());
        $grid->column('is_ignore_dtk', ll('Is open dtk'))->display(function ($value) {
            return $value ? '是' : '否';
        });
//        $grid->column('user_agent', ll('User agent'));
        $grid->column('replace_contents', ll('Replace contents'));
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));

//        $grid = $grid->render();
//        $grid = str_replace('<div class="box grid-box">', '<div class="box grid-box content_form_default">', $grid);

        return $grid;
        // return $grid;
    }

    /*采集镜像预览*/
    public function preview()
    {

        $target = request()->target;

        $id = request()->id;


        /* 获取url中域名的部分作为文件名 */
//        $target = parse_url($target)['host'];
//
//        $target = trim($target, '/');

        $target   = md5($target);

        $fullPath = $id . DIRECTORY_SEPARATOR . $target . '.html';

        try {
            $content = Storage::disk('mirror')->get($fullPath);
        } catch (\Exception $e) {
            $content = "内容不存在,请先采集或重新采集";

        }
        MirrorService::headerEncoding($content);

        list($scheme, $mainDomain) = MirrorService::parseDomain(url()->full());

        $content = MirrorService::replaceLink($scheme, $mainDomain, $content);
        $content = MirrorService::dtk(Mirror::find($id), $content);


        echo $content;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {

        $js = <<<EOT
        $('.box-tools').addClass('custom-tools')
        $('#app>.content-header').append($('.box-tools'));
EOT;

        \Admin::script($js);
        $show = new Show(Mirror::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('sub_domain', ll('Sub domain'));
        $show->field('targets', ll('Targets'));
        $show->field('title', ll('Title'));
        $show->field('keywords', ll('Keywords'));
        $show->field('description', ll('Description'));
        $show->field('conversion', ll('Conversion'));
        $show->field('is_ignore_dtk', ll('Is open dtk'));
        $show->field('user_agent', ll('User agent'));
        $show->field('replace_contents', ll('Replace contents'));
        $show->field('created_at', ll('Created at'));
        $show->field('updated_at', ll('Updated at'));

        // return $show;

        $show = $show->render();
        $show = str_replace('<div class="box box-info">', '<div class="box box-info content_show_default">', $show);
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Mirror());
        $form->select('category_id', ll('所属分类'))->options(Category::pluck('name', 'id'))->required();
        $form->switch('is_disabled', ll('是否禁用'));
        $help = "一行一个,需要包含http";
        $form->textarea('targets', ll('Targets'))->help($help)->required();
        $form->textarea('title', ll('Title'));
        $form->textarea('keywords', ll('Keywords'));
        $form->textarea('description', ll('Description'));

        $form->radio('conversion', ll('Conversion'))->options(MirrorConstant::conversionText())->default(MirrorConstant::CONVERSION_NO);
        $form->switch('is_ignore_dtk', ll('Is open dtk'));


        $help = sprintf("百度user-agent为:   %s<br>谷歌user-agent为:   %s<br>", GatherConstant::USER_AGENT_BAIDU_CONTENT, GatherConstant::USER_AGENT_GOOGLE_CONTENT);

        $form->text('user_agent', ll('User agent'))->help($help);

        $help = "一行一个,内容替换,格式为aaa---bbb,aaa将被替换为bbb";

        $form->textarea('replace_contents', ll('Replace contents'))->help($help);
        return $form;
    }
}
