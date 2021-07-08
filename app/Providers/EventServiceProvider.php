<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'App\Events\PageDeletingEvent' => [
            'App\Listeners\PageDeletingeventListener'
        ],
        'App\Events\MaterialDeletingEvent' => [
            'App\Listeners\MaterialDeletingeventListener'
        ],
        'App\Events\ModuleDeletingEvent' => [
            'App\Listeners\ModuleDeletingeventListener'
        ],
        'App\Events\FileDeletingEvent' => [
            'App\Listeners\FileDeletingeventListener'
        ],
        'App\Events\WebsiteTemplateDeletingEvent' => [
            'App\Listeners\WebsiteTemplateDeletingeventListener'
        ],
        'App\Events\TemplateDeletingEvent' => [
            'App\Listeners\TemplateDeletingeventListener'
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
