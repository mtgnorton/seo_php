<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 菜单模型
 */
class Menu extends Model
{
    /**
     * 数据库名
     *
     * @var string
     */
    protected $table = 'admin_menu';

    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 与子类一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany('App\Models\Menu', 'parent_id');
    }

    /**
     * 与子类一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function parent()
    {
        return $this->belongsTo('App\Models\Menu');
    }
}
