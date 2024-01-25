<?php

namespace App\Entity;

use App\Utils\SocketUtil;
use App\Utils\TableUtil;
use Swoole\Table;

abstract class AbstractEntity {
    const TABLE = '';
    
    abstract public static function mainKey(): string;
    public static function getTable(): Table {
        return SocketUtil::contextServer()->{static::TABLE};
    }
    /** @return static */
    public static function fromModel(array $model) {
        $entity = new static();
        foreach ($model as $key => $value) {
            $entity->{$key} = $value;
        }
        return $entity;
    }
    
    public function updateValue(string $key, $value): bool {
        if (property_exists($this, $key)) {
            $this->{$key} = $value;
            $mainKeyValue = $this->{static::mainKey()};
            static::getTable()->set($mainKeyValue, [
                $key => $value
            ]);
            return true;
        }
        return false;
    }
    public function updateValues(array $values): bool {
        $fixedValues = [];
        foreach ($values as $key => $value) {
            if (property_exists($this, $key)) {
                $fixedValues[$key] = $value;
                $this->{$key} = $value;
            }
        }
        $mainKeyValue = $this->{static::mainKey()};
        static::getTable()->set($mainKeyValue, $fixedValues);
        return false;
    }
}