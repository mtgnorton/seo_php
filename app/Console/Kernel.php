<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();


        // 每分钟执行一次,查看是否有需要投递的采集任务
        $schedule->command('scanning:gathers')
            ->everyMinute()
            ->withoutOverlapping();

        return;
        // 每分钟运行一次推送地址操作
        $schedule->command('baiduUrl:push')
            ->everyMinute()
            ->withoutOverlapping();
        // 每小时运行一次360访问地址方法
        $schedule->command('qihooUrl:push')
            ->hourly()
            ->withoutOverlapping();


        $schedule->command('sougouUrl:push')
            ->everyMinute()
            ->withoutOverlapping();

        // 每分钟运行一次删除模板文件操作
        $schedule->command('file:delete')
            ->everyMinute()
            ->withoutOverlapping();


    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
