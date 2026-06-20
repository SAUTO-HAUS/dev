<?php

namespace App\Services\Parsing;

use App\Services\Parsing\Adapters\EncarAdapter;
use App\Services\Parsing\Adapters\EcarsTradeAdapter;
use App\Services\Parsing\Adapters\OpenLaneAdapter;

class AdapterFactory
{
    public const SOURCES = [
        'encar' => EncarAdapter::class,
        'ecarstrade' => EcarsTradeAdapter::class,
        'openlane' => OpenLaneAdapter::class,
    ];

    public static function create(string $sourceCode): ?AdapterInterface
    {
        $sourceCode = strtolower($sourceCode);
        if (!isset(self::SOURCES[$sourceCode])) {
            return null;
        }
        $class = self::SOURCES[$sourceCode];
        return new $class();
    }

    public static function detectFromUrl(string $url): ?AdapterInterface
    {
        foreach (self::SOURCES as $code => $class) {
            $adapter = new $class();
            if ($adapter->detectsUrl($url)) {
                return $adapter;
            }
        }
        return null;
    }

    public static function getAvailableSources(): array
    {
        $list = [];
        foreach (self::SOURCES as $code => $class) {
            $adapter = new $class();
            $list[$code] = $adapter->getSourceName();
        }
        return $list;
    }
}
