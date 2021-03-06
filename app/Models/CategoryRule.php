<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 分类url规则模型
 */
class CategoryRule extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 和分类多对一的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }
}
