<?php

namespace Fayne\Event\Observers;

use Fayne\Event\Models\BatchInsertEventSlotRecord;

class BatchInsertEventSlotRecordObserver
{
    public function saved(BatchInsertEventSlotRecord $batchInsertRecord): void
    {
        $startDate = $batchInsertRecord->from;
        $endDate = $batchInsertRecord->till;
        $batchInsertRecord->records()->where('date', '>', $endDate)->delete();
        $batchInsertRecord->records()->where('date', '<', $startDate)->delete();
        $batchInsertRecord->records()->whereNotIn('store_id', $batchInsertRecord->store)->delete();
        $batchInsertRecord->records()->whereNot('event_id', $batchInsertRecord->event)->delete();

        foreach ($batchInsertRecord->store as $store) {
            $period = new \DatePeriod(
                new \DateTime(sprintf('%s 00:00:00', $startDate)),
                new \DateInterval('P1D'),
                new \DateTime(sprintf('%s 23:59:59', $endDate)),
            );

            foreach ($period as $date) {
                if ($this->isInWeek($date, $batchInsertRecord->week)) {
                    $timeslot = $batchInsertRecord->records()
                        ->whereDate('date', $date->format('Y-m-d'))
                        ->where('store_id', $store)
                        ->where('event_id', $batchInsertRecord->event)
                        ->first()
                        ?? $batchInsertRecord->records()->make([
                            'date' => $date->format('Y-m-d'),
                            'store_id' => $store,
                            'event_id' => $batchInsertRecord->event,
                        ]);
                    $timeslot->fill([
                        'start_time' => $batchInsertRecord->start_time,
                        'end_time' => $batchInsertRecord->end_time,
                        'stock' => $batchInsertRecord->stock,
                    ])->save();
                } else {
                    $batchInsertRecord->records()->whereDate('date', $date->format('Y-m-d'))->delete();
                }
            }
        }
    }

    private function isInWeek(\DateTimeInterface $date, array $week): bool
    {
        return in_array($date->format('w'), array_map('strval', $week), true);
    }
}
