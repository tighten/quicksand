<?php

namespace Quicksand;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunForceDeletePolicy extends Command
{
    protected $signature = 'quicksand:run-force-delete-policy';

    protected $description = 'Force delete all soft deleted content older than X days';

    public function handle()
    {
        $forceDeletedRows = $this->forceDeleteModels()
            ->reject(function ($numRowsDeleted) {
                return $numRowsDeleted === 0;
            })
            ->all();

        if (! empty(config('quicksand.log'))) {
            Log::info(sprintf(
                '%s force deleted these number of rows: %s',
                get_class($this),
                print_r($forceDeletedRows, true)
            ));
        }

        return $forceDeletedRows;
    }

    private function forceDeleteModels()
    {
        $models = collect(config('quicksand.models'));
        $days = config('quicksand.days');

        return $models->map(function ($modelConfig, $model) use ($days) {
            $softDeleteDays = isset($modelConfig['days']) ? $modelConfig['days'] : $days;

            return DB::table($model::getTableName())
                ->where('deleted_at', '<', Carbon::today()->subDays($softDeleteDays))
                ->delete();
        });
    }
}
