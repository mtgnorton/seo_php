<?php

namespace App\Console\Commands;

use App\Models\Title;
use App\Services\CommonService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AiContentMake extends Command
{
    /**
     * The name and signature of the console command. 
     *
     * @var string
     */
    protected $signature = 'aiContent:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        common_log('----------智能内容数据的缓存开始----------', null, [], 'ai-content');
        dump('----------智能内容数据的缓存开始----------');
        // 自动运行预存智能内容, 先将title和keyword表中的数据全部缓存下来, 缓存的同时记录下缓存的最大ID以及缓存失败ID
        $cacheTimeKey = 'aiContentTitleCaching';
        $cacheMaxIdKey = 'aiContentTitleMaxId';
        $cacheFailedIdsKey = 'aiContentTitleFailedData';
        $successDataKey = 'aiContentSuccess';
        $baseCacheContentKey = 'aiContentValue';
        // 4个小时, 按照最大15秒一个, 执行1000个
        $cacheTime = 3600;
        // 七天
        $contentCacheTime = 604800;
        // 固定第七天+六小时设为空
        $cacheDateTimeKey = 'aiContentTitleDateTime';
        $cacheDateTimeString = Cache::store('redis')->get($cacheDateTimeKey);
        $cacheDAteTimeHours = 174;
        if (empty($cacheDateTimeString)) {
            // 当天时间+七天六个小时
            $cacheDateTime = Carbon::parse('+'.$cacheDAteTimeHours.' hours');
            $cacheDateTimeString = $cacheDateTime->toDateTimeString();

            Cache::store('redis')->put($cacheDateTimeKey, $cacheDateTimeString, $cacheDateTime);
        } else {
            // 判断到期时间是否大于当前时间
            $cacheDateTime = Carbon::parse($cacheDateTimeString);
            if ($cacheDateTime->lt(Carbon::now())) {
                // 当天时间+七天六个小时
                $cacheDateTime = Carbon::parse('+'.$cacheDAteTimeHours.' hours');
                $cacheDateTimeString = $cacheDateTime->toDateTimeString();
    
                Cache::store('redis')->put($cacheDateTimeKey, $cacheDateTimeString, $cacheDateTime);
            }
        }
        try {
            // 如果缓存中是否有该值, 且该值是否为true, 则直接退出, 保证单线程执行
            if (Cache::store('redis')->get($cacheTimeKey, false)) {
                common_log('----------智能内容数据的缓存结束, 正在缓存中----------', null, [], 'ai-content');

                return '';
            }
    
            // 将缓存状态改为缓存中
            Cache::store('redis')->put($cacheTimeKey, true, $cacheTime);
    
            // 获取当前title表最大id和记录的title最大ID
            $maxId = Title::max('id');
            $cacheMaxId = Cache::store('redis')->get($cacheMaxIdKey, 0);
            $failedData = Cache::store('redis')->get($cacheFailedIdsKey, []);
            $successData = Cache::store('redis')->get($successDataKey, []);
            // 如果最大ID与缓存中最大ID相等, 则执行失败的数据
            if ($maxId <= $cacheMaxId) {
                if (empty($failedData)) {
                    // 将缓存状态改为false
                    Cache::store('redis')->put($cacheTimeKey, false);
                    common_log('----------智能内容数据的缓存结束, 暂无需要缓存数据----------', null, [], 'ai-content');
                    dump('----------智能内容数据的缓存结束, 暂无需要缓存数据----------');
    
                    return '';
                }
    
                // 循环进行数据的缓存
                try {
                    foreach ($failedData as $id => $title) {
                        if (empty($title)) {
                            unset($failedData[$id]);
                            continue;
                        }
                        $cacheKey = $baseCacheContentKey . '_' . $title;
                        // 如果缓存中存在该值, 则直接跳过
                        if (Cache::store('file')->get($cacheKey, false)) {
                            unset($failedData[$id]);
                            continue;
                        }
                        $content = CommonService::getSearchContent($title, 1, 'cache');
                        if (empty($content)) {
                            continue;
                        }
                        // 将内容数据写入缓存
                        Cache::store('file')->put($cacheKey, $content, $contentCacheTime);
                        if (count($successData) < 100) {
                            $successData[] = $cacheKey;
                        }

                        // 从failedData中删除掉该值
                        unset($failedData[$id]);
                    }
                    // 更新失败数据
                    Cache::store('redis')->put($cacheFailedIdsKey, $failedData, $cacheDateTime);
                    // 更新成功数据
                    Cache::store('redis')->put($successDataKey, $successData, $contentCacheTime);
                    // 更新缓存中为false
                    Cache::store('redis')->put($cacheTimeKey, false, $cacheTime);
                } catch (Exception $e) {
                    // 更新失败数据
                    Cache::store('redis')->put($cacheFailedIdsKey, $failedData, $cacheDateTime);
                    // 更新成功数据
                    Cache::store('redis')->put($successDataKey, $successData, $contentCacheTime);
                    // 更新缓存中为false
                    Cache::store('redis')->put($cacheTimeKey, false, $cacheTime);

                    throw $e;
                }
            } else {
                // 从缓存中最大的key开始, 获取连续1000条进行缓存
                $limit = 400;
                $data = Title::where('id', '>', $cacheMaxId)
                            ->limit($limit)
                            ->orderBy('id', 'asc')
                            ->pluck('content', 'id')
                            ->toArray();

                try {
                    foreach ($data as $key => $val) {
                        $cacheKey = $baseCacheContentKey . '_' . $val;
                        // 如果缓存中存在该值, 则直接跳过
                        if (Cache::store('file')->get($cacheKey, false)) {
                            $cacheMaxId = $key;
                            if (count($successData) < 100) {
                                $successData[] = $cacheKey;
                            }
                            continue;
                        }
                        $content = CommonService::getSearchContent($val, 1, 'cache');
                        if (empty($content)) {
                            $failedData[$key] = $val;
                            continue;
                        }
                        // 将内容数据写入缓存
                        Cache::store('file')->put($cacheKey, $content, $contentCacheTime);
                        if (count($successData) < 100) {
                            $successData[] = $cacheKey;
                        } else {
                            array_shift($successData);
                            $successData[] = $cacheKey;
                        }
                        // 更新最大缓存ID
                        $cacheMaxId = $key;
                    }

                    // 更新缓存最大ID
                    Cache::store('redis')->put($cacheMaxIdKey, $cacheMaxId, $cacheDateTime);
                    // 更新失败数据
                    Cache::store('redis')->put($cacheFailedIdsKey, $failedData, $cacheDateTime);
                    // 更新成功数据
                    Cache::store('redis')->put($successDataKey, $successData, $contentCacheTime);
                    // 更新缓存中为false
                    Cache::store('redis')->put($cacheTimeKey, false, $cacheTime);
                } catch (Exception $e) {
                    // 更新失败数据
                    Cache::store('redis')->put($cacheFailedIdsKey, $failedData, $cacheDateTime);
                    // 更新缓存最大ID
                    Cache::store('redis')->put($cacheMaxIdKey, $cacheMaxId, $cacheDateTime);
                    // 更新成功数据
                    Cache::store('redis')->put($successDataKey, $successData, $contentCacheTime);
                    // 更新缓存中为false
                    Cache::store('redis')->put($cacheTimeKey, false, $cacheTime);

                    throw $e;
                }
            }
        } catch (Exception $e) {
            common_log('智能内容缓存失败', $e, [], 'ai-content');
            common_log('----------智能内容数据的缓存结束----------', null, [], 'ai-content');
        }
        dump('----------智能内容数据的缓存结束----------');
        common_log('----------智能内容数据的缓存结束----------', null, [], 'ai-content');
    }
}
