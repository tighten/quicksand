<?php

namespace Tightenco\Quicksand;

use DateInterval;
use DateTime;
use Exception;
use Illuminate\Config\Repository as Config;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeleteOldSoftDeletes extends Command
{
    protected $signature = 'quicksand:run';

    protected $description = 'Force delete all soft deleted content older than X days';

    private $config;

    public function __construct(Config $config)
    {
        parent::__construct();

        $this->config = $config;
    }

    public function handle()
    {
        $deletedRows = $this->deleteOldSoftDeletes();

        if ($this->config->get('quicksand.log', false)) {
            $this->logAffectedRows($deletedRows);
        }
    }

    private function deleteOldSoftDeletes()
    {
        $models = collect($this->config->get('quicksand.models'));
        $pivotTables = collect($this->config->get('quicksand.pivot_tables'));
        $daysBeforeDeletion = $this->config->get('quicksand.days');

        if (empty($daysBeforeDeletion)) {
            return new Collection;
        }

        $deletedModels = $this->deleteModels($models);
        $deletedPivots = $this->deletePivots($pivotTables);

        return $deletedModels->merge($deletedPivots);
    }

    private function deleteModels($models)
    {
        $daysBeforeDeletion = $this->config->get('quicksand.days');

        return $models->map(function ($modelConfig, $modelName) use ($daysBeforeDeletion) {
            if (! is_array($modelConfig)) {
                $modelName = $modelConfig;
                $modelConfig = [];
            }

            if (! method_exists($modelName, 'bootSoftDeletes')) {
                throw new Exception("{$modelName} does not have SoftDeletes enabled");
            }

            return $this->deleteOldSoftDeletesForModel($modelName, $modelConfig, $daysBeforeDeletion);
        })->values();
    }

    private function deletePivots($pivots)
    {
        $daysBeforeDeletion = $this->config->get('quicksand.days');

        return $pivots->map(function ($pivotConfig, $pivotTable) use ($daysBeforeDeletion) {
            if (! is_array($pivotConfig)) {
                $pivotTable = $pivotConfig;
                $pivotConfig = [];
            }

            if (! Schema::hasColumn($pivotTable, 'deleted_at')) {
                throw new Exception("{$pivotTable} does not have a 'deleted_at' column");
            }

            return $this->deleteOldSoftDeletesForPivotTable($pivotTable, $pivotConfig, $daysBeforeDeletion);
        })->values();
    }

    private function deleteOldSoftDeletesForModel($modelName, $modelConfig, $daysBeforeDeletion)
    {
        $daysBeforeDeletion = $modelConfig['days'] ?? $daysBeforeDeletion;

        $affectedRows = $modelName::onlyTrashed()
            ->where('deleted_at', '<', (new DateTime)->sub(new DateInterval("P{$daysBeforeDeletion}D"))->format('Y-m-d H:i:s'))
            ->forceDelete();

        return [$modelName => $affectedRows];
    }

    private function deleteOldSoftDeletesForPivotTable($table, $tableConfig, $daysBeforeDeletion)
    {
        $daysBeforeDeletion = $tableConfig['days'] ?? $daysBeforeDeletion;

        $affectedRows = DB::table($table)->where('deleted_at', '<', (new DateTime)->sub(new DateInterval("P{$daysBeforeDeletion}D"))->format('Y-m-d H:i:s'))
            ->delete();

        return [$table => $affectedRows];
    }

    private function logAffectedRows(Collection $deletedRows)
    {
        $preparedRows = $this->prepareForLogging($deletedRows);

        if (! $this->config->get('quicksand.log', false) || empty($preparedRows)) {
            return;
        }

        if (! $this->config->has('logging.channels.quicksand')) {
            $this->config->set([
                'logging.channels.quicksand' => [
                    'driver' => 'stack',
                    'level' => 'info',
                    'channels' => ['single'],
                ],
            ]);
        }

        Log::channel('quicksand')->info(sprintf(
            '%s force deleted these number of rows: %s',
            get_class($this),
            print_r($preparedRows, true)
        ));
    }

    private function prepareForLogging($rawDeletedRows)
    {
        return $rawDeletedRows->reduce(function ($carry, $modelAndNumDeleted) {
            foreach ($modelAndNumDeleted as $model => $numDeleted) {
                if ($numDeleted === 0) {
                    continue;
                }
                $carry[$model] = $numDeleted;
            }

            return $carry;
        }, []);
    }
}
