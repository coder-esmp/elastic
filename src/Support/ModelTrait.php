<?php

namespace Esmp\Elastic\Support;

trait ModelTrait
{
    /**
     * 检测模型是否存在.
     *
     * @param string $modelName
     * @return false|void
     */
    public function checkModelIsset(string $modelName)
    {
        if (!str_starts_with($modelName, 'app\\')) {
            $modelName = 'app\\' . str_replace('/', '\\', $modelName);
        }
        $modelName = str_replace(['/', 'App'], ['\\', 'app'], $modelName);
        if (!class_exists($modelName)) {
            return false;
        }

        return $modelName;
    }

    /**
     * 获取模型属性.
     *
     * @param string $propertyName
     * @return false|mixed
     */
    public function getModelProperty(string $modelName,string $propertyName)
    {
        try {
            $reflectionClass = new \ReflectionClass($modelName);
            if (!$reflectionClass->hasProperty($propertyName)) {
                return false;
            }
            $property = $reflectionClass->getProperty($propertyName);
            $property->setAccessible(true);
        } catch (\ReflectionException $e) {
            return false;
        }
        
        return $property->getValue();
    }
}