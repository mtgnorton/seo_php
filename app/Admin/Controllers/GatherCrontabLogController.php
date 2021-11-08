<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\Clear;
use App\Admin\Components\Actions\Gathers\ClearLog;
use App\Admin\Components\Actions\Gathers\ImportGather;
use App\Constants\GatherConstant;
use App\Models\Gather;
use App\Models\GatherCrontabLog;
use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GatherCrontabLogController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '定时采集日志';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new GatherCrontabLog());


        $css = <<<EOT
.modal-dialog{
    margin: calc((100vh - 600px)/2) auto;
}
.modal-content{
    height: 600px;
    overflow-y: scroll;
}
EOT;


        Admin::style($css);


        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableView();
        });
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new ClearLog());

        });
        $grid->showColumnSelector();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->disableIdFilter();
            $filter->expand();
            $filter->equal('gather_id', '采集规则')->select(Gather::where([
                'crontab_type' => GatherConstant::CRONTAB_EVERY_DAY
            ])->pluck('name', 'id'));


        });

        $grid->column('id', __('Id'));
        $grid->column('gather.name', __('采集名称'));
        $grid->column('status', __('采集状态'))->display(function () {
            if (!empty($this->error_log)) {
                return '出现错误';
            }

            /**
             * @var  Carbon $updateAt
             */

            $updateAt = $this->updated_at;
            if ($diffSeconds = $updateAt->diffInSeconds($this->created_at)) {
                $settingDiff = GatherConstant::CRONTAB_TIMEOUT - 10;
                if ($diffSeconds >= $settingDiff) {
                    return '超过设定时间终止';
                }
            }

            if (empty($this->end_time)) {
                return '采集中';
            }


            return '采集完成';
        })->label([
            '超过设定时间终止' => 'success',
            '出现错误'     => 'warning',
            '采集中'      => 'primary',
            '采集完成'     => 'success'
        ]);
        $grid->column('setting_content_amount', __('设定采集内容数量'));
        $grid->column('setting_url_amount', __('设定采集链接数量'));
        $grid->column('setting_interval_time', __('设定采集时间间隔'));
        $grid->column('setting_timeout_time', __('设定采集超时时间'));
        $grid->column('gather_url_amount', __('实际采集链接数量'));
        $grid->column('gather_content_amount', __('实际采集内容数量'));
        $grid->column('error_log', __('错误日志'))->display(function ($value) {
            return Str::limit($value, 20);
        })->modal('错误日志', function ($model) {

            if ($model->error_log) {
                $errorLog = str_replace('<br/>', ' ', $model->error_log);
                $logs     = explode("\n", $errorLog);

                $data = [];
                foreach ($logs as $item) {
                    $data[] = [$item];
                }
                return new Table(['error'], $data);
            }
        });

        $grid->column('gather_log', __('采集日志'))->display(function ($value) {
            return Str::limit($value, 20);
        })->modal('采集日志', function ($model) {

            if ($model->gather_log) {

                $gatherLog = str_replace('<br/>', ' ', $model->gather_log);
                $logs      = array_filter(explode("\n", $gatherLog));
                $data      = [];
                foreach ($logs as $item) {
                    $data[] = [$item];
                }
                return new Table(['gather'], $data);
            }

        });
        // $grid->column('gather_log', __('Gather log'));
        $grid->column('created_at', __('采集开始时间'));

        $grid->column('end_time', __('采集结束时间'));
        // $grid->column('updated_at', __('Updated at'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(GatherCrontabLog::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('gather_id', __('Gather id'));
        $show->field('setting_content_amount', __('Setting content amount'));
        $show->field('setting_url_amount', __('Setting url amount'));
        $show->field('setting_interval_time', __('Setting interval time'));
        $show->field('setting_timeout_time', __('Setting timeout time'));
        $show->field('gather_url_amount', __('Gather url amount'));
        $show->field('gather_content_amount', __('Gather content amount'));
        $show->field('error_log', __('Error log'));
        $show->field('gather_log', __('Gather log'));
        $show->field('end_time', __('End time'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new GatherCrontabLog());

        $form->number('gather_id', __('Gather id'));
        $form->number('setting_content_amount', __('Setting content amount'));
        $form->number('setting_url_amount', __('Setting url amount'));
        $form->number('setting_interval_time', __('Setting interval time'));
        $form->number('setting_timeout_time', __('Setting timeout time'));
        $form->number('gather_url_amount', __('Gather url amount'));
        $form->number('gather_content_amount', __('Gather content amount'));
        $form->textarea('error_log', __('Error log'));
        $form->textarea('gather_log', __('Gather log'));
        $form->datetime('end_time', __('End time'))->default(date('Y-m-d H:i:s'));

        return $form;
    }
}
