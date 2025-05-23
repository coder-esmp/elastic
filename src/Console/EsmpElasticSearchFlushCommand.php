<?php

namespace Esmp\Elastic\Console;

use Esmp\Elastic\ElasticSearchOperations;
use Esmp\Elastic\Support\ModelTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EsmpElasticSearchFlushCommand extends Command
{
    use ModelTrait;

    protected static $defaultName = 'esmp-elastic:flush';
    
    protected static $defaultDescription = '清空指定模型的索引';

    protected string $elasticIndexName = 'elasticIndex';
    
    protected function configure()
    {
        $this->addArgument('model',InputArgument::REQUIRED,'model name');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $model = $input->getArgument('model');
        $modelName = $this->checkModelIsset($model);
        if($modelName === false){
            $output->writeln(sprintf('模型%s不存在',$model));
            return self::FAILURE;
        }

        $indexName = $this->getModelProperty($modelName,$this->elasticIndexName);
        if($indexName === false){
            $output->writeln(sprintf('模型 %s 缺少属性',$modelName));
            return self::FAILURE;
        }

        try {
            $params = ['index' => $indexName];
            $client = new ElasticSearchOperations();
            $client = $client->getClient();
            $client->indices()->delete($params);
        }catch (\Exception $e){
            throw new \RuntimeException($e->getMessage());
        }

        return self::SUCCESS;
    }
}