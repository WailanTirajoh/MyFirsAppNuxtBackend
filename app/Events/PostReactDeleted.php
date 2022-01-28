<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostReactDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $post;
    private $react;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($post, $react)
    {
        $this->post = $post;
        $this->react = $react;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('post.' . $this->post->id);
    }

    public function broadcastAs()
    {
        return 'react.deleted';
    }

    /**
     * send data to be broadcasted with
     */
    public function broadcastWith()
    {
        return [
            // 'post' => $this->post,
            'react' => $this->react,
        ];
    }
}