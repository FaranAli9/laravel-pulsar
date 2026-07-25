<?php

/**
 * Optional Pulsar architecture rules.
 *
 * Copy this file to tests/Arch/PulsarArchitectureTest.php in the consuming Laravel app.
 * Pest's architecture plugin is required.
 */
$namespaceDirectories = static function (string $pattern): array {
    $directories = glob(app_path($pattern), GLOB_ONLYDIR) ?: [];

    return array_values(array_map(
        static function (string $directory): string {
            $relative = substr($directory, strlen(app_path()) + 1);

            return 'App\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
        },
        $directories,
    ));
};

arch('Pulsar Domain never imports Services')
    ->expect('App\Pulsar\Domain')
    ->not->toUse('App\Pulsar\Services');

$infrastructureForbiddenDependencies = array_merge(
    ['App\Pulsar\Services'],
    $namespaceDirectories('Pulsar/Domain/*/Actions'),
    $namespaceDirectories('Pulsar/Services/*/Modules/*/UseCases'),
    $namespaceDirectories('Pulsar/Services/*/Modules/*/Operations'),
    $namespaceDirectories('Pulsar/Services/*/Modules/*/Controllers'),
);

arch('Pulsar Infrastructure stays outside workflows and delivery')
    ->expect('App\Pulsar\Infrastructure')
    ->not->toUse($infrastructureForbiddenDependencies);

$controllers = $namespaceDirectories('Pulsar/Services/*/Modules/*/Controllers');
$controllerDependencies = array_merge(
    $namespaceDirectories('Pulsar/Services/*/Modules/*/Requests'),
    $namespaceDirectories('Pulsar/Services/*/Modules/*/Resources'),
    $namespaceDirectories('Pulsar/Services/*/Modules/*/UseCases'),
    $namespaceDirectories('Pulsar/Domain/*/DTOs'),
    $namespaceDirectories('Pulsar/Domain/*/ValueObjects'),
    $namespaceDirectories('Pulsar/Domain/*/Enums'),
);

if ($controllers !== []) {
    arch('Pulsar Controllers depend only on Domain DTOs and UseCases')
        ->expect($controllers)
        ->toOnlyUse($controllerDependencies)
        ->ignoring('Illuminate');
}
