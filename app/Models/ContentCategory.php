<?php

namespace App\Models;

use Encore\Admin\Traits\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;

/**
 * 内容分类模型
 */
class ContentCategory extends Model
{
    use ModelTree, AdminBuilder;

    public function __construct(array $attributes=[])
    {
        parent::__construct($attributes);
 
        $this->setParentColumn('parent_id'); // 设置父类ID的字段名称
        $this->setOrderColumn('sort'); // 设置排序字段名称
        $this->setTitleColumn('name'); // 设置标题名称
    }

    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 与自定义标签一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tags()
    {
        return $this->hasMany('App\Models\Tag', 'category_id');
    }

    /**
     * 与子类一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany('App\Models\ContentCategory', 'parent_id');
    }

    /**
     * 与子类一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function parent()
    {
        return $this->belongsTo('App\Models\ContentCategory');
    }

    /**
     * 与子类一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }
}
