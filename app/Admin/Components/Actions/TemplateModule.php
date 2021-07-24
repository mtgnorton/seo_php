<?php

namespace App\Admin\Components\Actions;

use Encore\Admin\Actions\RowAction;

class TemplateModule extends RowAction
{
    public $name = '查看模块';

    /**
     * 跳转地址
     *
     * @return string
     */
    public function href()
    {
        return '/admin/template-modules?template_id=' . $this->getKey();
    }
}
