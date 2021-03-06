<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\CollectedUrl
 *
 * @property int $id
 * @property int $gather_id 采集规则id
 * @property string $url 采集过的url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|CollectedUrl newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CollectedUrl newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CollectedUrl query()
 * @method static \Illuminate\Database\Eloquent\Builder|CollectedUrl whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CollectedUrl whereGatherId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CollectedUrl whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CollectedUrl whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CollectedUrl whereUrl($value)
 * @mixin \Eloquent
 */
class CollectedUrl extends Model
{

    protected $guarded = [];
}
