<?php

namespace Lalog;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\DB;

class QueryLogger
{
    private FilesystemManager $filesystem;
    private array $config;

    public function __construct(FilesystemManager $filesystem, array $config)
    {
        $this->filesystem = $filesystem;
        $this->config = $config;
    }

    public function listen(): void
    {
        $disk = $this->getDisk();
        $fileName = $this->resolveFileName($disk);

        if ($this->config['clear_on_start']) {
            $this->clearCurrentFile($disk);
        }

        $disk->append($fileName, $this->config['separator_start']);

        DB::listen(function ($query) use ($disk, $fileName) {
            $sql = $this->formatQuery($query);
            $disk->append($fileName, $sql);
        });
    }

    protected function getDisk(): Filesystem
    {
        return $this->filesystem->disk($this->config['disk']);
    }

    protected function resolveFileName($disk): string
    {
        $directory = $this->config['directory'];
        $date = date($this->config['date_format']);
        $baseName = str_replace('{date}', $date, $this->config['format']);
        $extension = $this->config['extension'];
        $maxSize = $this->config['max_size'];

        $nameFix = "$directory/$baseName";
        $name = "$nameFix.$extension";
        $index = 0;

        while ($disk->exists($name) && $disk->size($name) >= $maxSize) {
            $index++;
            $name = "$nameFix-$index.$extension";
        }

        return $name;
    }

    protected function clearCurrentFile($disk): void
    {
        $directory = $this->config['directory'];
        $date = date($this->config['date_format']);
        $baseName = str_replace('{date}', $date, $this->config['format']);
        $extension = $this->config['extension'];

        $filePath = "$directory/$baseName.$extension";

        if ($disk->exists($filePath)) {
            $disk->delete($filePath);
        }
    }

    protected function formatQuery($query): string
    {
        $bindings = array_map([$this, 'formatBinding'], $query->bindings);

        $boundSql = $this->interpolateQuery($query->sql, $bindings);

        $sql = 'Date: ' . date('Y-m-d H:i:s') . "\n";
        $sql .= "Time query: $query->time(ms)\n";
        $sql .= "$boundSql;\n";
        $sql .= $this->config['separator_end'] . "\n";

        return $sql;
    }

    protected function formatBinding(mixed $binding): string
    {
        if (is_null($binding)) {
            return 'NULL';
        }

        if (is_bool($binding)) {
            return $binding ? 'TRUE' : 'FALSE';
        }

        if (is_int($binding) || is_float($binding)) {
            return (string) $binding;
        }

        if (is_array($binding)) {
            return "'" . json_encode($binding) . "'";
        }

        if (is_object($binding)) {
            if (method_exists($binding, 'format')) {
                return "'" . $binding->format('Y-m-d H:i:s') . "'";
            }

            if (method_exists($binding, '__toString')) {
                return "'" . (string) $binding . "'";
            }

            return "'" . get_class($binding) . "'";
        }

        return "'" . (string) $binding . "'";
    }

    protected function interpolateQuery(string $sql, array $bindings): string
    {
        if (empty($bindings)) {
            return $sql;
        }

        $escaped = str_replace(['%'], ['%%'], $sql);

        $placeholderCount = substr_count($escaped, '?');

        if ($placeholderCount === 0 || $placeholderCount !== count($bindings)) {
            $bindingStr = implode(', ', $bindings);

            return "$sql /* bindings: [$bindingStr] */";
        }

        $escaped = str_replace(['?'], ['%s'], $escaped);

        return vsprintf($escaped, $bindings);
    }
}
