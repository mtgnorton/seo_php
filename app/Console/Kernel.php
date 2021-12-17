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
        // 每分钟运行一次推送地址操作
        $schedule->command('baiduUrl:push')
            ->everyMinute()
            ->withoutOverlapping();
        // 每小时运行一次360访问地址方法
        $schedule->command('qihooUrl:push')
            ->everyMinute()
            ->withoutOverlapping();


        $schedule->command('sougouUrl:push')
            ->everyMinute()
            ->withoutOverlapping();

        // 每分钟运行一次删除模板文件操作
        $schedule->command('file:delete')
            ->everyMinute()
            ->withoutOverlapping();

        // // 每30分钟运行一次更新蜘蛛数据的操作
        // $schedule->command('spider:update')
        //     ->everyThirtyMinutes()
        //     ->withoutOverlapping();

        // 每半个小时运行一次蜘蛛数据缓存操作
        $schedule->command('spider:cache')
            ->everyThirtyMinutes()
            ->withoutOverlapping();

        // 每五分钟运行一次缓存智能内容操作
        $schedule->command('aiContent:make')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // 每五分钟运行一次缓存智能关键词内容操作
        $schedule->command('aiContent:keyword')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // // 每天运行一次删除导出模板包
        // $schedule->command('export:delete')
        //     ->dailyAt('03:00')
        //     ->withoutOverlapping();


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
