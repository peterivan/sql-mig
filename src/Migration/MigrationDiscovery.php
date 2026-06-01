<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Migration;

use PeterIvan\SqlMig\Exception\DuplicateMigrationVersion;

final class MigrationDiscovery
{
    public function __construct(
        private readonly VersionTokenParser $versionTokenParser,
        private readonly ChecksumCalculator $checksumCalculator,
    ) {}

    /**
     * @return MigrationFile[]
     */
    #[\NoDiscard('Discovered migrations must be validated or applied.')]
    public function discover(string $path): array
    {
        $files = glob(rtrim($path, '/') . '/*.sql') ?: [];
        $migrations = [];
        $pathsByVersion = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $version = $this->versionTokenParser->parseFilename($filename);
            $content = file_get_contents($file);

            if ($content === false) {
                $content = '';
            }

            $migrations[] = new MigrationFile(
                $file,
                $filename,
                $version,
                $content,
                $this->checksumCalculator->calculate($content),
            );
            $pathsByVersion[$version->value][] = $file;
        }

        foreach ($pathsByVersion as $version => $paths) {
            if (count($paths) > 1) {
                throw new DuplicateMigrationVersion($version, $paths);
            }
        }

        usort($migrations, static fn(MigrationFile $a, MigrationFile $b): int => $a->version->compare($b->version));

        return $migrations;
    }
}
