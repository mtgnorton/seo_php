<?php

namespace App\Listeners;

use App\Events\WebsiteTemplateDeletingEvent;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

class WebsiteTemplateDeletingeventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(WebsiteTemplateDeletingEvent $event)
    {
        $websiteTemplate = $event->model;

        // 删除后, 对应website數據也要删除
        $websiteTemplate->website()->delete();
    }
}
