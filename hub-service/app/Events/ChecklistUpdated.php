<?php

namespace App\Events;

use App\Enums\EventTypesEnum;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChecklistUpdated implements ShouldBroadcastNow
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
        return EventTypesEnum::CHECKLIST_UPDATED->value;
    }

    public function broadcastWith(): array
    {
        return [
            'country' => $this->country,
            'message' => "Checklist data has been updated for {$this->country}.",
        ];
    }
}
