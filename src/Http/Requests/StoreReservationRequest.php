<?php

namespace Fayne\Event\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Fayne\Event\Enums\EventStockType;
use Fayne\Event\Models\Event;

class StoreReservationRequest extends FormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        /** @var Event|null $event */
        $event = $this->route('event');

        $stockType = $event?->stock_type;
        $user = $this->user(config('filament-event.api.guard'));
        $isGuestBooking = $event?->booking_auth_mode === 'guest' || $user === null;
        $requiresCustomerInfo = $isGuestBooking || $event?->booking_auth_mode === 'authenticated';

        return [
            'quantity' => ['required', 'integer', 'min:1'],
            'store_id' => [
                Rule::requiredIf($stockType === EventStockType::Store),
                'integer',
            ],
            'timeslot_id' => [
                Rule::requiredIf($stockType === EventStockType::Timeslot),
                'string',
            ],
            'first_name' => [
                Rule::requiredIf($requiresCustomerInfo),
                'string',
                'max:255',
            ],
            'last_name' => [
                Rule::requiredIf($requiresCustomerInfo),
                'string',
                'max:255',
            ],
            'gender' => ['nullable', 'string', 'max:50'],
            'area_code' => [
                Rule::requiredIf($requiresCustomerInfo),
                'string',
                'max:10',
            ],
            'phone_number' => [
                Rule::requiredIf($requiresCustomerInfo),
                'string',
                'max:50',
            ],
        ];
    }
}
