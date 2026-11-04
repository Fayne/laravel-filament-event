<?php

namespace Fayne\Event\Filament\Resources\BatchInsertEventSlotRecordResource\Pages;

use Fayne\Event\Filament\Resources\BatchInsertEventSlotRecordResource;
use Fayne\CMS\Filament\Operations\CreateRecord;

class CreateBatchInsertEventSlotRecord extends CreateRecord
{
    protected static string $resource = BatchInsertEventSlotRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::toRecordData($data);
    }

    public static function toRecordData(array $data, ?int $adminId = null): array
    {
        return [
            'admin_id' => $adminId ?? auth('admin')->id(),
            'detail' => [
                'event' => $data['event_id'], 'store' => $data['store_ids'], 'week' => $data['week'],
                'start_time' => $data['start_time'], 'end_time' => $data['end_time'], 'stock' => $data['stock'],
                'from-till' => json_encode(['from' => $data['from'], 'till' => $data['till']], JSON_THROW_ON_ERROR),
            ],
        ];
    }
}
