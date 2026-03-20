<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;

class BackgroundArtisan
{
    public function run(array $arguments, ?string $logFile = null, ?string $pidFile = null): void
    {
        $logFile ??= storage_path('logs/background-artisan.log');
        $pidFile ??= $this->defaultPidFile($logFile);

        if (! is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        if (! is_dir(dirname($pidFile))) {
            mkdir(dirname($pidFile), 0755, true);
        }

        $artisanPath = base_path('artisan');
        $parts = array_map(static fn ($part) => escapeshellarg((string) $part), $arguments);
        $phpBinary = $this->resolvePhpBinary();

        $command = sprintf(
            'cd %s && nohup %s %s %s >> %s 2>&1 & echo $! > %s',
            escapeshellarg(base_path()),
            escapeshellarg($phpBinary),
            escapeshellarg($artisanPath),
            implode(' ', $parts),
            escapeshellarg($logFile),
            escapeshellarg($pidFile)
        );

        exec('/bin/bash -lc ' . escapeshellarg($command));
    }

    public function terminate(?string $pidFile, array $commandFragments = []): bool
    {
        $terminated = false;

        if ($pidFile && is_file($pidFile)) {
            $pid = trim((string) @file_get_contents($pidFile));
            if ($pid !== '' && ctype_digit($pid)) {
                $terminated = $this->terminatePid((int) $pid) || $terminated;
            }
            @unlink($pidFile);
        }

        foreach ($commandFragments as $fragment) {
            $fragment = trim((string) $fragment);
            if ($fragment === '') {
                continue;
            }

            $pids = [];
            exec('pgrep -f ' . escapeshellarg($fragment), $pids);
            foreach ($pids as $pid) {
                $pid = trim((string) $pid);
                if ($pid !== '' && ctype_digit($pid)) {
                    $terminated = $this->terminatePid((int) $pid) || $terminated;
                }
            }
        }

        return $terminated;
    }

    protected function resolvePhpBinary(): string
    {
        $candidates = array_filter([
            defined('PHP_BINARY') ? PHP_BINARY : null,
            (new PhpExecutableFinder())->find(false),
            '/usr/bin/php',
            '/usr/bin/php8.1',
        ]);

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_executable($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('No se pudo resolver el binario de PHP para lanzar procesos en segundo plano.');
    }

    protected function defaultPidFile(string $logFile): string
    {
        return preg_replace('/\.log$/', '.pid', $logFile) ?: ($logFile . '.pid');
    }

    protected function terminatePid(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        exec('/bin/bash -lc ' . escapeshellarg("kill -TERM {$pid} >/dev/null 2>&1 || true"));
        usleep(400000);
        exec('/bin/bash -lc ' . escapeshellarg("kill -0 {$pid} >/dev/null 2>&1"), $noop, $status);

        if ($status === 0) {
            exec('/bin/bash -lc ' . escapeshellarg("kill -KILL {$pid} >/dev/null 2>&1 || true"));
            usleep(200000);
            exec('/bin/bash -lc ' . escapeshellarg("kill -0 {$pid} >/dev/null 2>&1"), $noopAfterKill, $statusAfterKill);

            return $statusAfterKill !== 0;
        }

        return true;
    }
}
