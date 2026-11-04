<?php

namespace Fayne\Event\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\Order;

class EventReservationCanceled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    private Event $event;

    private Order $order;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Event $event, Order $order)
    {
        $this->user = $user;
        $this->event = $event;
        $this->order = $order;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }
}
