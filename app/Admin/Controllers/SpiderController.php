<?php

namespace App\Admin\Controllers;

use App\Constants\SpiderConstant;
use App\Models\SpiderRecord;
use App\Services\TemplateService;
use Carbon\Carbon;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Illuminate\Http\Request;

class SpiderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'SpiderRecord';

    public function __construct()
    {
        // $this->title = lp('Spider record');
        $this->title = ('蜘蛛访问日志-访问记录');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SpiderRecord());

        $grid->column('id', ll('Id'));
        $grid->column('type', ll('Type'))->display(function ($type) {
            $typeData = SpiderConstant::typeText();

            return $typeData[$type] ?? '';
        });
        $grid->column('ip', ll('Ip'));
        $grid->column('host', ll('Host'));
        $grid->column('url', ll('Url'));
        $grid->column('url_type', lp('Url', 'Type'));
        $grid->column('category_id', ll('Category'))->display(function () {
            return $this->category->name ?? '';
        });
        $grid->column('template_id', ll('Template'))->display(function () {
            return $this->template->name ?? '';
        });
        $grid->column('created_at', ll('Created at'));

        // 禁用导出
        $grid->disableExport();
        // 禁用创建
        $grid->disableCreateButton();
        // 禁用行操作列
        $grid->disableActions();
        // 禁用查询过滤器
        // $grid->disableFilter();
        // 查询
        $grid->filter(function($filter){
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            // 标题搜索
            // $filter->date('created_at', ll('Created at'));
            $filter->between('created_at', ll('Created at'))->datetime();
            $filter->equal('type', ll('Type'))->radio(SpiderConstant::typeText())
                    ->default('');
            $filter->like('host', ll('Host'));
        });

        $grid->selector(function (Grid\Tools\Selector $selector) {
            // 类型规格选择器
            $selector->select(
                'type',
                ll('Template type'),
                SpiderConstant::typeText()
            );
        });

        $grid->header(function ($query) {
            return new Box(ll(''), view('admin.chart.spider_pie_chart'));
        });
        $grid = $grid->render();
        $grid = str_replace('<div class="box grid-box">','<div class="box grid-box content_spider_default">', $grid);
        return $grid;
        // return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(SpiderRecord::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('type', ll('Type'));
        $show->field('ip', ll('Ip'));
        $show->field('host', ll('Host'));
        $show->field('url', ll('Url'));
        $show->field('url_type', ll('Url type'));
        $show->field('category_id', ll('Category id'));
        $show->field('template_id', ll('Template id'));
        $show->field('created_at', ll('Created at'));
        $show->field('updated_at', ll('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SpiderRecord());

        $form->text('type', ll('Type'))->default('other');
        $form->ip('ip', ll('Ip'));
        $form->text('host', ll('Host'));
        $form->url('url', ll('Url'));
        $form->text('url_type', ll('Url type'));
        $form->number('category_id', ll('Category id'));
        $form->number('template_id', ll('Template id'));

        return $form;
    }

    /**
     * 饼状图数据
     *
     * @param Request $request
     * @return void
     */
    public function pieData(Request $request)
    {
        $day = $request->input('day', 0);

        $title = '';
        $condition = [];
        // 今日
        if ($day == 0) {
            $title = '今日访问比率';
            $condition = [
                Carbon::now()->startOfDay()->toDateTimeString(),
                Carbon::now()->endOfDay()->toDateTimeString(),
            ];
        } else if ($day == 1) {
            $title = '昨日访问比率';
            $condition = [
                Carbon::yesterday()->startOfDay()->toDateTimeString(),
                Carbon::yesterday()->endOfDay()->toDateTimeString(),
            ];
        } else {
            if ($day == 365) {
                $title = '近一年访问比率';
            } else {
                $title = '近' . $day . '天访问比率';
            }
            $condition = [
                Carbon::parse('-'.($day - 1).' days')->startOfDay()->toDateTimeString(),
                Carbon::now()->endOfDay()->toDateTimeString(),
            ];
        }

        $spiderTypes = SpiderConstant::typeText();
        unset($spiderTypes['']);

        $values = [];
        $labels = [];

        foreach($spiderTypes as $key => $type) {
            $count = SpiderRecord::where('type', $key)
                            ->whereBetween('created_at', $condition)
                            ->count();
            $values[] = $count;
            $labels[] = ll(ucfirst($key));
        }

        $result = compact('labels', 'values', 'title');

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取每日数据
     *
     * @param Request $request
     * @return void
     */
    public function hourData(Request $request)
    {
        $result = [];
        $result['labels'] = [
            '00', '01', '02', '03',
            '04', '05', '06', '07',
            '08', '09', '10', '11',
            '12', '13', '14', '15',
            '16', '17', '18', '19',
            '20', '21', '22', '23',
        ];

        $day = $request->input('day', 0);

        $date = Carbon::parse('-'.$day.' days')->toDateString();
        $baseTitle = '蜘蛛时段走势图';
        $title = '';
        if ($day == 0) {
            $title = '今日' . $baseTitle;
        } else if ($day == 1) {
            $title = '昨日' . $baseTitle;
        } else if ($day == 2) {
            $title = '前日' . $baseTitle;
        } else {
            $title = $baseTitle;
        }

        $condition = [];
        $data = [];
        // 循环获取每小时的数量
        for($i=0; $i<24; $i++) {
            $dateBase = $date . ' ' . sprintf('%02d', $i);
            $condition = [
                $dateBase . ':00:00',
                $dateBase . ':59:59',
            ];

            $data[] = SpiderRecord::whereBetween('created_at', $condition)
                                            ->count();
        }
        $backgroundColor = ['rgb(54, 162, 235)'];
        $label = '蜘蛛访问次数(次/小时)';

        $result['values'][] = compact('data', 'backgroundColor', 'label');
        $result['title'] = $title;

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取多日数据
     *
     * @param Request $request
     * @return void
     */
    public function dayData(Request $request)
    {
        $type = $request->input('type', 'all');
        $day = $request->input('day', 10);
        $result = [];
        $labels = [];

        $baseTitle = '蜘蛛走势图';
        $title = $baseTitle;
        if ($day == 10) {
            $title = '近10日' . $baseTitle;
            $eachCount = 1;
        } else if ($day == 30) {
            $title = '近30日' . $baseTitle;
            $eachCount = 1;
        } else if ($day == 365) {
            $title = '近1年' . $baseTitle;
            $eachCount = 5;
        }

        // 全部
        $condition = [];
        $data = [];
        $values = [];
        $count = bcdiv($day, $eachCount, 0);
        if ($type == 'all') {
            for ($i=1; $i<=$count; $i++) {
                $startDay = $day - bcmul($eachCount, $i, 0);
                $endDay = $day - bcmul($eachCount, ($i + 1), 0) + 1;

                $labels[] = Carbon::parse('-'.$startDay.' days')->toDateString();
                $condition = [
                    Carbon::parse('-'.$startDay.' days')->startOfDay()->toDateTimeString(),
                    Carbon::parse('-'.$endDay.' days')->endOfDay()->toDateTimeString(),
                ];

                $data[] = SpiderRecord::whereBetween('created_at', $condition)
                                    ->count();
            }
            $backgroundColor = ['rgb(54, 162, 235)'];
            $label = '全部';

            $values[] = compact('data', 'backgroundColor', 'label');

            $result = compact('values', 'title', 'labels');

            return json_encode($result, JSON_UNESCAPED_UNICODE);
        } else {
            // 明细
            $spiderTypes = SpiderConstant::typeText();
            $colorData = [
                ['rgb(54, 162, 235)'],
                ['rgb(255, 99, 132)'],
                ['rgb(255, 205, 86)'],
                ['rgb(255,182,193)'],
                ['rgb(148,0,211)'],
                ['rgb(65,105,225)'],
                ['rgb(127,255,170)'],
            ];

            foreach ($spiderTypes as $key => $spiderType) {
                for ($i=1; $i<=$count; $i++) {
                    $startDay = $day - bcmul($eachCount, $i, 0);
                    $endDay = $day - bcmul($eachCount, ($i + 1), 0) + 1;

                    if ($key == 'baidu') {
                        $labels[] = Carbon::parse('-'.$startDay.' days')->toDateString();
                    }
                    $condition = [
                        Carbon::parse('-'.$startDay.' days')->startOfDay()->toDateTimeString(),
                        Carbon::parse('-'.$endDay.' days')->endOfDay()->toDateTimeString(),
                    ];

                    $data[] = SpiderRecord::where('type', $key)
                                        ->whereBetween('created_at', $condition)
                                        ->count();
                }
                $label = ll(ucfirst($key));
                $fill = false;
                $borderColor = array_shift($colorData);

                $values[] = compact('data', 'borderColor', 'label', 'fill');
                $data = [];
            }
            $result = compact('values', 'title', 'labels');

            return json_encode($result, JSON_UNESCAPED_UNICODE);
        }
    }
}
