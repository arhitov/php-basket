<?php

namespace App\Services;

use App\Contracts\SerializeServiceInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use ValueError;

class SerializeCustomService implements SerializeServiceInterface
{
    /** @inheritDoc */
    public function serialize(mixed $data): string
    {
        return json_encode(self::convertToArray($data), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @inheritDoc
     * @throws ReflectionException
     */
    public function unserialize(string $json): mixed
    {
        return json_validate($json)
            ? self::convertFromArray(json_decode($json, true))
            : throw new ValueError('Строка должна быть в формате JSON');
    }

    /**
     * @param $data
     * @return array
     */
    private static function convertToArray($data): array
    {
        if (is_object($data)) {

//            if ($data instanceof \Illuminate\Support\Carbon) {
            if ($data instanceof \Carbon\Carbon) {
                return [
                    'type'       => 'instance',
                    'class'      => get_class($data),
                    'properties' => [
                        $data->toString(),
                    ],
                ];
            }

            $reflection = new ReflectionClass($data);
            $properties = $reflection->getProperties();

            $result = [
                'type'       => 'object',
                'class'      => get_class($data),
                'properties' => [],
            ];

            foreach ($properties as $property) {
                // Делаем свойство доступным для чтения. Для PHP <8.1
                // $property->setAccessible(true);
                $value = $property->getValue($data);
                $result['properties'][$property->getName()] = [
                    'visibility' => self::getPropertyVisibility($property),
                    'value'      => self::convertToArray($value),
                ];
            }

            return $result;
        } elseif (is_array($data)) {
            $result = [
                'type'  => 'array',
                'items' => [],
            ];

            foreach ($data as $key => $value) {
                $result['items'][$key] = self::convertToArray($value);
            }

            return $result;
        } elseif (is_resource($data)) {
            throw new ValueError(sprintf(
                'Ресурсы не поддерживаются! Ресурс: [%s] %s',
                gettype($data),
                get_class($data),
            ));
        } else {
            return [
                'type'  => gettype($data),
                'value' => $data,
            ];
        }
    }

    /**
     * @param ReflectionProperty $property
     * @return string
     */
    private static function getPropertyVisibility(ReflectionProperty $property): string
    {
        return match (true) {
            $property->isPrivate()   => 'private',
            $property->isProtected() => 'protected',
            default                  => 'public',
        };
    }

    /**
     * @param array $data
     * @return array|mixed|object
     * @throws ReflectionException
     */
    private static function convertFromArray(array $data): mixed
    {
        if ($data['type'] === 'object') {
            $className = $data['class'];
            $reflection = new ReflectionClass($className);
            $object = $reflection->newInstanceWithoutConstructor();

            foreach ($data['properties'] as $name => $propertyData) {
                $property = $reflection->getProperty($name);
                // Делаем свойство доступным для чтения. Для PHP <8.1
                // $property->setAccessible(true);
                $property->setValue($object, self::convertFromArray($propertyData['value']));
            }

            return $object;
        } elseif ($data['type'] === 'instance') {
            $className = $data['class'];
            return new $className(...$data['properties']);
        } elseif ($data['type'] === 'array') {
            $result = [];
            foreach ($data['items'] as $key => $value) {
                $result[$key] = self::convertFromArray($value);
            }
            return $result;
        } else {
            return $data['value'];
        }
    }
}
