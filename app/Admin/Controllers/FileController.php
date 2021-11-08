<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\ClearFileData;
use App\Admin\Components\Actions\ImportArticle;
use App\Admin\Components\Actions\ImportFile;
use App\Admin\Components\Actions\ImportLocalImage;
use App\Models\Category;
use App\Models\ContentCategory;
use App\Models\File;
use App\Services\CommonService;
use App\Services\ContentService;

use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Callout;

class FileController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $type = request()->input('type');

        $this->title = lp(ucfirst($type), 'File');
    }

    /**
     * 首页方法
     *
     * @param Content $content
     * @return void
     */
    public function index(Content $content)
    {
        $gridRes = $this->grid();
        $groupId = $gridRes['group_id'] ?? 0;
        $grid = $gridRes['grid'];

        $tempRes = $content
            ->title($this->title());
        if (empty($groupId)) {
            $tempRes->row(new Callout("
                当前页面分类数据异常, 请刷新重试
            ", "注意事项", 'warning'));
        }
        
        return $tempRes->body($grid);
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new File());

        // $tagId = request()->input('tag_id', 0);
        $categoryId = request()->input('category_id', 0);
        $type = request()->input('type', '');

        // if (!empty($tagId)) {
        //     // $ids = ContentService::getFileIdsByTag($tagId);
        //     // $grid->model()->whereIn('id', $ids);
        //     $grid->model()->where('tag_id', $tagId);
        // }
        if (!empty($categoryId)) {
            // $ids = ContentService::getFileIdsByCategory($categoryId, $type);
            // $grid->model()->whereIn('id', $ids);
            $grid->model()->where('category_id', $categoryId);
        }

        $grid->column('id', ll('Id'))->sortable();
        $grid->column('name', ll('File name'));
        $grid->column('path', ll('File path'))->downloadable();
        // $grid->column('ext', ll('File ext'));
        $grid->column('size', ll('File size'))->filesize();
        $grid->column('rows', ll('Rows'));
        $grid->column('success_rows', lp('Success', 'Rows'));
        $grid->column('message', lp('Upload', 'Message'))->display(function ($message) {
            if ($this->is_collected == 1) {
                return '上传完毕';
            } else  {
                return $message ?: '数据处理中...';
            }
        });
        $grid->column('is_collected', lp('Is collected'))->display(function ($isCollected) {
            return $isCollected == 1 ? '是' : '否';
        });
        $grid->column('type', ll('Content type'));
        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));
        // 禁用导出
        $grid->disableExport();
        // 禁用创建
        $grid->disableCreateButton();
        // 查询
        $grid->filter(function($filter){
            // 标题搜索
            $filter->like('name', ll('File name'));
            // 分类
            // $filter->equal('type', ll('Content type'))
            //         ->select(ContentService::CONTENT_TYPE);
        });
        $grid->actions(function ($actions) {
            // 去掉编辑
            $actions->disableEdit();
            // 去掉编辑
            $actions->disableview();
        });

        $groupId = 0;
        $grid->tools(function (Grid\Tools $tools) use ($categoryId, $type, &$groupId) {
            $baseHelp = lp('Text format', ',', 'File size and number limit');
            if ($type == 'image') {
                $help = $baseHelp . ll('Image sum limit');
            } else if ($type == 'article') {
                $help = $baseHelp . ll('Article help');
            } else {
                $help = $baseHelp;
            }
            $tools->append(new ImportFile($categoryId, $type, $help));
            $tools->append(new ClearFileData($categoryId));
            if ($type == 'image') {
                $tools->append(new ImportLocalImage($categoryId, $baseHelp));
            } else if ($type == 'article') {
                $templateUrl = '/admin/articles/create?category_id='.$categoryId;
                $tools->append(CommonService::getActionJumpUrl($templateUrl, lp('Publish article'), 'primary', 10, 'fa-upload'));
            }

            // if ($type == 'diy') {
            //     $templateUrl = '/admin/diy-categories';
            // } else {
            $groupId = ContentCategory::find($categoryId)->group_id ?? 0;
            $templateUrl = '/admin/content-categories?type='.$type.'&group_id='.$groupId;
            // }
            $tools->append(CommonService::getActionJumpUrl($templateUrl, lp('Category', 'Page')));
        });
        $js = <<<EOT
        var h = `<section class="tip_modal load_body">
            <div class="tip_content"> 
                <div class="cloud"><\/div>
                文件上传中... 
            <\/div>
        <\/section>`
        $(".box.grid-box").append(h);
        $(".btn.btn-primary").click(function(){
            let timer = setTimeout(()=>{
                if(timer){
                    clearTimeout(timer)
                };
                window.location.reload();
            }, 600*1000);
            $('.tip_modal').show();
        });
EOT;

        Admin::script($js);
        Admin::style('
            .load_body{
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, .3);
                z-index: 9999;
                display: none;
            }
            .tip_content{
                width: 200px;
                height: 100px;
                margin: 100px auto;
                border-radius: 10px;
                background-color: rgba(0,0,0, .4);
                color: #fff;
                text-align:center;
                padding-top: 1px;
            }
            .cloud{
                margin: 20px 80px;
                width: 4px;
                height: 10px;
                opacity: 0.5;
                position: relative;
                box-shadow: 6px 0px 0px 0px rgba(255,255,255,1),
                            12px 0px 0px 0px rgba(255,255,255,1),
                            18px 0px 0px 0px rgba(255,255,255,1),
                            24px 0px 0px 0px rgba(255,255,255,1),
                            30px 0px 0px 0px rgba(255,255,255,1),
                            36px 0px 0px 0px rgba(255,255,255,1);
                
                -webkit-animation: rain 1s linear infinite alternate;
                   -moz-animation: rain 1s linear infinite alternate;
                        animation: rain 1s linear infinite alternate;
            }
            .cloud:after{
                width: 40px;
                height: 10px;
                position: absolute;
                content: "";
                background-color: rgba(255,255,255,1);
                top: 0px;
                opacity: 1;
                -webkit-animation: line_flow 2s linear infinite reverse;
                   -moz-animation: line_flow 2s linear infinite reverse;
                        animation: line_flow 2s linear infinite reverse;
            }
            
            @-webkit-keyframes rain{
                0%{
                 box-shadow: 6px 0px 0px 0px rgba(255,255,255,1),
                            12px 0px 0px 0px rgba(255,255,255,0.9),
                            18px 0px 0px 0px rgba(255,255,255,0.7),
                            24px 0px 0px 0px rgba(255,255,255,0.6),
                            30px 0px 0px 0px rgba(255,255,255,0.3),
                            36px 0px 0px 0px rgba(255,255,255,0.2);
                }
                100%{
                box-shadow: 6px 0px 0px 0px rgba(255,255,255,0.2),
                            12px 0px 0px 0px rgba(255,255,255,0.3),
                            18px 0px 0px 0px rgba(255,255,255,0.6),
                            24px 0px 0px 0px rgba(255,255,255,0.7),
                            30px 0px 0px 0px rgba(255,255,255,0.9),
                            36px 0px 0px 0px rgba(255,255,255,1);
                    opacity: 1;
                }
            }
            @-moz-keyframes rain{
                0%{
                 box-shadow: 6px 0px 0px 0px rgba(255,255,255,1),
                            12px 0px 0px 0px rgba(255,255,255,0.9),
                            18px 0px 0px 0px rgba(255,255,255,0.7),
                            24px 0px 0px 0px rgba(255,255,255,0.6),
                            30px 0px 0px 0px rgba(255,255,255,0.3),
                            36px 0px 0px 0px rgba(255,255,255,0.2);
                }
                100%{
                box-shadow: 6px 0px 0px 0px rgba(255,255,255,0.2),
                            12px 0px 0px 0px rgba(255,255,255,0.3),
                            18px 0px 0px 0px rgba(255,255,255,0.6),
                            24px 0px 0px 0px rgba(255,255,255,0.7),
                            30px 0px 0px 0px rgba(255,255,255,0.9),
                            36px 0px 0px 0px rgba(255,255,255,1);
                    opacity: 1;
                }
            }
            @keyframes rain{
                0%{
                 box-shadow: 6px 0px 0px 0px rgba(255,255,255,1),
                            12px 0px 0px 0px rgba(255,255,255,0.9),
                            18px 0px 0px 0px rgba(255,255,255,0.7),
                            24px 0px 0px 0px rgba(255,255,255,0.6),
                            30px 0px 0px 0px rgba(255,255,255,0.3),
                            36px 0px 0px 0px rgba(255,255,255,0.2);
                }
                100%{
                box-shadow: 6px 0px 0px 0px rgba(255,255,255,0.2),
                            12px 0px 0px 0px rgba(255,255,255,0.3),
                            18px 0px 0px 0px rgba(255,255,255,0.6),
                            24px 0px 0px 0px rgba(255,255,255,0.7),
                            30px 0px 0px 0px rgba(255,255,255,0.9),
                            36px 0px 0px 0px rgba(255,255,255,1);
                    opacity: 1;
                }
            }
            
            @-webkit-keyframes line_flow{
                0%{ width: 0px;}
                100%{width: 40px;}
            }
            @-moz-keyframes line_flow{
                0%{ width: 0px;}
                100%{width: 40px;}
            }
            @keyframes line_flow{
                0%{ width: 0px;}
                100%{width: 40px;}
            }
            
        ');

        return [
            'grid' => $grid,
            'group_id' => $groupId,
        ];
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(File::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('name', ll('Name'));
        $show->field('path', ll('Path'))->file();
        $show->field('ext', ll('Ext'));
        $show->field('size', ll('Size'));
        $show->field('rows', ll('Rows'));
        $show->field('type', ll('Type'));
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
        $form = new Form(new File());

        $form->text('name', ll('Name'));
        $form->text('path', ll('Path'));
        $form->text('ext', ll('Ext'));
        $form->number('size', ll('Size'));
        $form->number('rows', ll('Rows'));
        $form->text('type', ll('Type'));

        return $form;
    }
}
