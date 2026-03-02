<?php

namespace App\Events;

use App\Enums\EventTypesEnum;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeListUpdated implements ShouldBroadcastNow
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
        return EventTypesEnum::EMPLOYEE_LIST_UPDATED->value;
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
