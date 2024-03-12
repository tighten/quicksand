<?php

namespace Tighten\Quicksand;

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
        if (empty($this->config->get('quicksand.days'))) {
            return new Collection;
        }
         
        $config = $this->config->get('quicksand.deletables') 
          ?? $this->config->get('quicksand.models');

        $deletables = collect($config);

        return $deletables->map(function ($itemConfig, $itemName) {
            $item = $this->getItemProperties($itemName, $itemConfig);

            if (! Schema::connection($item['connection'])->hasColumn($item['table'], 'deleted_at')) {
                throw new Exception("{$item['table']} does not have a 'deleted_at' column");
            }

            $affectedRows = DB::connection($item['connection'])->table($item['table'])
                ->where('deleted_at', '<', (new DateTime)->sub(new DateInterval("P{$item['daysBeforeDeletion']}D"))->format('Y-m-d H:i:s'))
                ->delete();

            return [$item['name'] => $affectedRows];
        });
    }

    private function getItemProperties($itemName, $itemConfig)
    {
        if (! is_array($itemConfig)) {
            $itemName = $itemConfig;
            $itemConfig = [];
        }

        return [
            'name' => $itemName,
            'table' => $this->getTableName($itemName),
            'connection' => $this->getConnectionName($itemName),
            'daysBeforeDeletion' => $this->getDaysBeforeDeletion($itemConfig),
        ];
    }

    private function getTableName($itemName)
    {
        if (is_subclass_of($itemName, 'Illuminate\Database\Eloquent\Model')) {
            return resolve($itemName)->getTable();
        }

        return $itemName;
    }

    private function getConnectionName($itemName)
    {
        if (is_subclass_of($itemName, 'Illuminate\Database\Eloquent\Model')) {
            return resolve($itemName)->getConnectionName();
        }

        return null;
    }

    private function getDaysBeforeDeletion($itemConfig)
    {
        return $itemConfig['days'] ?? $this->config->get('quicksand.days');
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
