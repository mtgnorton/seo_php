<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\Gathers\Copy;
use App\Admin\Components\Actions\Gathers\MirrorGather;
use App\Admin\Components\Actions\Gathers\TestRegularContent;
use App\Admin\Components\Actions\Gathers\TestRegularUrl;
use App\Constants\GatherConstant;
use App\Constants\MirrorConstant;
use App\Models\Category;
use App\Models\Mirror;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
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

    public function __construct()
    {
        $this->title = ll('Mirror');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Mirror());
        $grid->actions(function ($actions) {
            $actions->add(new MirrorGather());
        });
        $grid->column('id', ll('Id'));
        $grid->column('category_id', ll('所属分类'))->display(function ($value) {
            return Category::find($value)->name;
        });
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
        $grid->column('title', ll('Title'));
        $grid->column('keywords', ll('Keywords'));
        $grid->column('description', ll('Description'));
        $grid->column('conversion', ll('Conversion'))->using(MirrorConstant::conversionText());
        $grid->column('is_ignore_dtk', ll('Is open dtk'))->display(function ($value) {
            return $value ? '是' : '否';
        });
//        $grid->column('user_agent', ll('User agent'));
        $grid->column('replace_contents', ll('Replace contents'));
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));

        return $grid;
    }

    /*采集镜像预览*/
    public function preview()
    {

        $target = request()->target;

        $id = request()->id;

        /* 获取url中域名的部分作为文件名 */
        $target = parse_url($target)['host'];

        $target = trim($target, '/');

        $fullPath = $id . DIRECTORY_SEPARATOR . $target . '.html';

        try {
            $content = Storage::disk('mirror')->get($fullPath);
        } catch (\Exception $e) {
            $content = "内容不存在,请先采集或重新采集";

        }


        /*字符编码 将gbk 转为*/
        if (strpos($content, 'charset=gbk') !== false) {
            header("content-type:text/html;charset=GBK");
        }
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
        $form->text('title', ll('Title'));
        $form->text('keywords', ll('Keywords'));
        $form->text('description', ll('Description'));

        $form->radio('conversion', ll('Conversion'))->options(MirrorConstant::conversionText())->default(MirrorConstant::CONVERSION_NO);
        $form->switch('is_ignore_dtk', ll('Is open dtk'));


        $help = sprintf("百度user-agent为:   %s<br>谷歌user-agent为:   %s<br>", GatherConstant::USER_AGENT_BAIDU_CONTENT, GatherConstant::USER_AGENT_GOOGLE_CONTENT);

        $form->text('user_agent', ll('User agent'))->help($help);

        $help = "一行一个,内容替换,格式为aaa---bbb,aaa将被替换为bbb";

        $form->textarea('replace_contents', ll('Replace contents'))->help($help);

        return $form;
    }
}
