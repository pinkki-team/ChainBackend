<?php

namespace App\Entity;

abstract class AbstractEntity {
    /** @return static */
    public static function fromModel(array $model) {
        $entity = new static();
        foreach ($model as $key => $value) {
            $entity->{$key} = $value;
        }
        return $entity;
    }
}