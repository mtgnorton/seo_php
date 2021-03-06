<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 模板类型模型
 */
class TemplateType extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 和模板一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function templates()
    {
        return $this->hasMany('App\Models\Template', 'type_id');
    }
}
