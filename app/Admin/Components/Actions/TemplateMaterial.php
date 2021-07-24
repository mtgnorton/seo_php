<?php

namespace App\Admin\Components\Actions;

use Encore\Admin\Actions\RowAction;

class TemplateMaterial extends RowAction
{
    public $name = '查看素材';

    /**
     * 跳转地址
     *
     * @return string
     */
    public function href()
    {
        return '/admin/template-materials?template_id=' . $this->getKey();
    }
}