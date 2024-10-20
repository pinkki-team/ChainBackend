<?php

namespace App\Entity;

use App\Utils\SocketUtil;
use App\Utils\TableUtil;
use Swoole\Table;

abstract class AbstractEntity {
    const TABLE = '';
    const JSON_KEYS = [];

    abstract public static function mainKey(): string;
    public static function getTable(): Table {
        return SocketUtil::contextServer()->{static::TABLE};
    }
    /** @return static */
    public static function fromModel(array $model) {
        $entity = new static();
        foreach ($model as $key => $value) {
            if (in_array($key, static::JSON_KEYS)) $value = json_decode($value, true);
            $entity->{$key} = $value;
        }
        return $entity;
    }
    
    public function updateValue(string $key, $value): bool {
        if (property_exists($this, $key)) {
            $this->{$key} = $value;
            $mainKeyValue = $this->{static::mainKey()};
            $valuesUpdate = in_array($key, static::JSON_KEYS) ? json_encode($value) : $value;
            static::getTable()->set($mainKeyValue, [
                $key => $valuesUpdate
            ]);
            return true;
        }
        return false;
    }
    public function updateValues(array $values): bool {
        $fixedValues = [];
        foreach ($values as $key => $value) {
            if (property_exists($this, $key)) {
                $valuesUpdate = in_array($key, static::JSON_KEYS) ? json_encode($value) : $value;
                $fixedValues[$key] = $valuesUpdate;
                $this->{$key} = $value;
            }
        }
        $mainKeyValue = $this->{static::mainKey()};
        static::getTable()->set($mainKeyValue, $fixedValues);
        return false;
    }
}