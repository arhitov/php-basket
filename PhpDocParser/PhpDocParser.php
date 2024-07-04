<?php

namespace App\Supports\Property\Helpers;

use App\Supports\RectalReflection\Dto\DataPropertyType;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;

class PhpDocParser
{
    private const BUILT_IN_TYPES = [
        'int',
        'float',
        'string',
        'bool',
        'callable',
        'self',
        'parent',
        'array',
        'iterable',
        'object',
        'void',
        'mixed',
    ];

    public static function function(ReflectionMethod|ReflectionFunction $reflection): array
    {
        $doc = $reflection->getDocComment();
        $result = [];

        if ($doc) {
            preg_match_all('/@(param)\s+(?:(.*?)\s+)?\$(.*?)\n/i', $doc, $out);

            foreach ($out[2] as $idx => $type) {
                $result['param'][trim($out[3][$idx])] = [
                    'types' => self::makePropertyTypes(trim($type)),
                ];
            }
        }
        
        return $result;
    }

    public static function property(ReflectionProperty $reflection): ?array
    {
        $doc = $reflection->getDocComment();

        return ($doc && preg_match(
                '/@var\s+(.*?)\s+\$' . preg_quote($reflection->name) . '/i',
                $doc,
                $out
            ))
            ? self::makePropertyTypes(trim($out[1]))
            : null;
    }

    /**
     * @param string $name
     * @return \App\Supports\RectalReflection\Dto\DataPropertyType[]
     */
    private static function makePropertyTypes(string $name): array
    {
        $type = self::parseType($name);
        dump(
            $type
        );
        return new DataPropertyType(
            $type['name'] ?? null,
            $type['nullable'] ?? null,
            $type['isBuiltin'] ?? null,
            $type['className'] ?? null,
            $type['classShortName'] ?? null,
            $type['properties'] ?? null,
        );
    }

    private static function parseType(string $type): array
    {
        dump($type);
        if (empty($type)) {
            return [
                'name'           => 'mixed',
                'nullable'       => true,
                'isBuiltin'      => true,
                'className'      => null,
                'classShortName' => null,
                'properties'     => null,
            ];
        }
        $nullable = false;
        if (str_starts_with($type, '?')) {
            $type = substr($type, 1);
            $nullable = true;
        }

        if (in_array('null', $typeExp = explode('|', $type))) {
            $nullable = true;
            $type = implode(
                '|',
                array_filter(
                    $typeExp,
                    fn(string $t) => 'null' !== $t,
                )
            );
        }

        $isBuiltin = self::isBuiltin($type);
        if ($isBuiltin) {
            return [
                'name'           => $type,
                'nullable'       => $nullable,
                'isBuiltin'      => true,
                'className'      => null,
                'classShortName' => null,
                'properties'     => null,
            ];
        } elseif (class_exists($type)) {

        } else {

        }
        dd(
            ReflectionNamedType::class,
            $type,
        );
//        $isBuiltin = class
//        return [
//            'name' => ,
//            'nullable' => ,
//            'isBuiltin' => $isBuiltin,
//            'className' => ,
//            'classShortName' => ,
//            'properties' => ,
//        ];
    }

    private static function isBuiltin(string $type): bool
    {
        return in_array($type, self::BUILT_IN_TYPES);
    }
}