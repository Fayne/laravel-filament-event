<?php

namespace Fayne\Event\Filament\Resources\EventCategoryResource\Pages;

use Fayne\Event\Filament\Resources\EventCategoryResource;
use Fayne\CMS\Filament\Operations\CreateRecord;
use Fayne\CMS\Support\ContentLocale;

class CreateEventCategory extends CreateRecord
{
    protected static string $resource = EventCategoryResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['locale'] = ContentLocale::default();
        $data['fallback_id'] = null;

        return $data;
    }
}
