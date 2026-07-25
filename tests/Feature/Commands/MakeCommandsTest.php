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

dataset('make command validation cases', [
    'make:action' => [MakeActionCommand::class, ['name' => 'CreateOrder', 'domain' => 'Orders']],
    'make:controller' => [MakeControllerCommand::class, ['name' => 'OrderController', 'module' => 'Orders', 'service' => 'Admin']],
    'make:dto' => [MakeDtoCommand::class, ['name' => 'OrderData', 'domain' => 'Orders']],
    'make:enum' => [MakeEnumCommand::class, ['name' => 'OrderStatus', 'domain' => 'Orders']],
    'make:event' => [MakeEventCommand::class, ['name' => 'OrderPlaced', 'domain' => 'Orders']],
    'make:exception' => [MakeExceptionCommand::class, ['name' => 'OrderNotFound', 'domain' => 'Orders']],
    'make:model' => [MakeModelCommand::class, ['name' => 'Order', 'domain' => 'Orders']],
    'make:operation' => [MakeOperationCommand::class, ['name' => 'PersistOrder', 'module' => 'Orders', 'service' => 'Admin']],
    'make:policy' => [MakePolicyCommand::class, ['name' => 'OrderPolicy', 'domain' => 'Orders']],
    'make:query' => [MakeQueryCommand::class, ['name' => 'FindOrder', 'domain' => 'Orders']],
    'make:request' => [MakeRequestCommand::class, ['name' => 'StoreOrderRequest', 'module' => 'Orders', 'service' => 'Admin']],
    'make:service' => [MakeServiceCommand::class, ['name' => 'Internal']],
    'make:use-case' => [MakeUseCaseCommand::class, ['name' => 'CreateOrder', 'module' => 'Orders', 'service' => 'Admin']],
]);

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

it('keeps Controller command and generator arguments in name module service order', function () {
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

it('passes the policy model option through to a model-aware stub', function () {
    $tester = new CommandTester(new MakePolicyCommand);
    $exitCode = $tester->execute([
        'name' => 'UserPolicy',
        'domain' => 'Accounts',
        '--model' => 'User',
    ]);
    $relativePath = implode(DIRECTORY_SEPARATOR, [
        'app', 'Pulsar', 'Domain', 'Accounts', 'Policies', 'UserPolicy.php',
    ]);
    $content = file_get_contents($this->tempDir.DIRECTORY_SEPARATOR.$relativePath);

    expect($exitCode)->toBe(Command::SUCCESS)
        ->and($content)
        ->toBeValidPhp()
        ->toContain('use App\Pulsar\Domain\Accounts\Models\User;')
        ->toContain('public function view(AuthUser $user, User $model): bool')
        ->toContain('public function create(AuthUser $user): bool')
        ->toContain('public function update(AuthUser $user, User $model): bool')
        ->toContain('public function delete(AuthUser $user, User $model): bool');
});

it('rejects reserved, invalid, and traversal class names before writing through every Make command', function (
    string $commandClass,
    array $arguments,
) {
    $snapshot = static function (string $root): array {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $paths[] = $item->getPathname();
        }

        sort($paths);

        return $paths;
    };

    foreach (['class', 'Invalid-Name', '../evil'] as $invalidName) {
        $invalidArguments = $arguments;
        $invalidArguments['name'] = $invalidName;
        $before = $snapshot($this->tempDir);
        $tester = new CommandTester(new $commandClass);

        expect($tester->execute($invalidArguments))->toBe(Command::FAILURE)
            ->and($tester->getDisplay())->toContain($invalidName)
            ->and($snapshot($this->tempDir))->toBe($before);
    }
})->with('make command validation cases');

it('rejects invalid and traversing path segments before writing through applicable Make commands', function (
    string $commandClass,
    array $arguments,
    string $segment,
) {
    $snapshot = static function (string $root): array {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $paths[] = $item->getPathname();
        }

        sort($paths);

        return $paths;
    };

    foreach (['../evil', 'Bad|Segment'] as $invalidSegment) {
        $invalidArguments = $arguments;
        $invalidArguments[$segment] = $invalidSegment;
        $before = $snapshot($this->tempDir);
        $tester = new CommandTester(new $commandClass);

        expect($tester->execute($invalidArguments))->toBe(Command::FAILURE)
            ->and($tester->getDisplay())->toContain($invalidSegment)
            ->and($snapshot($this->tempDir))->toBe($before);
    }
})->with([
    'make:action domain' => [MakeActionCommand::class, ['name' => 'CreateOrder', 'domain' => 'Orders'], 'domain'],
    'make:controller service' => [MakeControllerCommand::class, ['name' => 'OrderController', 'module' => 'Orders', 'service' => 'Admin'], 'service'],
    'make:controller module' => [MakeControllerCommand::class, ['name' => 'OrderController', 'module' => 'Orders', 'service' => 'Admin'], 'module'],
    'make:dto domain' => [MakeDtoCommand::class, ['name' => 'OrderData', 'domain' => 'Orders'], 'domain'],
    'make:enum domain' => [MakeEnumCommand::class, ['name' => 'OrderStatus', 'domain' => 'Orders'], 'domain'],
    'make:event domain' => [MakeEventCommand::class, ['name' => 'OrderPlaced', 'domain' => 'Orders'], 'domain'],
    'make:exception domain' => [MakeExceptionCommand::class, ['name' => 'OrderNotFound', 'domain' => 'Orders'], 'domain'],
    'make:model domain' => [MakeModelCommand::class, ['name' => 'Order', 'domain' => 'Orders'], 'domain'],
    'make:operation service' => [MakeOperationCommand::class, ['name' => 'PersistOrder', 'module' => 'Orders', 'service' => 'Admin'], 'service'],
    'make:operation module' => [MakeOperationCommand::class, ['name' => 'PersistOrder', 'module' => 'Orders', 'service' => 'Admin'], 'module'],
    'make:policy domain' => [MakePolicyCommand::class, ['name' => 'OrderPolicy', 'domain' => 'Orders'], 'domain'],
    'make:query domain' => [MakeQueryCommand::class, ['name' => 'FindOrder', 'domain' => 'Orders'], 'domain'],
    'make:request service' => [MakeRequestCommand::class, ['name' => 'StoreOrderRequest', 'module' => 'Orders', 'service' => 'Admin'], 'service'],
    'make:request module' => [MakeRequestCommand::class, ['name' => 'StoreOrderRequest', 'module' => 'Orders', 'service' => 'Admin'], 'module'],
    'make:service name' => [MakeServiceCommand::class, ['name' => 'Internal'], 'name'],
    'make:use-case service' => [MakeUseCaseCommand::class, ['name' => 'CreateOrder', 'module' => 'Orders', 'service' => 'Admin'], 'service'],
    'make:use-case module' => [MakeUseCaseCommand::class, ['name' => 'CreateOrder', 'module' => 'Orders', 'service' => 'Admin'], 'module'],
]);

it('warns only when a domain is created for the first time', function () {
    $first = new CommandTester(new MakeActionCommand);
    $second = new CommandTester(new MakeDtoCommand);

    expect($first->execute(['name' => 'CreateOrder', 'domain' => 'Orders']))->toBe(Command::SUCCESS)
        ->and($first->getDisplay())->toContain('Domain [Orders] did not exist and was created.')
        ->and($second->execute(['name' => 'OrderData', 'domain' => 'Orders']))->toBe(Command::SUCCESS)
        ->and($second->getDisplay())->not->toContain('did not exist and was created.');
});
