<?php

namespace Tightenco\Quicksand;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteOldSoftDeletes extends Command
{
    protected $signature = 'quicksand:run';

    protected $description = 'Force delete all soft deleted content older than X days';

    public function handle()
    {
        $deletedRows = $this->deleteOldSoftDeletes()
            ->flatten(1)
            ->reject(function ($numRowsDeleted) {
                return $numRowsDeleted === 0;
            });

        $this->logAffectedRows($deletedRows);
    }

    private function deleteOldSoftDeletes()
    {
        $models = collect(config('quicksand.models'));
        $daysBeforeDeletion = config('quicksand.days');

        if (empty($daysBeforeDeletion)) {
            return new Collection;
        }

        return $models->map(function ($modelConfig, $modelName) use ($daysBeforeDeletion) {
            if (! is_array($modelConfig)) {
                $modelName = $modelConfig;
                $modelConfig = [];
            }

            return $this->deleteOldSoftDeletesForModel($modelName, $modelConfig, $daysBeforeDeletion);
        })->values();
    }

    private function deleteOldSoftDeletesForModel($modelName, $modelConfig, $daysBeforeDeletion)
    {
        $daysBeforeDeletion = empty($modelConfig['days']) ? $daysBeforeDeletion : $modelConfig['days'];

        return [$modelName => DB::table((new $modelName)::getTableName())
            ->where('deleted_at', '<', Carbon::today()->subDays($daysBeforeDeletion))
            ->delete()];
    }

    private function logAffectedRows(Collection $deletedRows)
    {
        if (! config('quicksand.log', false) || $deletedRows->isEmpty()) {
            return;
        }

        Log::info(sprintf(
            '%s force deleted these number of rows: %s',
            get_class($this),
            print_r($deletedRows->all(), true)
        ));
    }
}
