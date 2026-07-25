<?php

namespace App\Pulsar\Support;

final class ProbeState
{
    public static int $commands = 0;

    public static int $committedEvents = 0;

    public static int $jobs = 0;

    public static int $placedEvents = 0;

    public static function reset(): void
    {
        self::$commands = 0;
        self::$committedEvents = 0;
        self::$jobs = 0;
        self::$placedEvents = 0;
    }
}
