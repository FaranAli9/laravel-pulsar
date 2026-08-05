<?php

use Faran\Pulsar\Commands\MakeActionCommand;
use Faran\Pulsar\Commands\MakeAdapterCommand;
use Faran\Pulsar\Commands\MakeCommandCommand;
use Faran\Pulsar\Commands\MakeContractCommand;
use Faran\Pulsar\Commands\MakeControllerCommand;
use Faran\Pulsar\Commands\MakeDomainCommand;
use Faran\Pulsar\Commands\MakeDtoCommand;
use Faran\Pulsar\Commands\MakeEnumCommand;
use Faran\Pulsar\Commands\MakeEventCommand;
use Faran\Pulsar\Commands\MakeExceptionCommand;
use Faran\Pulsar\Commands\MakeJobCommand;
use Faran\Pulsar\Commands\MakeListenerCommand;
use Faran\Pulsar\Commands\MakeMailableCommand;
use Faran\Pulsar\Commands\MakeModelCommand;
use Faran\Pulsar\Commands\MakeNotificationCommand;
use Faran\Pulsar\Commands\MakeOperationCommand;
use Faran\Pulsar\Commands\MakePolicyCommand;
use Faran\Pulsar\Commands\MakeQueryCommand;
use Faran\Pulsar\Commands\MakeRequestCommand;
use Faran\Pulsar\Commands\MakeResourceCommand;
use Faran\Pulsar\Commands\MakeServiceCommand;
use Faran\Pulsar\Commands\MakeUseCaseCommand;
use Faran\Pulsar\Commands\MakeValueObjectCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function () {
    createService($this->tempDir, 'Admin');
    createDomain($this->tempDir, 'Orders');
    createDomain($this->tempDir, 'Accounts');
    createDomain($this->tempDir, 'Billing');
});

dataset('make command validation cases', [
    'make:action' => [MakeActionCommand::class, ['name' => 'CreateOrder', 'domain' => 'Orders']],
    'make:adapter' => [MakeAdapterCommand::class, ['name' => 'StripePaymentGateway', 'area' => 'Payments']],
    'make:command' => [MakeCommandCommand::class, ['name' => 'ReconcileLedger', 'module' => 'Billing', 'service' => 'Admin']],
    'make:contract' => [MakeContractCommand::class, ['name' => 'PaymentGateway', 'domain' => 'Billing']],
    'make:controller' => [MakeControllerCommand::class, ['name' => 'OrderController', 'module' => 'Orders', 'service' => 'Admin']],
    'make:dto' => [MakeDtoCommand::class, ['name' => 'OrderData', 'domain' => 'Orders']],
    'make:domain' => [MakeDomainCommand::class, ['name' => 'Catalog']],
    'make:enum' => [MakeEnumCommand::class, ['name' => 'OrderStatus', 'domain' => 'Orders']],
    'make:event' => [MakeEventCommand::class, ['name' => 'OrderPlaced', 'domain' => 'Orders']],
    'make:exception' => [MakeExceptionCommand::class, ['name' => 'OrderNotFound', 'domain' => 'Orders']],
    'make:job' => [MakeJobCommand::class, ['name' => 'ProcessOrder', 'module' => 'Orders', 'service' => 'Admin']],
    'make:listener' => [MakeListenerCommand::class, ['name' => 'AuditOrderPlaced', 'domain' => 'Orders']],
    'make:mailable' => [MakeMailableCommand::class, ['name' => 'OrderReceiptMail', 'domain' => 'Orders']],
    'make:model' => [MakeModelCommand::class, ['name' => 'Order', 'domain' => 'Orders']],
    'make:notification' => [MakeNotificationCommand::class, ['name' => 'OrderReceiptNotification', 'domain' => 'Orders']],
    'make:operation' => [MakeOperationCommand::class, ['name' => 'PersistOrder', 'module' => 'Orders', 'service' => 'Admin']],
    'make:policy' => [MakePolicyCommand::class, ['name' => 'OrderPolicy', 'domain' => 'Orders']],
    'make:query' => [MakeQueryCommand::class, ['name' => 'FindOrder', 'domain' => 'Orders']],
    'make:request' => [MakeRequestCommand::class, ['name' => 'StoreOrderRequest', 'module' => 'Orders', 'service' => 'Admin']],
    'make:resource' => [MakeResourceCommand::class, ['name' => 'OrderResource', 'module' => 'Orders', 'service' => 'Admin']],
    'make:service' => [MakeServiceCommand::class, ['name' => 'Internal']],
    'make:use-case' => [MakeUseCaseCommand::class, ['name' => 'CreateOrder', 'module' => 'Orders', 'service' => 'Admin']],
    'make:value-object' => [MakeValueObjectCommand::class, ['name' => 'OrderNumber', 'domain' => 'Orders']],
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
    'make:adapter' => [
        MakeAdapterCommand::class,
        [
            'name' => 'StripePaymentGateway',
            'area' => 'Payments',
            '--contract' => 'PaymentGateway',
            '--domain' => 'Billing',
        ],
        'Adapter created successfully',
        ['app', 'Pulsar', 'Infrastructure', 'Payments', 'StripePaymentGateway.php'],
    ],
    'make:command' => [
        MakeCommandCommand::class,
        [
            'name' => 'ReconcileLedger',
            'module' => 'Billing',
            'service' => 'Admin',
            '--signature' => 'billing:reconcile',
        ],
        'Command created successfully',
        ['app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Billing', 'Commands', 'ReconcileLedger.php'],
    ],
    'make:contract' => [
        MakeContractCommand::class,
        ['name' => 'PaymentGateway', 'domain' => 'Billing'],
        'Contract created successfully',
        ['app', 'Pulsar', 'Domain', 'Billing', 'Contracts', 'PaymentGateway.php'],
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
    'make:domain' => [
        MakeDomainCommand::class,
        ['name' => 'Catalog'],
        'Domain created successfully',
        ['app', 'Pulsar', 'Domain', 'Catalog', '.gitkeep'],
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
    'make:job' => [
        MakeJobCommand::class,
        ['name' => 'ProcessOrder', 'module' => 'Orders', 'service' => 'Admin'],
        'Job created successfully',
        ['app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Orders', 'Jobs', 'ProcessOrder.php'],
    ],
    'make:listener' => [
        MakeListenerCommand::class,
        ['name' => 'AuditOrderPlaced', 'domain' => 'Orders', '--event' => 'OrderPlaced'],
        'Listener created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'Listeners', 'AuditOrderPlaced.php'],
    ],
    'make:mailable' => [
        MakeMailableCommand::class,
        ['name' => 'OrderReceiptMail', 'domain' => 'Orders'],
        'Mailable created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'Mail', 'OrderReceiptMail.php'],
    ],
    'make:model' => [
        MakeModelCommand::class,
        ['name' => 'Order', 'domain' => 'Orders'],
        'Model created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'Models', 'Order.php'],
    ],
    'make:notification' => [
        MakeNotificationCommand::class,
        ['name' => 'OrderReceiptNotification', 'domain' => 'Orders'],
        'Notification created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'Notifications', 'OrderReceiptNotification.php'],
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
    'make:resource' => [
        MakeResourceCommand::class,
        ['name' => 'OrderResource', 'module' => 'Orders', 'service' => 'Admin'],
        'Resource created successfully',
        ['app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Orders', 'Resources', 'OrderResource.php'],
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
    'make:value-object' => [
        MakeValueObjectCommand::class,
        ['name' => 'OrderNumber', 'domain' => 'Orders'],
        'Value Object created successfully',
        ['app', 'Pulsar', 'Domain', 'Orders', 'ValueObjects', 'OrderNumber.php'],
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
    'make:adapter failure' => [MakeAdapterCommand::class, ['name' => 'StripePaymentGateway', 'area' => 'Payments']],
    'make:command failure' => [MakeCommandCommand::class, ['name' => 'ReconcileLedger', 'module' => 'Billing', 'service' => 'Admin']],
    'make:contract failure' => [MakeContractCommand::class, ['name' => 'PaymentGateway', 'domain' => 'Billing']],
    'make:controller failure' => [MakeControllerCommand::class, ['name' => 'OrderController', 'module' => 'Orders', 'service' => 'Admin']],
    'make:dto failure' => [MakeDtoCommand::class, ['name' => 'OrderData', 'domain' => 'Orders']],
    'make:domain failure' => [MakeDomainCommand::class, ['name' => 'Catalog']],
    'make:enum failure' => [MakeEnumCommand::class, ['name' => 'OrderStatus', 'domain' => 'Orders']],
    'make:event failure' => [MakeEventCommand::class, ['name' => 'OrderPlaced', 'domain' => 'Orders']],
    'make:exception failure' => [MakeExceptionCommand::class, ['name' => 'OrderNotFound', 'domain' => 'Orders']],
    'make:job failure' => [MakeJobCommand::class, ['name' => 'ProcessOrder', 'module' => 'Orders', 'service' => 'Admin']],
    'make:listener failure' => [MakeListenerCommand::class, ['name' => 'AuditOrderPlaced', 'domain' => 'Orders']],
    'make:mailable failure' => [MakeMailableCommand::class, ['name' => 'OrderReceiptMail', 'domain' => 'Orders']],
    'make:model failure' => [MakeModelCommand::class, ['name' => 'Order', 'domain' => 'Orders']],
    'make:notification failure' => [MakeNotificationCommand::class, ['name' => 'OrderReceiptNotification', 'domain' => 'Orders']],
    'make:operation failure' => [MakeOperationCommand::class, ['name' => 'PersistOrder', 'module' => 'Orders', 'service' => 'Admin']],
    'make:policy failure' => [MakePolicyCommand::class, ['name' => 'OrderPolicy', 'domain' => 'Orders']],
    'make:query failure' => [MakeQueryCommand::class, ['name' => 'FindOrder', 'domain' => 'Orders']],
    'make:request failure' => [MakeRequestCommand::class, ['name' => 'StoreOrderRequest', 'module' => 'Orders', 'service' => 'Admin']],
    'make:resource failure' => [MakeResourceCommand::class, ['name' => 'OrderResource', 'module' => 'Orders', 'service' => 'Admin']],
    'make:service failure' => [MakeServiceCommand::class, ['name' => 'Internal']],
    'make:use-case failure' => [MakeUseCaseCommand::class, ['name' => 'CreateOrder', 'module' => 'Orders', 'service' => 'Admin']],
    'make:value-object failure' => [MakeValueObjectCommand::class, ['name' => 'OrderNumber', 'domain' => 'Orders']],
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

it('passes the web option through to the Service generator and prints provider registration', function () {
    $tester = new CommandTester(new MakeServiceCommand);
    $exitCode = $tester->execute([
        'name' => 'Portal',
        '--web' => true,
    ]);
    $servicePath = $this->tempDir.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, [
        'app', 'Pulsar', 'Services', 'Portal',
    ]);

    expect($exitCode)->toBe(Command::SUCCESS)
        ->and(file_exists($servicePath.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'api.php'))->toBeTrue()
        ->and(file_exists($servicePath.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'web.php'))->toBeTrue()
        ->and($tester->getDisplay())
        ->toContain('Register: App\Pulsar\Services\Portal\Providers\PortalServiceProvider::class');
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
    'make:adapter area' => [MakeAdapterCommand::class, ['name' => 'StripePaymentGateway', 'area' => 'Payments'], 'area'],
    'make:adapter domain' => [
        MakeAdapterCommand::class,
        [
            'name' => 'StripePaymentGateway',
            'area' => 'Payments',
            '--contract' => 'PaymentGateway',
            '--domain' => 'Billing',
        ],
        '--domain',
    ],
    'make:command service' => [MakeCommandCommand::class, ['name' => 'ReconcileLedger', 'module' => 'Billing', 'service' => 'Admin'], 'service'],
    'make:command module' => [MakeCommandCommand::class, ['name' => 'ReconcileLedger', 'module' => 'Billing', 'service' => 'Admin'], 'module'],
    'make:contract domain' => [MakeContractCommand::class, ['name' => 'PaymentGateway', 'domain' => 'Billing'], 'domain'],
    'make:controller service' => [MakeControllerCommand::class, ['name' => 'OrderController', 'module' => 'Orders', 'service' => 'Admin'], 'service'],
    'make:controller module' => [MakeControllerCommand::class, ['name' => 'OrderController', 'module' => 'Orders', 'service' => 'Admin'], 'module'],
    'make:dto domain' => [MakeDtoCommand::class, ['name' => 'OrderData', 'domain' => 'Orders'], 'domain'],
    'make:enum domain' => [MakeEnumCommand::class, ['name' => 'OrderStatus', 'domain' => 'Orders'], 'domain'],
    'make:event domain' => [MakeEventCommand::class, ['name' => 'OrderPlaced', 'domain' => 'Orders'], 'domain'],
    'make:exception domain' => [MakeExceptionCommand::class, ['name' => 'OrderNotFound', 'domain' => 'Orders'], 'domain'],
    'make:job service' => [MakeJobCommand::class, ['name' => 'ProcessOrder', 'module' => 'Orders', 'service' => 'Admin'], 'service'],
    'make:job module' => [MakeJobCommand::class, ['name' => 'ProcessOrder', 'module' => 'Orders', 'service' => 'Admin'], 'module'],
    'make:listener domain' => [MakeListenerCommand::class, ['name' => 'AuditOrderPlaced', 'domain' => 'Orders'], 'domain'],
    'make:mailable domain' => [MakeMailableCommand::class, ['name' => 'OrderReceiptMail', 'domain' => 'Orders'], 'domain'],
    'make:model domain' => [MakeModelCommand::class, ['name' => 'Order', 'domain' => 'Orders'], 'domain'],
    'make:notification domain' => [MakeNotificationCommand::class, ['name' => 'OrderReceiptNotification', 'domain' => 'Orders'], 'domain'],
    'make:operation service' => [MakeOperationCommand::class, ['name' => 'PersistOrder', 'module' => 'Orders', 'service' => 'Admin'], 'service'],
    'make:operation module' => [MakeOperationCommand::class, ['name' => 'PersistOrder', 'module' => 'Orders', 'service' => 'Admin'], 'module'],
    'make:policy domain' => [MakePolicyCommand::class, ['name' => 'OrderPolicy', 'domain' => 'Orders'], 'domain'],
    'make:query domain' => [MakeQueryCommand::class, ['name' => 'FindOrder', 'domain' => 'Orders'], 'domain'],
    'make:request service' => [MakeRequestCommand::class, ['name' => 'StoreOrderRequest', 'module' => 'Orders', 'service' => 'Admin'], 'service'],
    'make:request module' => [MakeRequestCommand::class, ['name' => 'StoreOrderRequest', 'module' => 'Orders', 'service' => 'Admin'], 'module'],
    'make:resource service' => [MakeResourceCommand::class, ['name' => 'OrderResource', 'module' => 'Orders', 'service' => 'Admin'], 'service'],
    'make:resource module' => [MakeResourceCommand::class, ['name' => 'OrderResource', 'module' => 'Orders', 'service' => 'Admin'], 'module'],
    'make:service name' => [MakeServiceCommand::class, ['name' => 'Internal'], 'name'],
    'make:use-case service' => [MakeUseCaseCommand::class, ['name' => 'CreateOrder', 'module' => 'Orders', 'service' => 'Admin'], 'service'],
    'make:use-case module' => [MakeUseCaseCommand::class, ['name' => 'CreateOrder', 'module' => 'Orders', 'service' => 'Admin'], 'module'],
    'make:value-object domain' => [MakeValueObjectCommand::class, ['name' => 'OrderNumber', 'domain' => 'Orders'], 'domain'],
]);

it('passes entrypoint variant options through to their generated stubs', function () {
    $commandTester = new CommandTester(new MakeCommandCommand);
    $listenerTester = new CommandTester(new MakeListenerCommand);
    $resourceTester = new CommandTester(new MakeResourceCommand);

    expect($commandTester->execute([
        'name' => 'ArchiveOrders',
        'module' => 'Orders',
        'service' => 'Admin',
        '--signature' => 'orders:archive {tenant}',
    ]))->toBe(Command::SUCCESS);

    $commandPath = implode(DIRECTORY_SEPARATOR, [
        $this->tempDir, 'app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Orders', 'Commands', 'ArchiveOrders.php',
    ]);
    $commandContent = file_get_contents($commandPath);

    expect($commandContent)->toContain("protected \$signature = 'orders:archive {tenant}';")
        ->and($listenerTester->execute([
            'name' => 'SendOrderReceipt',
            'domain' => 'Orders',
            '--event' => 'OrderPaid',
            '--queued' => true,
        ]))->toBe(Command::SUCCESS);

    $listenerPath = implode(DIRECTORY_SEPARATOR, [
        $this->tempDir, 'app', 'Pulsar', 'Domain', 'Orders', 'Listeners', 'SendOrderReceipt.php',
    ]);
    $listenerContent = file_get_contents($listenerPath);

    expect($listenerContent)
        ->toContain('use App\Pulsar\Domain\Orders\Events\OrderPaid;')
        ->toContain('implements ShouldQueue, ShouldQueueAfterCommit')
        ->and($resourceTester->execute([
            'name' => 'OrderCollection',
            'module' => 'Orders',
            'service' => 'Admin',
            '--collection' => true,
        ]))->toBe(Command::SUCCESS);

    $resourcePath = implode(DIRECTORY_SEPARATOR, [
        $this->tempDir, 'app', 'Pulsar', 'Services', 'Admin', 'Modules', 'Orders', 'Resources', 'OrderCollection.php',
    ]);
    $resourceContent = file_get_contents($resourcePath);

    expect($resourceContent)->toContain('extends ResourceCollection');
});

it('prints the adapter binding line and normalizes contract suffixes through the CLI', function () {
    $contract = new CommandTester(new MakeContractCommand);
    $adapter = new CommandTester(new MakeAdapterCommand);

    expect($contract->execute([
        'name' => 'ClockContract',
        'domain' => 'Billing',
    ]))->toBe(Command::SUCCESS)
        ->and($contract->getDisplay())->toContain(
            implode(DIRECTORY_SEPARATOR, [
                'app', 'Pulsar', 'Domain', 'Billing', 'Contracts', 'Clock.php',
            ]),
        )
        ->and($adapter->execute([
            'name' => 'SystemClock',
            'area' => 'Time',
            '--contract' => 'Clock',
            '--domain' => 'Billing',
        ]))->toBe(Command::SUCCESS)
        ->and($adapter->getDisplay())
        ->toContain('$this->app->bind(Clock::class, SystemClock::class);');
});

it('hard-fails domain type commands when their domain does not exist', function (
    string $commandClass,
    array $arguments,
) {
    $tester = new CommandTester(new $commandClass);

    expect($tester->execute($arguments))->toBe(Command::FAILURE)
        ->and($tester->getDisplay())->toContain('Domain [Missing] does not exist')
        ->and(is_dir($this->tempDir.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Pulsar'
            .DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR.'Missing'))->toBeFalse();
})->with([
    'make:action missing domain' => [MakeActionCommand::class, ['name' => 'CreateOrder', 'domain' => 'Missing']],
    'make:contract missing domain' => [MakeContractCommand::class, ['name' => 'PaymentGateway', 'domain' => 'Missing']],
    'make:dto missing domain' => [MakeDtoCommand::class, ['name' => 'OrderData', 'domain' => 'Missing']],
    'make:enum missing domain' => [MakeEnumCommand::class, ['name' => 'OrderStatus', 'domain' => 'Missing']],
    'make:event missing domain' => [MakeEventCommand::class, ['name' => 'OrderPlaced', 'domain' => 'Missing']],
    'make:exception missing domain' => [MakeExceptionCommand::class, ['name' => 'OrderNotFound', 'domain' => 'Missing']],
    'make:listener missing domain' => [MakeListenerCommand::class, ['name' => 'AuditOrderPlaced', 'domain' => 'Missing']],
    'make:mailable missing domain' => [MakeMailableCommand::class, ['name' => 'OrderReceiptMail', 'domain' => 'Missing']],
    'make:model missing domain' => [MakeModelCommand::class, ['name' => 'Order', 'domain' => 'Missing']],
    'make:notification missing domain' => [MakeNotificationCommand::class, ['name' => 'OrderReceiptNotification', 'domain' => 'Missing']],
    'make:policy missing domain' => [MakePolicyCommand::class, ['name' => 'OrderPolicy', 'domain' => 'Missing']],
    'make:query missing domain' => [MakeQueryCommand::class, ['name' => 'FindOrder', 'domain' => 'Missing']],
    'make:value-object missing domain' => [MakeValueObjectCommand::class, ['name' => 'OrderNumber', 'domain' => 'Missing']],
]);

it('hard-fails new service-layer commands when their service does not exist', function (
    string $commandClass,
    array $arguments,
) {
    $tester = new CommandTester(new $commandClass);

    expect($tester->execute($arguments))->toBe(Command::FAILURE)
        ->and($tester->getDisplay())->toContain('Service [Missing] does not exist')
        ->and(is_dir($this->tempDir.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Pulsar'
            .DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'Missing'))->toBeFalse();
})->with([
    'make:command missing service' => [
        MakeCommandCommand::class,
        ['name' => 'ReconcileLedger', 'module' => 'Billing', 'service' => 'Missing'],
    ],
    'make:job missing service' => [
        MakeJobCommand::class,
        ['name' => 'ProcessOrder', 'module' => 'Orders', 'service' => 'Missing'],
    ],
    'make:resource missing service' => [
        MakeResourceCommand::class,
        ['name' => 'OrderResource', 'module' => 'Orders', 'service' => 'Missing'],
    ],
]);
