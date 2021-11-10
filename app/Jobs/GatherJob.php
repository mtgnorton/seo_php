<?php

namespace App\Jobs;

use App\Constants\GatherConstant;
use App\Jobs\Middleware\RateLimited;
use App\Models\Gather;
use App\Services\GatherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Redis;

class GatherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $model;


    public $tries = 0;

    public $timeout = GatherConstant::CRONTAB_TIMEOUT;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Gather $model)
    {
        $this->model = $model;
    }

    public function middleware()
    {

        return [];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $g = new GatherService();

        $logModal = $this->model->crontabLogs()->create([
            'setting_content_amount' => $this->model->crontab_setting_content_amount,
            'setting_url_amount'     => $this->model->crontab_setting_url_amount,
            'setting_interval_time'  => $this->model->crontab_setting_interval_time,
            'setting_timeout_time'   => $this->model->crontab_setting_timeout_time,
            'gather_log'             => ''
        ]);


        gather_crontab_log('当前的规则为:', $this->model->toArray());

        try {
            $g->dynamic(
                $this->model,
                $this->model->crontab_setting_content_amount,
                $this->model->crontab_setting_url_amount,
                $this->model->crontab_setting_interval_time,
                $this->model->crontab_setting_timeout_time,
                $logModal->id

            );
        } catch (\Exception $e) {
            $logModal->update([
                'error_log' => full_error_msg($e)
            ]);
        }

        $g->crontabFlushLog();

        $logModal->update([
            'end_time' => Carbon::now()
        ]);

    }


}
