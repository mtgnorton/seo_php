<?php

namespace App\Admin\Controllers;


use Encore\Admin\Layout\Content;

/**
 * 控制器基类
 */
class BaseController extends AdminController
{
    protected $breadcrumbs;

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content, ...$args)
    {
        return $content
            ->title($this->title())
            ->description($this->description['index'] ?? trans('admin.list'))
            ->breadcrumb(...$args)
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['show'] ?? trans('admin.show'))
            ->breadcrumb(
                ['text' => '首页', 'url' => '/admin'],
                ['text' => '用户管理', 'url' => '/admin/users'],
                ['text' => '编辑用户']
            )->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->breadcrumb(
                ['text' => '首页', 'url' => '/admin'],
                ['text' => '用户管理', 'url' => '/admin/users'],
                ['text' => '编辑用户']
            )->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['create'] ?? trans('admin.create'))
            ->breadcrumb(
                ['text' => '首页', 'url' => '/admin'],
                ['text' => '用户管理', 'url' => '/admin/users'],
                ['text' => '编辑用户']
            )->body($this->form());
    }
}
