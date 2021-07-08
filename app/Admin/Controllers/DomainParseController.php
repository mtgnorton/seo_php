<?php

namespace App\Admin\Controllers;

use App\Models\DomainParse;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DomainParseController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '域名解析';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DomainParse());
        $grid->disableActions();
        $grid->disableColumnSelector();

        $grid->disableFilter();
        $grid->column('id', __('ID'));
        $grid->column('domain', __('域名'));


        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new DomainParse());


        $form->display('id', __('ID'));

        if ($form->isCreating()) {
            $form->textarea('domain', '域名')->required()->help('一行一个');
        } else {
            $form->text('domain', '域名')->required();

        }

        $form->saving(function (Form $form) {

            if ($form->isEditing()) {
                DomainParse::replaceDomain($form->domain, $form->model()->domain);
            }
            if ($form->isCreating()) {
                $existDomains = DomainParse::getDomains();
                $domains      = explode("\r\n", $form->domain);
                foreach ($domains as $domain) {
                    if ($existDomains->contains($domain)) {
                        return back_error('域名已存在');
                    }
                }

                DomainParse::batchAddDomains($domains);
            }

            return redirect()->to('/admin/domain-parse');
        });


        return $form;
    }


    public function destroy($id) //批量删除
    {
        DomainParse::batchDelete(array_filter(explode(',', $id)));
        $response = [
            'status'  => true,
            'message' => trans('admin.delete_succeeded'),
        ];
        return response()->json($response);

    }

}
