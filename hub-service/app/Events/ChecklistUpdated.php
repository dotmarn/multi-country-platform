<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChecklistUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $country,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("checklist.{$this->country}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'checklist.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'country' => $this->country,
            'message' => "Checklist data has been updated for {$this->country}.",
        ];
    }
}
