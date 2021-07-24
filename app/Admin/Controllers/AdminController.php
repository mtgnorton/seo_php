<?php

namespace App\Admin\Controllers;

use Encore\Admin\Layout\Content;

class AdminController extends \Encore\Admin\Controllers\AdminController
{

    protected $icon = "";

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

        return $content
            ->title('')
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($grid);
    }


    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     *
     * @return Content
     */
    public function edit($id, Content $content)
    {
        $titleHeader = <<<EOT
            <img class="title-icon" src="$this->icon" class="default" alt="">
            <label class="title-word">$this->title</label>
EOT;

        return $content
            ->title($titleHeader)
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->form()->edit($id));
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
        $titleHeader = <<<EOT
            <img class="title-icon" src="$this->icon" class="default" alt="">
            <label class="title-word">$this->title</label>
EOT;
        $form        = $this->form()->render();
        $form        = str_replace('<div class="box-body">', '<div class="box-body box-body_default"><h1 class="home-title">创建</h1>', $form);

        return $content
            ->title($titleHeader)
            ->description($this->description['create'] ?? trans('admin.create'))
            ->body($form);
    }
}
