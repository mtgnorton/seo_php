<?php

namespace App\Events;

use App\Models\Template;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TemplateDeletingEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 模块页面对象
     *
     * @var [type]
     */
    public $model;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Template $model)
    {
        $this->model = $model;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('template-deleting');
    }
}
