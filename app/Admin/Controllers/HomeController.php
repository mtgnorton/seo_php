<?php

namespace App\Admin\Controllers;

use App\Constants\AuthCodeConstants;
use App\Http\Controllers\Controller;
use App\Models\SpiderRecord;
use App\Services\CommonService;
use App\Services\Gather\DynamicService;
use App\Services\ServerMonitorService;
use App\Services\SpiderService;
use Carbon\Carbon;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\str;

class HomeController extends Controller
{
    public function index(Content $content)
    {


        // return $content
        //     ->title('Dashboard')
        //     ->description('Description...')
        //     ->row(Dashboard::title())
        //     ->row(function (Row $row) {

        //         $row->column(4, function (Column $column) {
        //             $column->append(Dashboard::environment());
        //         });

        //         $row->column(4, function (Column $column) {
        //             $column->append(Dashboard::extensions());
        //         });

        //         $row->column(4, function (Column $column) {
        //             $column->append(Dashboard::dependencies());
        //         });
        //     });
        return $content
            ->title('')
            // ->icon('/asset/imgs/default_icon/a1.png')
            // ->row(function (Row $row){
            //     $row->column(4, function (Column $column) {
            //         // 获取数量
            //         $count = SpiderService::getCount('today');
            //         $column->append(new Box('', '今日蜘蛛统计:' . $count));
            //     });
            //     $row->column(4, function (Column $column) {
            //         // 获取数量
            //         $count = SpiderService::getCount('yesterday');
            //         $column->append(new Box('', '昨日蜘蛛统计:' . $count));
            //     });
            //     $row->column(4, function (Column $column) {
            //         // 获取数量
            //         $count = SpiderService::getCount('all');
            //         $column->append(new Box('', '全部蜘蛛统计:' . $count));
            //     });
            // })
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $userResult = <<<HTML
                    <section class="content-header">
                        <h1>
                            <img style="width:20px;height:20px;margin-right:5px;" src="/asset/imgs/default_icon/a1.png" alt="">
                            <span style="vertical-align:middle">系统首页</span>
                        </h1>
                    <section>
HTML;
                    $column->append($userResult);
                });
            })
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $todayCount     = SpiderService::getCount('today');
                    $yesterdayCount = SpiderService::getCount('yesterday');
                    $allCount       = SpiderService::getCount('all');
                    $userResult     = <<<HTML
                        <div class="home-box-list">
                            <div class="home-box home-box-y">今日蜘蛛统计: <span>{$todayCount}</span></div>
                            <div class="home-box home-box-t">昨日蜘蛛统计: <span>{$yesterdayCount}</span></div>
                            <div class="home-box home-box-a">全部蜘蛛统计: <span>{$allCount}</span></div>
                        </div>

HTML;
                    $column->append($userResult);
                });
            })
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $userResult = <<<HTML
                        <h1 class="home-title">信息记录</h1>
HTML;
                    $column->append($userResult);
                });
            })
            ->row(function (Row $row) {
                // $row->column(6, function (Column $column) {
                //     $userInfo = Admin::user();
                //     $username = $userInfo->username;
                //     $lastLoginedAt = conf('user.last_logined_at');
                //     $lastIp = conf('user.last_logined_ip');
                //     // 获取授权时间和剩余天数
                //     $beginTime = conf('auth.use_begin_time');
                //     $beginTimeFormat = date('Y-m-d H:i:s', $beginTime);
                //     // 判断账户类型是一年还是永久
                //     $effectiveType = conf('auth.effective_type');
                //     if ($effectiveType == AuthCodeConstants::EFFECTIVE_TYPE_ONE_YEAR) {
                //         $expiredTime = strtotime("+1years",$beginTime);
                //         $now = time();

                //         $day = intval(($expiredTime - $now) / (60 * 60 *24));

                //         $expiredDay = $day . '天';
                //     } else if ($effectiveType == AuthCodeConstants::EFFECTIVE_TYPE_ALL) {
                //         $expiredDay = '永久';
                //     } else {
                //         $expiredDay = '未知';
                //     }
                //     $userResult = <<<HTML
                //         <p>用户名: {$username}</p>
                //         <p>上次登录时间: {$lastLoginedAt}</p>
                //         <p>上次登录IP: {$lastIp}</p>
                //         <p>授权时间: {$beginTimeFormat}</p>
                //         <p>授权剩余时间: {$expiredDay}</p>
                //     HTML;
                //     $column->append(new Box('账户信息', $userResult));
                // });
                // $row->column(6, function (Column $column) {
                //     $notices = CommonService::getNotices();
                //     $noticeData = '';

                //     if (!empty($notices)) {
                //         foreach ($notices as $notice) {
                //             $noticeData .= <<<HTML
                //                 <p><a href="{$notice['address']}" target="__block" style="color:black">{$notice['title']}</a></p>
                //             HTML;
                //         }
                //     }
                //     $column->append(new Box('官网动态', $noticeData));
                // });
                $row->column(12, function (Column $column) {
                    $notices    = CommonService::getNotices();
                    $noticeData = '';
                    if (!empty($notices)) {
                        foreach ($notices as $notice) {
                            $noticeData .= <<<HTML
                            <p><a href="{$notice['address']}" target="__block">{$notice['title']}</a></p>
HTML;
                        }
                    }
                    $userInfo      = Admin::user();
                    $username      = $userInfo->username;
                    $lastLoginedAt = conf('user.last_logined_at');
                    $lastIp        = conf('user.last_logined_ip');
                    // 获取授权时间和剩余天数
                    $beginTime       = conf('auth.use_begin_time');
                    $effectiveDays   = conf('auth.effective_days');
                    $beginTimeFormat = date('Y-m-d H:i:s', $beginTime);


                    $link       = config('seo.official_domain') . '/index/home/news.html?cate=1';
                    $userResult = <<<HTML
                        <div class="home-box-list">
                            <div class="home-content">
                                <div class="home-content-name">账户信息</div>
                                <p>用户名: {$username}</p>
                                <p>上次登录时间: {$lastLoginedAt}</p>
                                <p>上次登录IP: {$lastIp}</p>
                                <p>授权时间: {$beginTimeFormat}</p>
                                <p>授权剩余时间: $effectiveDays 天</p>
                            </div>
                            <div class="home-content">
                                <div class="home-content-name">
                                    官网动态
                                    <div class="home-content-extra pull-right">
                                        <a href="{$link}" target="_blank" rel="noopener noreferrer">
                                            <span style="vertical-align:middle">查看更多</span>
                                            <!-- <img src="/asset/imgs/default_icon/r-icon.png" alt="" style="height:14px;"> -->
                                        </a>
                                    </div>
                                </div>
                                {$noticeData}

                            </div>
                        </div>
HTML;
                    $column->append($userResult);
                });
            })
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $userResult = <<<HTML
                        <h1 class="home-title">服务器状态</h1>
HTML;
                    $column->append($userResult);
                });
            })
            ->row(function (Row $row) {

                $sm = new ServerMonitorService();

                $row->column(12, function (Column $column) use ($sm) {


                    $memInfo  = $sm->getExecMem();
                    $diskInfo = $sm->getExecDisk();


                    $cpuAmount   = $sm->getCpuNumber();
                    $loadPercent = data_get($sm->getLoad(), '15m', 0);
                    if ($loadPercent > $sm->getCpuNumber()) {
                        $loadPercent = 100;
                    } else {

                        $loadPercent = round($loadPercent / $cpuAmount, 4) * 100;
                    }


                    $column->append(view('admin.chart.server_info', [
                        'upTime'      => $sm->getUpTime(),
                        'loadPercent' => $loadPercent,
                        'cpuPercent'  => $sm->getCpuPercent(),
                        'cpuAmount'   => $cpuAmount,
                        'memTotal'    => $memInfo['total'],
                        'memUsed'     => $memInfo['used'],
                        'memPercent'  => $memInfo['percent'],
                        'diskPercent' => $diskInfo['percent'],
                        'diskUsed'    => $diskInfo['used'],
                        'diskTotal'   => $diskInfo['total'],
                    ]));
                });
            })
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $userResult = <<<HTML
                        <h1 class="home-title">访问记录</h1>
HTML;
                    $column->append($userResult);
                });
            })
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    // $column->append(new Box(ll('Visit record'), view('admin.chart.spider_home')));
                    $column->append(view('admin.chart.spider_home'));
                });
            });
    }


}
