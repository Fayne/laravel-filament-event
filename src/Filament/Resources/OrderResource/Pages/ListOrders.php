<?php

namespace Fayne\Event\Filament\Resources\OrderResource\Pages;

use Fayne\Event\Filament\Resources\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
