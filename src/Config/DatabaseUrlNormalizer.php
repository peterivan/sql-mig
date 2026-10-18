<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Config;

final class DatabaseUrlNormalizer
{
    private const DOCTRINE_ONLY_QUERY_PARAMETERS = [
        'charset' => true,
        'serverversion' => true,
    ];

    #[\NoDiscard('Normalized database URL must be passed to psql.')]
    public function normalize(string $databaseUrl): string
    {
        $normalized = preg_replace('/^pdo-pgsql:/i', 'postgresql:', $databaseUrl, 1) ?? $databaseUrl;
        $queryOffset = strpos($normalized, '?');

        if ($queryOffset === false) {
            return $normalized;
        }

        $baseUrl = substr($normalized, 0, $queryOffset);
        $query = substr($normalized, $queryOffset + 1);
        $keptParameters = [];

        foreach (explode('&', $query) as $parameter) {
            if ($parameter === '') {
                continue;
            }

            [$key] = explode('=', $parameter, 2);
            $key = rawurldecode(strtolower($key));
            if (isset(self::DOCTRINE_ONLY_QUERY_PARAMETERS[$key])) {
                continue;
            }

            $keptParameters[] = $parameter;
        }

        if ($keptParameters === []) {
            return $baseUrl;
        }

        return $baseUrl . '?' . implode('&', $keptParameters);
    }
}
