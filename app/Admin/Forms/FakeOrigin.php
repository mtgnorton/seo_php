<?php


namespace App\Admin\Forms;

use App\Constants\FakeOriginConstants;
use App\Constants\SpiderConstant;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;

class FakeOrigin extends Base
{
    public function tabTitle()
    {
        return lp('伪原创设置');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->disableReset();
        $this->radio("type", ll('伪原创类型'))
            ->options(FakeOriginConstants::typeText())
            ->when(FakeOriginConstants::TYPE_5118, function (Form $form) {
                $form->text('5118_key', '5118 一键智能换词API Key');
            });
        $this->radio("article_image_type", ll('文章图片采集是否本地化'))
            ->options(FakeOriginConstants::articleImageText());

    }
}
