<?php

namespace App\Admin\Controllers;

use App\Admin\Components\Actions\Gathers\Copy;
use App\Admin\Components\Actions\Gathers\DispatchGather;
use App\Admin\Components\Actions\Gathers\ExportGather;
use App\Admin\Components\Actions\Gathers\ImportGather;
use App\Admin\Components\Actions\Gathers\TestRegularContent;
use App\Admin\Components\Actions\Gathers\TestRegularUrl;
use App\Constants\ContentConstant;
use App\Constants\GatherConstant;

use App\Models\ContentCategory;
use App\Models\Gather;
use App\Models\Category;
use App\Services\Gather\CrawlService;
use App\Services\GatherService;
use Encore\Admin\Admin;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Callout;
use Ichynul\RowTable\TableRow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use PHPUnit\TextUI\XmlConfiguration\Constant;

class GatherController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Gather manage';

    public function __construct()
    {
        $this->title = ll('Gather manage');
        // $this->icon = ('/asset/imgs/icon/1.png');
    }


    public function index(Content $content)
    {
        $grid = $this->grid();

        $titleHeader = <<<EOT
        <div class="box-header with-border">
            <div class="conmon-icon-title">
            <img class="title-icon" src="$this->icon" class="default" alt="">
            <label class="title-word">$this->title</label>
        </div>
EOT;

        if (is_object($grid)) {
            $grid = $grid->render();
        }
        $grid = str_replace('<div class="box-header with-border">', $titleHeader, $grid);

        $css = <<<EOT
.content-header{
display:none;
}
EOT;

        \Encore\Admin\Facades\Admin::style($css);
        return $content
            ->title($this->title())
            ->row(new Callout("
                采集有两种方式:<br/>
                1.手动采集: 通过手型图标采集,会有时间限制,最长10分钟<br/>
                2.后台采集: 配置好定时任务的相关参数,右键点击立即后台采集,后台采集最长时间为1小时<br/>
                请注意当有多个采集规则需要定时任务时,尽量错开定时采集时间,避免同一时间进行多个采集任务
            ", "注意事项", 'info'))
            ->body($grid);
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        // <button type="reset" class="btn btn-warning stop-gather">停止采集<\/button>
        /*当关闭modal框时重载*/
        $js = <<<EOT

function getIframeHtml(name,src){

return  `<div>
		 <div class="btn-group pull-left" style="margin-bottom: 8px">

         <\/div>
         <div class="btn-group pull-right">
         <span>采集名称:
         <b class="gather-name">\${name}<\/b>
         <\/span>
         <\/div>
         <\/div>
         <iframe src="\${src}" width="100%" height="620px">
         <\/iframe>`
}


$('.grid-modal .stop-gather').bind('click',function(){
  $.post("/admin/gathers/stop",{},function(result){
    console.log(result)
  });
})

$('.modal-content').click(function (event){
    if($(event.target).attr('aria-hidden')){

    }else{
        event.stopPropagation();
    }
})

$('.grid-modal').click( function (){
    modal_id = $(this).attr('id')
    src  = $(this).find('iframe').attr('src')
     gatherName = $(this).find('.gather-name').text()
    $("#"+modal_id+" .custom-width-height").empty().append(getIframeHtml(gatherName,src))
})



EOT;

        Admin::script($js);

        $css = <<<EOT
#swal2-content * { max-width: 100%}
#app-admin-components-actions-gathers-importgather .modal-dialog {
width:700px;

}
EOT;


        Admin::style($css);


        $grid = new Grid(new Gather());


        $grid->model()->collection(function (Collection $collection) {

            $collection->load(['contentCategory.group:name,id', 'contentCategory.category:name,id']);


            foreach ($collection as $item) {

                $item->category_name = data_get($item, 'contentCategory.category.name') . '-' . data_get($item, 'contentCategory.group.name') . '-' . data_get($item, 'contentCategory.name');

            }

            return $collection;
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(new ImportGather());

        });
        $grid->actions(function ($actions) {
            $actions->add(new TestRegularUrl());
            $actions->add(new TestRegularContent());
            $actions->add(new DispatchGather());
            $actions->add(new ExportGather());
            $actions->add(new Copy());

        });
        $grid->column('id', ll('Id'));
        $grid->column('name', ll('Name'));
        $grid->column('category_id', ll('Category'))->display(function ($value) {


            if ($this->category_name == '--') {
                return '<span style="color:#c7254e;">暂无分类,请重新设置</span>';
            }


            $link = sprintf('/admin/files?category_id=%s&type=%s', data_get($this, 'contentCategory.id'), $this->storage_type);

            return "<a href='$link'>$this->category_name </a>";
        });
        $grid->column('tag', ll('Tag'));
        $grid->column('type', ll('Type'))->using(GatherConstant::typeText())->label([
            GatherConstant::TYPE_TITLE    => 'default',
            ContentConstant::TYPE_ARTICLE => 'primary',
            GatherConstant::TYPE_SENTENCE => 'info',
            GatherConstant::TYPE_IMAGE    => 'warning',
        ]);
        $grid->column('begin_url', ll('Begin url'))->display(function ($value) {
            return "<code>{$value}</code>";
        });
        $grid->column('grab', ll('采集'))->customModal('采集(采集过程中不要关闭该窗口)', function ($model) {

            return self::getIframeHtml('/admin/gathers/run/' . $model->id, $model->name);


        }, 'fa-hand-grab-o', '600px', '700px');

        $grid->column('created_at', ll('Created at'));
        $grid->column('updated_at', ll('Updated at'));

        $grid = $grid->render();

        $grid = str_replace('<div class="box grid-box">', '<div class="box grid-box content_form_default">', $grid);

        return $grid;
        // return $grid;
    }


    /**
     * author: mtg
     * time: 2021/6/16   14:58
     * function description:停止采集
     */
    public function stop()
    {


        Cache::put('stop_gather', 1, 10);
        return Response::json([
            'status' => 1
        ]);
    }

    static private function getIframeHtml($src, $name)
    {

        // <button type="reset" class="btn btn-warning stop-gather">停止采集</button>
        $iframe = <<<EOT


<div>
<div class="btn-group pull-left" style="margin-bottom: 8px">

        </div>


         <div class="btn-group pull-right">
                <span>采集名称:<b class="gather-name">{$name}</b></span>
        </div>
</div>



 <iframe src="{$src}" width="100%" height="620px">

</iframe>
EOT;
        return $iframe;
    }


    /**
     * author: mtg
     * time: 2021/6/16   12:17
     * function description:启动采集 get和post
     * @return string
     */
    public function run()
    {
        set_time_limit(0);
        $script = <<<EOT
    <script>

    function LA() {}

    window.parent.$(function () {//使用window.parent调用父级jquery
        var head = document.getElementsByTagName("head").item(0);
        var linkList = window.parent.document.getElementsByTagName("link");//获取父窗口link标签对象列表
        for (var i = 0; i < linkList.length; i++) {
            var _link = document.createElement("link");
            _link.rel = 'stylesheet'
            _link.type = 'text/css';
            _link.href = linkList[i].href;
            head.appendChild(_link);
        }

        var scriptList = window.parent.document.getElementsByTagName("script");//获取父窗口script标签对象列表
        for (var i = 0; i < scriptList.length; i++) {
            var _script = document.createElement("script");
            _script.type = 'text/javascript';
            _script.src = scriptList[i].src;
            head.appendChild(_script);
        }
    });
</script>
EOT;

        if (request()->isMethod('GET')) {


            $css = <<<EOT
<style>
.box-header{
display:none;
}

body > .box{
    border: none;
    box-shadow: none;
}
.btn-primary {
    background-color: #3E4AF5;
    border-color: #3E4AF5;
}
.btn-primary:hover,.btn-primary:active,.btn-primary.hover {
    background-color: #3E4AF5
}
.btn{ border: none; }

.btn:active {
    -webkit-box-shadow: none;
    -moz-box-shadow: none;
    box-shadow: none;
}
.btn-primary.active.focus, .btn-primary.active:focus,
.btn-primary.active:hover, .btn-primary:active.focus,
.btn-primary:active:focus, .btn-primary:active:hover{
    color: #fff;
    background-color: #3E4AF5;
    border-color: #3E4AF5;
}
.btn-primary.focus,.btn-primary:focus {
    color: #fff;
    background-color: #3E4AF5;
    border-color: #3E4AF5;
}
.btn-default { background: #99A1AA; color: #FFFFFF; border-color: #99A1AA }
.btn-default:hover, .btn-default:active, .btn-default.hover{ background: #99A1AA; color: #FFFFFF; border-color: #99A1AA; }

.btn-default.active.focus,.btn-default.active:focus,
.btn-default.active:hover,.btn-default:active.focus,
.btn-default:active:focus,.btn-default:active:hover {
    color: #fff;
    background-color: #99A1AA;
    border-color: #99A1AA
}
.fields-group label{
    height: 40px;
    line-height: 40px;
    font-weight: 600;
}
.fields-group .input-group{
    width: 100%;
}
.fields-group .input-group input{
    height: 50px;
    line-height: 50px;
    border-radius: 10px !important;
    width: 100% !important;
}
.box-footer .col-md-8 {
    text-align: center;
}
.box-footer .col-md-8 .btn-group:nth-child(1){
    margin-right: 20px;
}
.box-footer .col-md-8 .pull-left{
    float: inherit !important;
}
</style>
EOT;

            $form = new Form(new Gather());

            $model                      = Gather::find(request()->id);
            $gatherRequestAmountDefault = 10;

            switch ($model->type) {
                case GatherConstant::TYPE_IMAGE:
                    $gatherAmountDefault = 20;
                    break;
                case GatherConstant::TYPE_ARTICLE:
                    $gatherAmountDefault = 50;
                    break;
                case GatherConstant::TYPE_TITLE:
                    $gatherAmountDefault = 50;
                    break;
                case GatherConstant::TYPE_SENTENCE:
                    $gatherAmountDefault = 5000;
                    break;
            }

            $form->number("gather_amount", '采集内容数量')->default($gatherAmountDefault);
            $form->number("gather_request_amount", '采集请求数量')->default($gatherRequestAmountDefault)->help('采集内容数量或采集请求数量有一个满足要求将停止采集');
            $form->number("gather_request_time_interval", '采集请求间隔时间(单位秒)')->default(1)->help('两次请求之间的间隔时间,时间过短可能被封禁ip');
            $form->number("gather_request_time", '请求超时时间')->default(5)->help('当一个请求超时该时间仍未成功,将放弃');

            $form->tools(function (Form\Tools $tools) {
                // 去掉`列表`按钮
                $tools->disableList();
            });
            $form->footer(function ($footer) {


                // 去掉`查看`checkbox
                $footer->disableViewCheck();

                // 去掉`继续编辑`checkbox
                $footer->disableEditingCheck();

                // 去掉`继续创建`checkbox
                $footer->disableCreatingCheck();

            });


            $form->setAction('/admin/gathers/run/' . request()->id);


            return $script . $css . $form->render();
        }

        $css = <<<EOT
<style>
body {
height: 620px;
padding: 30px
}

</style>
EOT;

        header('X-Accel-Buffering: no');

        $gatherAmount              = request()->input('gather_amount');
        $gatherRequestAmount       = request()->input('gather_request_amount');
        $gatherRequestTimeInterval = request()->input('gather_request_time_interval');
        $maxRequestTime            = request()->input('gather_request_time');

        force_response($css . $script);
        $id = request()->id;

        $concrete = new GatherService();
        Cache::delete('stop_gather');

        $concrete->dynamic(Gather::find($id), $gatherAmount, $gatherRequestAmount, $gatherRequestTimeInterval, $maxRequestTime);


    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $js = <<<EOT
        $('.box-tools').addClass('custom-tools')
        $('#app>.content-header').append($('.box-tools'));
EOT;

        \Admin::script($js);
        $show = new Show(gather::findOrFail($id));

        $show->field('id', ll('Id'));
        $show->field('name', ll('Name'));
        $show->field('category_id', ll('Category id'));
        $show->field('tag', ll('Tag'));
        $show->field('day_max_limit', ll('Day max limit'));
        $show->field('type', ll('Type'));
        $show->field('user_agent', ll('User agent'));
        $show->field('is_auto', ll('Is auto'));
        $show->field('begin_url', ll('Begin url'));
        $show->field('regular_url', ll('Regular url'));
        $show->field('test_url', ll('Test url'));
        $show->field('regular_content', ll('Regular content'));
        $show->field('filter_length_limit', ll('Filter length limit'));
        $show->field('filter_regular', ll('Filter regular'));
        $show->field('filter_content', ll('Filter content'));
        $show->field('created_at', ll('Created at'));
        $show->field('updated_at', ll('Updated at'));
        // return $show;

        $show = $show->render();
        $show = str_replace('<div class="box box-info">', '<div class="box box-info content_show_default">', $show);
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {


        Admin::style('
        .cascade-group input{
        height: 40px;
        }
        ');
        $form = new Form(new Gather());


        $form->text('name', ll('Name'))->required();


//        $form->radio('user_agent_type', ll('User agent 类型'))
//            ->options(GatherConstant::userAgentText())
//            ->when(GatherConstant::USER_AGENT_BAIDU, function (Form $form) {
//                $form->textarea('user_agent')->value(GatherConstant::USER_AGENT_BAIDU_CONTENT);
//            })
//            ->when(GatherConstant::USER_AGENT_GOOGLE, function (Form $form) {
//                $form->textarea('user_agent')->value(GatherConstant::USER_AGENT_GOOGLE_CONTENT);
//            })
//            ->when(GatherConstant::USER_AGENT_DEFAULT, function (Form $form) {
//                $form->textarea('user_agent')->value(GatherConstant::USER_AGENT_DEFAULT_CONTENT);
//            })
//            ->when(GatherConstant::USER_AGENT_CUSTOM, function (Form $form) {
//                $form->textarea('user_agent')->value(GatherConstant::USER_AGENT_CUSTOM_CONTENT);
//            });

        $help = sprintf("百度user-agent为:   %s<br>谷歌user-agent为:   %s<br>", GatherConstant::USER_AGENT_BAIDU_CONTENT, GatherConstant::USER_AGENT_GOOGLE_CONTENT);
        $form->textarea('user_agent')->value(GatherConstant::USER_AGENT_DEFAULT_CONTENT)->help($help);


//        $form->switch('is_auto', ll('Is auto'));
        $form->text('begin_url', ll('Begin url'))
            ->required()
            ->help("使用<code>page=[0-1000]</code>会将page=0,page=1...page=1000url放入待采集池");


        $form->url('agent', '代理')->help('代理类似于http://127.0.0.1:1080');

        $form->textarea('header', 'header头')->help('一行一条,部分网站可能需要手动添加header头,否则无法采集,如网易新闻,header头的key前面不要加:');


        /**
         * author: mtg
         * time: 2021/9/7   15:44
         * function description:根据采集类型动态变化
         * @param $type
         * @return \Closure
         */
        $confirmCategory = function ($type) {
            return function (Form $form) use ($type) {

                switch ($type) {
                    case GatherConstant::TYPE_ARTICLE:
                        $storageType = ContentConstant::storageTypeText(ContentConstant::TYPE_ARTICLE);
                        break;
                    case GatherConstant::TYPE_TITLE:
                        $storageType = ContentConstant::storageTypeText(ContentConstant::TYPE_TITLE, ContentConstant::TYPE_SENTENCE);
                        break;
                    case GatherConstant::TYPE_SENTENCE:
                        $storageType = ContentConstant::storageTypeText(ContentConstant::TYPE_TITLE, ContentConstant::TYPE_SENTENCE);
                        break;
                    case GatherConstant::TYPE_IMAGE:
                        $storageType = ContentConstant::storageTypeText(ContentConstant::TYPE_IMAGE);
                        break;
                }

                $fun = function ($type) {

                    return function (Form $form) use ($type) {
                        $form->select('category_id', ll('Category'))
                            ->options(
                                ContentCategory
                                    ::with(['group:name,id', 'category:name,id'])
                                    ->where('type', $type)
                                    ->where('parent_id', '<>', 0)
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        return [
                                            $item['id'] => data_get($item, 'category.name') . '-' . data_get($item, 'group.name') . '-' . $item['name']
                                        ];
                                    })
                            );

                    };
                };


                $form->select('storage_type', ll('Storage type'))->options($storageType)->rules('required')
                    ->when(ContentConstant::TYPE_SENTENCE, $fun(ContentConstant::TYPE_SENTENCE))
                    ->when(ContentConstant::TYPE_TITLE, $fun(ContentConstant::TYPE_TITLE))
                    ->when(ContentConstant::TYPE_ARTICLE, $fun(ContentConstant::TYPE_ARTICLE))
                    ->when(ContentConstant::TYPE_IMAGE, $fun(ContentConstant::TYPE_IMAGE))
                    ->when(0, function () {
                    });


                $form->display('tag', ll('Tag'))->disable()->help('保存后自动生成');

            };
        };

        $js = <<<EOT
$("label[for='category_id']").addClass('asterisk')
EOT;

        Admin::script($js); //默认的必选项有bug,改为这种方式
        $help = <<<EOT
匹配网址表达式可以有多个,一行一条,满足条件的网址都将被抓取,只匹配href里面的网址内容 <br>
以*号开头和结尾的匹配网址将不会在同一采集规则下重复请求<br>
匹配使用php正则表达式 <br>
当匹配所有所有链接时,使用<code>.*?</code> <br>
当不匹配所有链接时,直接为空,使用api类型的接口 <br>
需要转义的字符: <code>. \ + * ? [ ^ ] $ ( ) { } = ! < > | : -</code><br>
php正则教程: https://wiki.jikexueyuan.com/project/php/regular-expression.html
EOT;

        $form->textarea('regular_url', ll('Regular url'))
            ->default('')
            ->help($help)
            ->required();
        $form->select('type', ll('Gather Type'))
            ->options(GatherConstant::typeText())
            ->required()
            /*确定分类*/

            ->when(GatherConstant::TYPE_SENTENCE, $confirmCategory(ContentConstant::TYPE_SENTENCE))
            ->when(GatherConstant::TYPE_TITLE, $confirmCategory(ContentConstant::TYPE_TITLE))
            ->when(GatherConstant::TYPE_ARTICLE, $confirmCategory(ContentConstant::TYPE_ARTICLE))
            ->when(GatherConstant::TYPE_IMAGE, $confirmCategory(ContentConstant::TYPE_IMAGE))
            ->when(0, function () {
            })
            ->when(
                'in',
                [
                    GatherConstant::TYPE_SENTENCE,

                ],
                function (Form $form) {
                    $form->checkbox("delimiter", '分隔符号')
                        ->options(GatherConstant::delimiterText())
                        ->help('用于句子或标题,将段落分隔');
                }
            )
            ->when(
                'in',
                [
                    GatherConstant::TYPE_ARTICLE

                ],
                function (Form $form) {
//                    $form->switch('is_filter_url', '是否过滤网址');
                    $form->textarea('regular_title', '匹配标题')->help('一行一个,和匹配内容根据顺序一一对应,如果匹配内容正则有多行,而匹配标题正则只有一个,则默认使用该标题正则')->rules('required');
                }
            )
            ->when(
                'in',
                [
                    GatherConstant::TYPE_SENTENCE,
                    GatherConstant::TYPE_ARTICLE,
                    GatherConstant::TYPE_TITLE,
                ],
                function (Form $form) {

                    $help = <<<EOT
一行一个关键词,内容包含关键词才会被抓取
EOT;

                    $form->textarea("keywords", "关键词包含")->help($help);

                })
            ->when(
                'in',
                [
                    GatherConstant::TYPE_SENTENCE,
                    GatherConstant::TYPE_ARTICLE,

                ],
                function (Form $form) {


                    $form->switch("is_open_fake_origin", "是否开启伪原创")->help('开启该选项前,请先到伪原创配置中进行配置,<a href="/admin/gather-fake-origin">点击配置</a>');

                });


        $imagePattern = htmlspecialchars('<img.*?src\="([^"]*?)"');
        $help         = <<<EOT
匹配内容表达式可以有多个,一行一条,满足条件的内容都将被抓取<br>
匹配使用php正则表达式<br>
当为图片时,为匹配图片链接的正则,传统html可以使用<code>$imagePattern</code><br>
在线测试内容匹配网址<a href="https://tool.oschina.net/regex/" target="_blank">https://tool.oschina.net/regex/</a>
EOT;

        $form->textarea('regular_content', ll('Regular content'))
            ->help($help)
            ->default('')
            ->rules('required');


        $form->url('test_url', ll('Test url'))
            ->default('')
            ->help('测试网址用于测试匹配内容');


        $form->number('filter_length_limit', ll('Filter length limit'))
            ->default(0)
            ->help('为0不限制<br>一个中文字符为3个长度单位,如<code>你好</code>的长度为6')->rules('min:0');


//                    $form->textarea('filter_regular', ll('Filter regular'))
//                        ->help($help)
//                        ->default('');
        $help = <<<EOT
内容过滤短语可以有多个,一行一条,默认只过滤短语<br>
如果想过滤掉包含短语的句子,文章等则短语两边加*号,如<code>*你好*</code>
EOT;

        $form->textarea('filter_content', ll('Filter content'))
            ->help($help)
            ->default('');


        $form->radio('crontab_type', ll('定时任务'))
            ->options(GatherConstant::crontabText())
            ->when(GatherConstant::CRONTAB_EVERY_DAY, function (Form $form) {
                $form->rowtable('定时任务设定', function ($table) {

                    $table->row(function ($row) {
                        $row->number('crontab_hour', '小时')->rules('min:0|max:23')->help('取值范围为:0-23,如果设定3小时22分,则表示每天的3点22分运行该次采集')->default(function () {
                            return mt_rand(0, 23);
                        });
                        $row->number('crontab_minute', '分钟')->rules('max:59')->default(function () {
                            return mt_rand(0, 59);
                        })->help('取值范围为:0-59');
                    });

                    $table->row(function ($row) {
                        $row->number('crontab_setting_content_amount', '采集内容数量')->rules('min:0')->default(50);
                        $row->number('crontab_setting_url_amount', '采集链接数量')->rules('min:0')->default(50)->help('采集内容数量或采集请求数量有一个满足要求将停止采集');
                        $row->text('crontab_setting_interval_time', '采集时间间隔')->rules('min:0')->default(1)->help('两次请求之间的间隔时间,时间过短可能被封禁ip');
                        $row->number('crontab_setting_timeout_time', '采集超时时间')->rules('min:0')->default(5)->help('当一个请求超时该时间仍未成功,将放弃');
                    });
                })->useDiv(true);

            })->default(GatherConstant::CRONTAB_NO);


        $form->saving(function (Form $form) {
            if (!$form->type) {
                return back_error('请选择采集类型');
            }
            if (!$form->category_id) {
                return back_error('请选择分类');
            }
            if ($form->is_open_crontab) {

                try {
                    form_validate(request()->all(), [
                        'crontab_hour'                   => 'min:0|max:23',
                        'crontab_minute'                 => 'min:0|max:59',
                        'crontab_setting_content_amount' => 'min:1',
                        'crontab_setting_url_amount'     => 'min:1',
                        'crontab_setting_interval_time'  => 'numeric|min:0.01',
                        'crontab_setting_timeout_time'   => 'min:1',
                    ]);
                } catch (\Exception $e) {

                    return back_error($e->getMessage());
                }
            }

            $form->tag = ContentCategory::find($form->category_id)->name . ContentConstant::tagText()[$form->storage_type];
        });

        return $form;
    }


    public function export()
    {
        $id = request()->id;

        $content = base64_encode(Gather::find($id)->toJson());

        $filename = Carbon::now()->format('Y-m-d-H-i-s-') . Str::random(6) . '.gather';
        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename);
    }
}
