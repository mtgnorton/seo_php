<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\AddTemplateWebsite;
use App\Models\Website;
use App\Services\CommonService;
// use Encore\Admin\Controllers\AdminController;

use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class WebsiteTemplateController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('Template website');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    public function grid()
    {
        $grid = new Grid(new Website());

        $groupId = request()->input('group_id');
        $grid->model()->where('group_id', $groupId);

        $grid->column('id', ll('Id'));
        $grid->column('website_id', ll('Url'))->display(function () {
            return $this->url ?? '';
        });
        // $grid->column('group_id', ll('Template id'));
        $grid->column('created_at', ll('Created at'));
        // $grid->column('updated_at', ll('Updated at'));
        $grid->disableCreateButton();
        // $grid->disablePagination();
        $grid->disableFilter();
        $grid->disableExport();
        // $grid->disableRowSelector();
        $grid->disableColumnSelector();
        $grid->actions(function ($actions) {
            // 去掉编辑
            $actions->disableEdit();
            // 去掉查看
            $actions->disableView();
        });

        $grid->tools(function ($tools) use ($groupId) {
            $templateUrl = '/admin/template-groups?group_id='.$groupId;
            $tools->append(CommonService::getActionJumpUrl($templateUrl, lp('Template group')));
            $tools->append(new AddTemplateWebsite($groupId));
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Website::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('website_id', ll('Website id'));
        $show->field('group_id', ll('Template id'));
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
        $form = new Form(new Website());

        $form->number('website_id', ll('Website id'));
        $form->number('group_id', ll('Template id'));

        return $form;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $idArr = explode(',', $id);

        foreach ($idArr as $idVal) {
            $this->form()->destroy($id);
        }

        return ll('Delete success');
    }
}