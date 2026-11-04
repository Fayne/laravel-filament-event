<?php

namespace Fayne\Event\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'user_id' => $this->user_id,
            'store_id' => $this->store_id,
            'timeslot_id' => $this->timeslot_id,
            'quantity' => (int) $this->quantity,
            'status' => $this->status?->value ?? (string) $this->status,
            'check_in_code' => $this->check_in_code,
            'check_in_at' => $this->check_in_at,
            'cancelled_at' => $this->cancelled_at,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'area_code' => $this->area_code,
            'phone_number' => $this->phone_number,
            'created_at' => $this->created_at,
        ];
    }
}
