<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 标签模型
 */
class Tag extends Model
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
        return $this->belongsTo('App\Models\DiyCategory');
    }

    /**
     * 和自定义数据一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function diys()
    {
        return $this->hasMany('App\Models\Diy');
    }

    // /**
    //  * 内容库标识获取器
    //  *
    //  * @param string $value
    //  * @return string
    //  */
    // public function getContentIdentifyAttribute($value)
    // {
    //     $types = Content::CONTENT_TYPE;

    //     return $types[$value] ?? '未知';
    // }

    // /**
    //  * 内容库标识获取器
    //  *
    //  * @param string $value
    //  * @return string
    //  */
    // public function getTypeAttribute($value)
    // {
    //     $types = Content::TAG_TYPE;

    //     return $types[$value] ?? '未知';
    // }
}
