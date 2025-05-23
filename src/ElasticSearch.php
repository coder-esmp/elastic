<?php

namespace Esmp\Elastic;

use Elastic\Elasticsearch\ClientBuilder;
use Esmp\Elastic\Exception\ElasticSearchException;
use Throwable;


class ElasticSearch
{

    /**
     * @var ElasticSearch|null
     */
    private static ?ElasticSearch $instance = null;

    /**
     * @var object|null
     */
    protected ?object $client = null;

    private function __construct()
    {
        $config = $this->getElasticSearchConfig();
        if (!$this->validateConfig($config)) {
            throw new ElasticSearchException('elasticSearch config error, Missing configuration parameters');
        }

        try {
            $this->client = ClientBuilder::create()
                ->setHosts([$config['elastic_host']])
                ->setBasicAuthentication($config['elastic_username'], $config['elastic_password'])
                ->build();
        } catch (Throwable $e) {
            throw new ElasticSearchException('elasticSearch error, ' . $e->getMessage());
        }
    }

    /**
     * 获取 ElasticSearch 配置.
     *
     * @return mixed
     */
    private function getElasticSearchConfig(): mixed
    {
        return config('plugin.esmp.elastic.elastic');
    }

    /**
     * 验证配置是否正确.
     *
     * @param array $config
     * @return bool
     */
    private function validateConfig(array $config): bool
    {
        return !empty($config) && !empty($config['elastic_username']) && !empty($config['elastic_password']) && !empty($config['elastic_host']);
    }


    /**
     * 返回 ElasticSearch 实例.
     *
     * 注意：该方法返回的是一个 ElasticSearch 实例，
     * @return ElasticSearch|null
     */
    public static function getInstance(): ?ElasticSearch
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 检查是否已连接到 Elasticsearch 服务器.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->client !== null;
    }

    /**
     * 返回客户端句柄.
     *
     * 注意：该方法返回的是一个 Elasticsearch 客户端对象
     * @return object|null
     */
    public function getClient(): object|null
    {
        return $this->client;
    }


    private function __clone() {}
}
