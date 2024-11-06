<?php

declare(strict_types=1);

namespace App\Model\Database\Entity;

abstract class BaseEntity
{
    /**
     * __clone
     * Warning: entity collections set to null!
     *
     * @return void
     */
    public function __clone()
    {
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);
            if ($value instanceof \Doctrine\Common\Collections\Collection) {
                $property->setValue($this, null);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(bool $autoInitialize = true, int $depth = 0, int $maxDepth = 3): array
    {
        $depth++;

        if ($depth > 100) {
            throw new \InvalidArgumentException("{$this->getEntityName()}::toArray Maximum depth exceeded. Depth = {$depth}");
        }

        $errorMsg = '';
        if (!is_int($depth) || $depth < 0) {
            $errorMsg .= " depth = {$depth}";
        }
        if (!is_int($maxDepth) || $maxDepth < 0) {
            $errorMsg .= ($errorMsg ? ', ' : '') . " maxDepth = {$maxDepth}";
        }
        if ($errorMsg != '') {
            throw new \InvalidArgumentException("{$this->getEntityName()}::toArray Incorrect value {$errorMsg}");
        }

        $result = [];
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, 'get') === 0) {
                $key = lcfirst(substr($method, 3));

                try {
                    $value = $this->$method();
                } catch (\Exception $e) {
                    $value = null;
                }
                if (is_object($value) && method_exists($value, 'toArray')) {
                    if (
                        ($maxDepth > 0 && $depth >= $maxDepth)
                        || (!$autoInitialize && $value instanceof \Doctrine\Persistence\Proxy && !$value->__isInitialized())
                    ) {
                        $result[$key] = null;
                        continue;
                    }
                    if ($maxDepth > 0 && $depth > $maxDepth) {
                        throw new \InvalidArgumentException("{$this->getEntityName()}::toArray Incorrect depth depth:$depth > maxDepth:$maxDepth ");
                    } else {
                        $result[$key] = $value->toArray($autoInitialize, $depth, $maxDepth);
                    }
                } else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    public function getEntityName(): string
    {
        $reflector = new \ReflectionClass($this);
        return $reflector->getShortName();
    }
}
