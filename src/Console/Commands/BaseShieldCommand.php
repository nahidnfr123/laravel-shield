<?php

namespace NahidFerdous\Shield\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

abstract class BaseShieldCommand extends Command
{
    protected function userClass(): string
    {
        return resolveAuthenticatableClass();
    }

    protected function openUrl(string $url): bool
    {
        $command = match (PHP_OS_FAMILY) {
            'Darwin' => ['open', $url],
            'Windows' => ['cmd', '/c', 'start', '', $url],
            default => ['xdg-open', $url],
        };

        try {
            $process = new Process($command);
            $process->setTimeout(3);
            $process->disableOutput();
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
