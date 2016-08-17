<?php

namespace Tightenco\Quicksand;

use Carbon\Carbon;
use Exception;
use Illuminate\Config\Repository as Config;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
        $deletedRows = $this->deleteOldSoftDeletes()
            ->flatten(1)
            ->reject(function ($numRowsDeleted) {
                return $numRowsDeleted === 0;
            });

        $this->logAffectedRows($deletedRows);
    }

    private function deleteOldSoftDeletes()
    {
        $models = collect($this->config->get('quicksand.models'));
        $daysBeforeDeletion = $this->config->get('quicksand.days');

        if (empty($daysBeforeDeletion)) {
            return new Collection;
        }

        return $models->map(function ($modelConfig, $modelName) use ($daysBeforeDeletion) {
            if (! is_array($modelConfig)) {
                $modelName = $modelConfig;
                $modelConfig = [];
            }

            if (! method_exists($modelName, 'bootSoftDeletes')) {
                throw new Exception("$modelName does not have SoftDeletes enabled");
            }

            return $this->deleteOldSoftDeletesForModel($modelName, $modelConfig, $daysBeforeDeletion);
        })->values();
    }

    private function deleteOldSoftDeletesForModel($modelName, $modelConfig, $daysBeforeDeletion)
    {
        $daysBeforeDeletion = empty($modelConfig['days']) ? $daysBeforeDeletion : $modelConfig['days'];

        $affectedRows = $modelName::onlyTrashed()
            ->where('deleted_at', '<', Carbon::today()->subDays($daysBeforeDeletion))
            ->forceDelete();

        return [$modelName => $affectedRows];
    }

    private function logAffectedRows(Collection $deletedRows)
    {
        if (! $this->config->get('quicksand.log', false) || $deletedRows->isEmpty()) {
            return;
        }

        Log::info(sprintf(
            '%s force deleted these number of rows: %s',
            get_class($this),
            print_r($deletedRows->all(), true)
        ));
    }
}
