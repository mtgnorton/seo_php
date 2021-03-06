<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Mirror
 *
 * @property int $id
 * @property string $sub_domain 泛域名
 * @property string|null $targets 目标站,多个,一行一个
 * @property string $title 标题
 * @property string $keywords 标题
 * @property string $description 描述
 * @property string $conversion 简繁 to_complex,中英 to_english
 * @property int $is_ignore_dtk 是否开启dtk,0否 1是
 * @property string $user_agent
 * @property string|null $replace_contents 替换内容,一行一个
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror query()
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror whereConversion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror whereIsOpenDtk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror whereKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror whereReplaceContents($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror whereSubDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror whereTargets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Mirror whereUserAgent($value)
 * @mixin \Eloquent
 */
class Mirror extends Model
{
    //
}
