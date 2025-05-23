<?php

namespace Esmp\Elastic\Console;

set_time_limit(0);

use Esmp\Elastic\ElasticSearchOperations;
use Esmp\Elastic\Support\ModelTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EsmpElasticSearchIndexCommand extends Command
{
    use ModelTrait;


    /**
     * @var string
     */
    protected static $defaultName = 'esmp-elastic:index';

    /**
     * @var string
     */
    protected static $defaultDescription = '创建elastic 索引';

    /**
     * @var string
     */
    protected string $elasticIndexName = 'elasticIndex';

    /**
     * @var string
     */
    protected string $searchable = 'searchable';

    /**
     * 模型.
     *
     * @var string
     */
    protected string $modelName;

    /**
     * doc映射字段
     *
     * @var array
     */
    protected array $searchAbleArr;

    /**
     * 索引.
     *
     * @var string
     */
    protected string $indexName;

    protected function configure()
    {
        $this->addArgument('model', InputArgument::REQUIRED, 'model name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $modelName = $input->getArgument('model');

        $modelName = $this->checkModelIsset($modelName);
        if ($modelName === false) {
            $output->writeln(sprintf('模型%s 不存在'));
            return self::FAILURE;
        }

        $this->modelName = $modelName;
        $createIndex = $this->createDocsIndex();
        if (!$createIndex) {
            $output->writeln(sprintf('模型 %s 索引 %s 创建失败', $this->modelName, $this->indexName));

            return self::FAILURE;
        }

        $this->IndexsDocs();

        return self::SUCCESS;
    }


    /**
     * 创建文档索引.
     *
     * @return bool
     */
    protected function createDocsIndex()
    {
        $indexName = $this->getModelProperty($this->modelName, $this->elasticIndexName);
        if ($indexName === false) {
            throw new \RuntimeException('获取文档索引属性失败');
        }
        $this->indexName = $indexName;
        $clientOperation = new ElasticSearchOperations();
        $client = $clientOperation->getClient();
        try {
            $params = [
                'index' => $this->indexName,
            ];
            $response = $client->indices()->create($params);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'resource_already_exists_exception')) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * 索引文档.
     *
     * @return true
     */
    protected function IndexsDocs()
    {
        $status = true;

        $page = 0;
        $size = 500;

        try {
            $client = new ElasticSearchOperations();
            $client = $client->getClient();
            $model = new $this->modelName;
            $searchAbleArr = $this->getModelProperty($this->modelName, $this->searchable);
            if ($searchAbleArr === false) {
                throw new \RuntimeException('获取模型映射失败');
            }
            $this->searchAbleArr = $searchAbleArr;

            while ($status) {
                $offset = ($page - 1) * $size;
                $data = $model->select($this->searchAbleArr)->limit($size)->offset($offset)->get()->toArray();

                if (empty($data)) {
                    $status = false;
                    break;
                }

                ++$page;

                $params = ['body' => []];
                foreach ($data as $item) {
                    $params['body'][] = [
                        'index' => [
                            '_index' => $this->indexName,
                            '_id' => $item['id'],
                        ]
                    ];

                    $params['body'][] = $item;

                    $client->bulk($params);
                }
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return true;
    }
}