<?php

it('ships an opt-in Pest architecture preset with all three dependency rules', function () {
    $preset = file_get_contents(
        dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'presets'.DIRECTORY_SEPARATOR.'PulsarArchitectureTest.php',
    );

    expect($preset)
        ->not->toBeFalse()
        ->toBeValidPhp()
        ->toContain("'App\\Pulsar\\Domain'")
        ->toContain("'App\\Pulsar\\Infrastructure'")
        ->toContain('Pulsar/Domain/*/Actions')
        ->toContain('Pulsar/Services/*/Modules/*/Controllers')
        ->toContain('Pulsar/Services/*/Modules/*/Operations')
        ->toContain('Pulsar/Services/*/Modules/*/UseCases')
        ->toContain('->toOnlyUse(')
        ->toContain("->ignoring('Illuminate')");
});
