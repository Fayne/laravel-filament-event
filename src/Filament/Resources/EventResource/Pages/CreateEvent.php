<?php

namespace Fayne\Event\Filament\Resources\EventResource\Pages;

use Fayne\Event\Filament\Resources\EventResource;
use Fayne\CMS\Filament\Operations\CreateRecord;
use Fayne\CMS\Support\ContentLocale;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['locale'] = ContentLocale::default();
        $data['fallback_id'] = null;

        return $data;
    }
}
