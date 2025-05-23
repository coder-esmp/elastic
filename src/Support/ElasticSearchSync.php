<?php

namespace Esmp\Elastic\Support;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Esmp\Elastic\ElasticSearchOperations;
use Esmp\Elastic\Exception\ElasticSearchException;
use Exception;
use Illuminate\Database\Eloquent\Model;
use support\Log;
use Throwable;

trait ElasticSearchSync
{

    /**
     * @return void
     * @throws ElasticSearchException
     */
    protected static function bootElasticSearchSync(): void
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;
        $index = static::$elasticIndex;
        $fields = static::$searchable;

        static::created(static function (Model $model) use ($index, $fields) {
            $modelArray = $model->toArray();
            $selectedData = array_intersect_key($modelArray, array_flip($fields));
            $client = new ElasticSearchOperations();
            try {
                $client->insertSingleIndex($selectedData, $index);
            } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
                throw new ElasticSearchException('ElasticSearch insert single doc error: ' . $e->getMessage());
            }
        });

        static::updated(static function (Model $model) use ($index, $fields) {
            $dirtyFields = $model->getDirty();
            foreach ($dirtyFields as $field => $value) {
                if (in_array($field, $fields, true)) {
                    try {
                        $modelArray = $model->toArray();
                        $selectedData = array_intersect_key($modelArray, array_flip($fields));
                        $client = new ElasticSearchOperations();
                        $client->updateSingleIndex($selectedData, $index);
                    } catch (Throwable $e) {
                        throw new ElasticSearchException('ElasticSearch update single doc error: ' . $e->getMessage());
                    }
                }
            }
        });

        static::deleted(static function (Model $model) use ($index) {
            $id = $model->id;
            $client = new ElasticSearchOperations();

            try {
                $client->deleteSingleIndex($id, $index);
            } catch (Exception $e) {
                // 记录详细的错误日志
                Log::error('ElasticSearch delete single doc error', [
                    'model_id' => $id,
                    'index' => $index,
                    'error_message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]);
            }
        });
    }
}