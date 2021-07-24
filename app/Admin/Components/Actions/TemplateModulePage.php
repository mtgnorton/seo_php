<?php

namespace App\Admin\Components\Actions;

use App\Models\TemplateModule;
use Encore\Admin\Actions\RowAction;

class TemplateModulePage extends RowAction
{
    public $name = '查看文件';

    /**
     * 跳转地址
     *
     * @return string
     */
    public function href()
    {
        $module = TemplateModule::find($this->getKey());

        $query = http_build_query([
            'module_id' => $module->id
        ]);

        return '/admin/template-module-pages?' . $query;
    }
}