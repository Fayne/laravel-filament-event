<?php

namespace Fayne\Event\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventTimeslot;
use Fayne\StoreLocator\Models\Store;

class EventReserved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;

    private Event $event;

    private Store|EventTimeslot|null $entity;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Event $event, Store|EventTimeslot|null $entity = null)
    {
        $this->user = $user;
        $this->event = $event;
        $this->entity = $entity;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * @return Store|EventTimeslot|null $entity
     */
    public function getEntity(): Store|EventTimeslot|null
    {
        return $this->entity;
    }
}
