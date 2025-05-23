<?php

namespace Esmp\Elastic;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Esmp\Elastic\Exception\ElasticSearchException;

class ElasticSearchOperations extends ElasticSearch
{

    public function __construct()
    {
        $elasticSearch = ElasticSearch::getInstance();
        if (!$elasticSearch || !$elasticSearch->isConnected()) {
            throw new ElasticSearchException('ElasticSearch client not connected');
        }

        $this->client = $elasticSearch->getClient();
    }

    /**
     * 插入单条数据.
     *
     * @param array $doc
     * @param $indexName
     * @return true
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function insertSingleIndex(array $doc, $indexName = null): bool
    {
        $params = [
            'index' => $indexName,
            'id' => $doc['id'],
            'body' => $doc,
        ];

        try {
            $response = $this->client->index($params);
        }catch (ElasticSearchException $e) {
            throw new ElasticSearchException('ElasticSearch insert single doc error: '. $e->getMessage());
        }

        return true;
    }

    /**
     * 更新数据.
     *
     * @param array $doc
     * @param $indexName
     * @return bool
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function updateSingleIndex(array $doc, $indexName = null): bool
    {
        $params = [
            'index' => $indexName,
            'id' => $doc['id'],
            'body' => [
                'doc' => $doc,
            ]
        ];

        $response = $this->client->update($params);

        if ($response->getStatusCode() !== 200) {
            throw new ElasticSearchException('ElasticSearch update single doc error');
        }

        return true;
    }

    /**
     * 删除数据.
     *
     * @param int $id
     * @param $index
     * @return true
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function deleteSingleIndex(int $id, $index): bool
    {
        $params = [
            'index' => $index,
            'id' => $id
        ];

        $response = $this->client->delete($params);
        if ($response->getStatusCode() !== 200) {
            throw new ElasticSearchException('ElasticSearch delete single doc error');
        }

        return true;
    }
}