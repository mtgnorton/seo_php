<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 菜单模型
 */
class OperationLog extends Model
{
    /**
     * 数据库名
     *
     * @var string
     */
    protected $table = 'admin_operation_log';

    /**
     * 黑名单
     */
    protected $guarded = [];
}
