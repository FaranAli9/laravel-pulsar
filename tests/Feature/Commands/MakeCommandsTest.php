<?php

use Faran\Pulsar\Commands\MakeActionCommand;
use Faran\Pulsar\Commands\MakeControllerCommand;
use Faran\Pulsar\Commands\MakeDtoCommand;
use Faran\Pulsar\Commands\MakeEnumCommand;
use Faran\Pulsar\Commands\MakeEventCommand;
use Faran\Pulsar\Commands\MakeExceptionCommand;
use Faran\Pulsar\Commands\MakeModelCommand;
use Faran\Pulsar\Commands\MakeOperationCommand;
use Faran\Pulsar\Commands\MakePolicyCommand;
use Faran\Pulsar\Commands\MakeQueryCommand;
use Faran\Pulsar\Commands\MakeRequestCommand;
use Faran\Pulsar\Commands\MakeServiceCommand;
use Faran\Pulsar\Commands\MakeUseCaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function () {
    createService($this->tempDir, 'Admin');
});

it('runs each Make command successfully with the documented output and exit code', function (
    string $commandClass,
    array $arguments,
    string $successOutput,
    array $pathSegments,
) {
    $tester = new CommandTester(new $commandClass);
    $exitCode = $tester->execute($arguments);
    $expectedPath = implode(DIRECTORY_SEPARATOR, $pathSegments);

    expect($exitCode)->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain($successOutput)
        ->and($tester->getDisplay())->toContain($expectedPath)
        ->and(file_exists($this->tempDir.DIRECTORY_SEPARATOR.$expectedPath))->toBeTrue();
})->with([
    'make:action' => [
        MakeActionCommand::class,
        ['name' => 'CreateOrder', 'domain' => 'Orders'],
        'Action created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'Actions', 'CreateOrder.php'],
    ],
    'make:controller' => [
        MakeControllerCommand::class,
        ['name' => 'OrderController', 'module' => 'Orders', 'service' => 'Admin'],
        'Controller created successfully',
        ['app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Orders', 'Controllers', 'OrderController.php'],
    ],
    'make:dto' => [
        MakeDtoCommand::class,
        ['name' => 'OrderData', 'domain' => 'Orders'],
        'DTO created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'DTOs', 'OrderData.php'],
    ],
    'make:enum' => [
        MakeEnumCommand::class,
        ['name' => 'OrderStatus', 'domain' => 'Orders'],
        'Enum created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'Enums', 'OrderStatus.php'],
    ],
    'make:event' => [
        MakeEventCommand::class,
        ['name' => 'OrderPlaced', 'domain' => 'Orders'],
        'Event created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'Events', 'OrderPlaced.php'],
    ],
    'make:exception' => [
        MakeExceptionCommand::class,
        ['name' => 'OrderNotFound', 'domain' => 'Orders'],
        'Exception created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'Exceptions', 'OrderNotFound.php'],
    ],
    'make:model' => [
        MakeModelCommand::class,
        ['name' => 'Order', 'domain' => 'Orders'],
        'Model created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'Models', 'Order.php'],
    ],
    'make:operation' => [
        MakeOperationCommand::class,
        ['name' => 'PersistOrder', 'module' => 'Orders', 'service' => 'Admin'],
        'Operation created successfully',
        ['app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Orders', 'Operations', 'PersistOrder.php'],
    ],
    'make:policy' => [
        MakePolicyCommand::class,
        ['name' => 'OrderPolicy', 'domain' => 'Orders'],
        'Policy created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'Policies', 'OrderPolicy.php'],
    ],
    'make:query' => [
        MakeQueryCommand::class,
        ['name' => 'FindOrder', 'domain' => 'Orders'],
        'Query created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'Queries', 'FindOrder.php'],
    ],
    'make:request' => [
        MakeRequestCommand::class,
        ['name' => 'StoreOrderRequest', 'module' => 'Orders', 'service' => 'Admin'],
        'Request created successfully!',
        ['app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Orders', 'Requests', 'StoreOrderRequest.php'],
    ],
    'make:service' => [
        MakeServiceCommand::class,
        ['name' => 'Internal'],
        'Internal Service created successfully!',
        ['app', 'Pulsar', 'Services', 'Internal'],
    ],
    'make:use-case' => [
        MakeUseCaseCommand::class,
        ['name' => 'CreateOrder', 'module' => 'Orders', 'service' => 'Admin'],
        'UseCase created successfully!',
        ['app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Orders', 'UseCases', 'CreateOrder.php'],
    ],
]);

it('returns a failure exit code and error output from each Make command', function (
    string $commandClass,
    array $arguments,
) {
    $firstRun = new CommandTester(new $commandClass);
    expect($firstRun->execute($arguments))->toBe(Command::SUCCESS);

    $duplicateRun = new CommandTester(new $commandClass);
    $exitCode = $duplicateRun->execute($arguments);

    expect($exitCode)->toBe(Command::FAILURE)
        ->and($duplicateRun->getDisplay())->toContain('already exists');
})->with([
    'make:action failure' => [MakeActionCommand::class, ['name' => 'CreateOrder', 'domain' => 'Orders']],
    'make:controller failure' => [MakeControllerCommand::class, ['name' => 'OrderController', 'module' => 'Orders', 'service' => 'Admin']],
    'make:dto failure' => [MakeDtoCommand::class, ['name' => 'OrderData', 'domain' => 'Orders']],
    'make:enum failure' => [MakeEnumCommand::class, ['name' => 'OrderStatus', 'domain' => 'Orders']],
    'make:event failure' => [MakeEventCommand::class, ['name' => 'OrderPlaced', 'domain' => 'Orders']],
    'make:exception failure' => [MakeExceptionCommand::class, ['name' => 'OrderNotFound', 'domain' => 'Orders']],
    'make:model failure' => [MakeModelCommand::class, ['name' => 'Order', 'domain' => 'Orders']],
    'make:operation failure' => [MakeOperationCommand::class, ['name' => 'PersistOrder', 'module' => 'Orders', 'service' => 'Admin']],
    'make:policy failure' => [MakePolicyCommand::class, ['name' => 'OrderPolicy', 'domain' => 'Orders']],
    'make:query failure' => [MakeQueryCommand::class, ['name' => 'FindOrder', 'domain' => 'Orders']],
    'make:request failure' => [MakeRequestCommand::class, ['name' => 'StoreOrderRequest', 'module' => 'Orders', 'service' => 'Admin']],
    'make:service failure' => [MakeServiceCommand::class, ['name' => 'Internal']],
    'make:use-case failure' => [MakeUseCaseCommand::class, ['name' => 'CreateOrder', 'module' => 'Orders', 'service' => 'Admin']],
]);

it('maps Controller arguments from name module service to name service module', function () {
    $tester = new CommandTester(new MakeControllerCommand);
    $exitCode = $tester->execute([
        'name' => 'InvoiceController',
        'module' => 'Billing',
        'service' => 'Admin',
        '--resource' => true,
    ]);
    $relativePath = implode(DIRECTORY_SEPARATOR, [
        'app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Billing', 'Controllers', 'InvoiceController.php',
    ]);
    $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

    expect($exitCode)->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain("Location: {$relativePath}")
        ->and($content)
        ->toHaveNamespace('App\Pulsar\Services\Admin\Modules\Billing\Controllers')
        ->not->toContain('Services\Billing\Modules\Admin');
});
