<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeDataChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $employeeId,
        public readonly array $changedFields,
        public readonly array $employee,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("employee.{$this->employeeId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'employee.data.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'changed_fields' => $this->changedFields,
            'employee' => $this->employee,
        ];
    }
}
