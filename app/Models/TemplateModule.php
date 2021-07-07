<?php

namespace App\Models;

use App\Events\ModuleDeletingEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * 模板类型模型
 */
class TemplateModule extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 绑定删除事件
     */
    protected $dispatchesEvents = [
        'deleting' => ModuleDeletingEvent::class
    ];

    /**
     * 和模板多对一的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo('App\Models\Template');
    }

    /**
     * 模块页面
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pages()
    {
        return $this->hasMany('App\Models\TemplateModulePage', 'module_id');
    }
}
