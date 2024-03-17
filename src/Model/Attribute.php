<?php

namespace Blrf\Orm\Model;

use JsonSerializable;
use ReflectionClass;

/**
 * Base class for Model/Field attributes
 */
abstract class Attribute implements JsonSerializable
{
    /**
     * Implement json_encode() with 'attrName' field.
     */
    public function jsonSerialize(): mixed
    {
        $ref = new ReflectionClass($this);
        $ret = [
            'attrName'  => get_class($this)
        ];
        foreach ($ref->getProperties() as $prop) {
            //if ($prop->isAccessible()) {
                $ret[$prop->getName()] = $prop->isInitialized($this) ? $prop->getValue($this) : null;
            //}
        }
        return $ret;
    }
}
