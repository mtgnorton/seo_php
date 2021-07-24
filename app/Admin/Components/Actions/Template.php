<?php

namespace App\Admin\Components\Actions;

use Encore\Admin\Actions\RowAction;

class Template extends RowAction
{
    public $name = '查看模板';

    /**
     * 跳转地址
     *
     * @return string
     */
    public function href()
    {
        $groupId = $this->row->id;

        return '/admin/templates?group_id='.$groupId;
    }
}