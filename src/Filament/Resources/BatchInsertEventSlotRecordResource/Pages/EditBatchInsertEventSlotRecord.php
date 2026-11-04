<?php

namespace Fayne\Event\Filament\Resources\BatchInsertEventSlotRecordResource\Pages;

use Fayne\Event\Filament\Resources\BatchInsertEventSlotRecordResource;
use Fayne\CMS\Filament\Operations\EditRecord;

class EditBatchInsertEventSlotRecord extends EditRecord
{
    protected static string $resource = BatchInsertEventSlotRecordResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $detail = $data['detail'] ?? [];
        $period = json_decode($detail['from-till'] ?? '{}', true) ?: [];

        return [
            'event_id' => $detail['event'] ?? null,
            'store_ids' => $detail['store'] ?? [],
            'week' => $detail['week'] ?? [],
            'start_time' => $detail['start_time'] ?? null,
            'end_time' => $detail['end_time'] ?? null,
            'stock' => $detail['stock'] ?? null,
            'from' => $period['from'] ?? null,
            'till' => $period['till'] ?? null,
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return CreateBatchInsertEventSlotRecord::toRecordData($data, $this->record->admin_id);
    }
}
