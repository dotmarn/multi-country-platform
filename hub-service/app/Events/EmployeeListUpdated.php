<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeListUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $country,
        public readonly string $action,
        public readonly array $employee,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("country.{$this->country}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'employee.list.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'country' => $this->country,
            'action' => $this->action,
            'employee' => $this->employee,
        ];
    }
}
