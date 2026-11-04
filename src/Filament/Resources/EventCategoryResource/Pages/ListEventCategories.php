<?php

namespace Fayne\Event\Filament\Resources\EventCategoryResource\Pages;

use Fayne\Event\Filament\Resources\EventCategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListEventCategories extends ListRecords
{
    protected static string $resource = EventCategoryResource::class;
}
