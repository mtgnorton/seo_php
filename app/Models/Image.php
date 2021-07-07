<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 图片模型
 */
class Image extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 和文件多对一的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function file()
    {
        return $this->belongsTo('App\Models\File');
    }

    /**
     * 和分类多对一的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo('App\Models\ContentCategory');
    }
}
