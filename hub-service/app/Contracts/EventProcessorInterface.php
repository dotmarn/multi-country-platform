<?php

namespace App\Contracts;

interface EventProcessorInterface
{
    /**
     * Handle an incoming event from RabbitMQ.
     */
    public function handle(array $eventData): void;

    /**
     * Get the event type this processor handles.
     */
    public function getEventType(): string;
}
