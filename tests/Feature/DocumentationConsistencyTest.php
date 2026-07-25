<?php

describe('Documentation consistency', function () {
    $expectedCommands = [
        'install',
        'make:action',
        'make:adapter',
        'make:command',
        'make:contract',
        'make:controller',
        'make:domain',
        'make:dto',
        'make:enum',
        'make:event',
        'make:exception',
        'make:job',
        'make:listener',
        'make:mailable',
        'make:model',
        'make:notification',
        'make:operation',
        'make:policy',
        'make:query',
        'make:request',
        'make:resource',
        'make:service',
        'make:use-case',
        'make:value-object',
        'ping',
        'publish:context',
        'publish:skill',
    ];

    $expectedMethods = [
        'Action' => 'execute',
        'Operation' => 'execute',
        'Query' => 'execute',
        'UseCase' => 'execute',
    ];

    $projectRoot = dirname(__DIR__, 2);

    $readSection = static function (string $path, string $heading): string {
        $content = file_get_contents($path);

        expect($content)->not->toBeFalse();

        $matched = preg_match(
            '/^#{2,3}\s+'.preg_quote($heading, '/').'\s*$\R(?<section>.*?)(?=^#{1,3}\s|\z)/ms',
            $content,
            $matches,
        );

        expect($matched)->toBe(1, "Missing [{$heading}] section in {$path}");

        return $matches['section'];
    };

    $readDocumentedCommands = static function (string $path, string $heading) use ($readSection): array {
        $section = $readSection($path, $heading);
        preg_match_all('/\|\s*`((?:make|publish):[a-z-]+|install|ping)`\s*\|/', $section, $matches);
        $commands = array_values(array_unique($matches[1]));
        sort($commands);

        return $commands;
    };

    $readDocumentedMethods = static function (string $path) use ($readSection): array {
        $section = $readSection($path, 'Generated Method Conventions');
        preg_match_all(
            '/\|\s*(Action|Operation|Query|UseCase)\s*\|\s*`([a-z]+)`\s*\|/',
            $section,
            $matches,
            PREG_SET_ORDER,
        );

        $methods = [];

        foreach ($matches as $match) {
            $methods[$match[1]] = $match[2];
        }

        ksort($methods);

        return $methods;
    };

    it('keeps every published command list aligned with bin/pulsar', function () use (
        $expectedCommands,
        $projectRoot,
        $readDocumentedCommands,
    ) {
        $bin = file_get_contents($projectRoot.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'pulsar');

        expect($bin)->not->toBeFalse();

        preg_match_all('/new\s+([A-Z][A-Za-z]+Command)\(\)/', $bin, $matches);
        $registeredCommands = [];

        foreach (array_unique($matches[1]) as $commandClass) {
            $commandPath = $projectRoot.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Commands'
                .DIRECTORY_SEPARATOR.$commandClass.'.php';
            $commandSource = file_get_contents($commandPath);

            expect($commandSource)->not->toBeFalse();

            $matched = preg_match("/name:\\s*'([^']+)'/", $commandSource, $commandMatch);

            expect($matched)->toBe(1, "Missing AsCommand name in {$commandPath}");

            $registeredCommands[] = $commandMatch[1];
        }

        sort($registeredCommands);

        expect($registeredCommands)->toBe($expectedCommands);

        foreach ([
            [$projectRoot.DIRECTORY_SEPARATOR.'README.md', 'Commands Reference'],
            [$projectRoot.DIRECTORY_SEPARATOR.'CLAUDE.md', 'Pulsar Command Reference'],
            [$projectRoot.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'context.stub', 'Commands Reference'],
            [$projectRoot.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'skill.stub', 'Commands Reference'],
        ] as [$path, $heading]) {
            expect($readDocumentedCommands($path, $heading))
                ->toBe($registeredCommands, "Command list drifted in {$path}");
        }

        $agents = file_get_contents($projectRoot.DIRECTORY_SEPARATOR.'AGENTS.md');

        expect($agents)
            ->not->toBeFalse()
            ->toContain('[CLAUDE.md](CLAUDE.md)');
    });

    it('keeps generated workflow methods aligned with documented conventions', function () use (
        $expectedMethods,
        $projectRoot,
        $readDocumentedMethods,
    ) {
        $stubMethods = [];

        foreach ([
            'Action' => 'action',
            'Operation' => 'operation',
            'Query' => 'query',
            'UseCase' => 'use-case',
        ] as $type => $stubName) {
            $stubPath = $projectRoot.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'stubs'
                .DIRECTORY_SEPARATOR.$stubName.'.stub';
            $stub = file_get_contents($stubPath);

            expect($stub)->not->toBeFalse();

            $matched = preg_match('/public function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $stub, $methodMatch);

            expect($matched)->toBe(1, "Missing public method in {$stubPath}");

            $stubMethods[$type] = $methodMatch[1];
        }

        ksort($stubMethods);
        ksort($expectedMethods);

        expect($stubMethods)->toBe($expectedMethods);

        foreach ([
            $projectRoot.DIRECTORY_SEPARATOR.'README.md',
            $projectRoot.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'context.stub',
            $projectRoot.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'skill.stub',
        ] as $path) {
            expect($readDocumentedMethods($path))
                ->toBe($stubMethods, "Method convention drifted in {$path}");
        }
    });
});
