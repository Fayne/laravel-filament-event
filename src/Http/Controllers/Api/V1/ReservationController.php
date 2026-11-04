<?php

namespace Fayne\Event\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fayne\Event\Exceptions\InsufficientStockException;
use Fayne\Event\Http\Requests\CheckInRequest;
use Fayne\Event\Http\Requests\StoreReservationRequest;
use Fayne\Event\Http\Resources\OrderResource;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\Order;
use Fayne\Event\Services\ReservationService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Auth\Access\AuthorizationException;

class ReservationController extends Controller
{
    /**
     * @param StoreReservationRequest $request
     * @param Event $event
     * @param ReservationService $service
     * @return JsonResponse
     */
    public function store(StoreReservationRequest $request, Event $event, ReservationService $service)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if ($event->booking_auth_mode === 'authenticated' && ! $user) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if ($event->booking_auth_mode === 'guest' && $user) {
            $user = null;
        }

        try {
            $order = $service->reserve($event, $user, $request->validated());

            return (new OrderResource($order))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function meIndex(Request $request)
    {
        $userId = $request->user()->getAuthIdentifier();

        $query = Order::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        return OrderResource::collection(
            $query->paginate((int) $request->input('per_page', 15))
        );
    }

    /**
     * @param Request $request
     * @param Order $order
     * @return JsonResponse|OrderResource
     */
    public function meShow(Request $request, Order $order)
    {
        if ((string) $order->user_id !== (string) $request->user()->getAuthIdentifier()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return new OrderResource($order);
    }

    /**
     * @param Request $request
     * @param Order $order
     * @param ReservationService $service
     * @return JsonResponse|OrderResource
     */
    public function cancel(Request $request, Order $order, ReservationService $service)
    {
        if ((string) $order->user_id !== (string) $request->user()->getAuthIdentifier()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $order = $service->cancel($order, $request->user());

            return new OrderResource($order);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param CheckInRequest $request
     * @param ReservationService $service
     * @return JsonResponse|OrderResource
     */
    public function checkIn(CheckInRequest $request, ReservationService $service)
    {
        try {
            $order = $service->checkInByCode($request->validated('check_in_code'), $request->user());

            return new OrderResource($order);
        } catch (AuthorizationException) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
