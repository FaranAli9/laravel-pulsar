<?php

use Faran\Pulsar\Pulsar;

it('reports the single-sourced version and responds to ping', function () {
    $binary = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'pulsar';

    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($binary).' --version 2>&1', $versionOutput, $versionExitCode);
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($binary).' ping 2>&1', $pingOutput, $pingExitCode);

    expect($versionExitCode)->toBe(0)
        ->and(implode("\n", $versionOutput))->toContain('Pulsar '.Pulsar::VERSION)
        ->and($pingExitCode)->toBe(0)
        ->and(implode("\n", $pingOutput))->toContain('Pulsar is alive.');
});
