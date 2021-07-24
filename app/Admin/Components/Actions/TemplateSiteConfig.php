<?php

namespace App\Admin\Components\Actions;

use Encore\Admin\Actions\RowAction;

class TemplateSiteConfig extends RowAction
{
    public $name = '站点配置';

    /**
     * 跳转地址
     *
     * @return string
     */
    public function href()
    {
        $categoryId = $this->row->category_id;

        return '/admin/sites?group_id=' . $this->getKey() . '&category_id='.$categoryId;
    }
}