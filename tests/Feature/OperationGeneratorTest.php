<?php

use Faran\Pulse\Generators\OperationGenerator;
use Faran\Pulse\Exceptions\ServiceDoesNotExistException;

describe('Operation Generator', function () {
    
    beforeEach(function () {
        // Create a test service
        createService($this->tempDir, 'TestService');
    });
    
    describe('Happy Path', function () {
        
        it('generates operation file', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $relativePath = $generator->generate();
            
            $fullPath = $this->tempDir . DIRECTORY_SEPARATOR . $relativePath;
            expect(file_exists($fullPath))->toBeTrue();
        });
        
        it('file in correct location', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $relativePath = $generator->generate();
            
            expect($relativePath)->toContain('app' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'TestService');
            expect($relativePath)->toContain('Modules' . DIRECTORY_SEPARATOR . 'Checkout');
            expect($relativePath)->toContain('Operations');
        });
        
        it('file has correct name', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $relativePath = $generator->generate();
            
            expect($relativePath)->toEndWith('CreateOrder.php');
        });
        
        it('content is valid PHP', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $relativePath = $generator->generate();
            
            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);
            expect($content)->toBeValidPhp();
        });
        
        it('namespace is correct', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $relativePath = $generator->generate();
            
            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);
            expect($content)->toHaveNamespace('App\Services\TestService\Modules\Checkout\Operations');
        });
        
        it('class name is correct', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $relativePath = $generator->generate();
            
            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);
            expect($content)->toHaveClass('CreateOrder');
        });
        
        it('returns relative path', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $relativePath = $generator->generate();
            
            expect($relativePath)->not->toStartWith($this->tempDir);
            expect($relativePath)->toStartWith('app');
        });
        
        it('creates intermediate directories', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $generator->generate();
            
            $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services' . 
                         DIRECTORY_SEPARATOR . 'TestService' . DIRECTORY_SEPARATOR . 'Modules' . 
                         DIRECTORY_SEPARATOR . 'Checkout';
            
            expect(is_dir($modulePath))->toBeTrue();
            expect(is_dir($modulePath . DIRECTORY_SEPARATOR . 'Operations'))->toBeTrue();
        });
    });
    
    describe('User Control Over Naming', function () {
        
        it('uses exact name provided by user', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $relativePath = $generator->generate();
            
            expect($relativePath)->toContain('CreateOrder.php');
        });
        
        it('preserves suffix if user provides it', function () {
            $generator = new OperationGenerator('CreateOrderOperation', 'Checkout', 'TestService');
            $relativePath = $generator->generate();
            
            expect($relativePath)->toContain('CreateOrderOperation.php');
            expect($relativePath)->not->toContain('CreateOrderOperationOperation.php');
        });
        
        it('preserves exact casing from user', function () {
            $generator = new OperationGenerator('CreateOrderoperation', 'Checkout', 'TestService');
            $relativePath = $generator->generate();
            
            expect($relativePath)->toContain('CreateOrderoperation.php');
        });
        
        it('file name matches class name exactly', function () {
            $generator = new OperationGenerator('ProcessPayment', 'Payment', 'TestService');
            $relativePath = $generator->generate();
            
            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);
            
            expect($relativePath)->toEndWith('ProcessPayment.php');
            expect($content)->toHaveClass('ProcessPayment');
        });
    });
    
    describe('Error Cases', function () {
        
        it('throws if service does not exist', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'NonExistentService');
            
            expect(fn() => $generator->generate())
                ->toThrow(ServiceDoesNotExistException::class);
        });
        
        it('throws if file already exists', function () {
            $generator1 = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $generator1->generate();
            
            $generator2 = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            
            expect(fn() => $generator2->generate())
                ->toThrow(Exception::class, 'already exists');
        });
        
        it('error message shows operation name', function () {
            $generator1 = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $generator1->generate();
            
            try {
                $generator2 = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
                $generator2->generate();
                $this->fail('Should have thrown exception');
            } catch (Exception $e) {
                expect($e->getMessage())->toContain('CreateOrder');
            }
        });
        
        it('error message shows location', function () {
            $generator1 = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $generator1->generate();
            
            try {
                $generator2 = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
                $generator2->generate();
                $this->fail('Should have thrown exception');
            } catch (Exception $e) {
                expect($e->getMessage())
                    ->toContain('TestService')
                    ->toContain('Checkout');
            }
        });
        
        it('does not create file on service not exist error', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'NonExistent');
            
            try {
                $generator->generate();
            } catch (ServiceDoesNotExistException $e) {
                // Expected
            }
            
            $operationPath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services' . 
                           DIRECTORY_SEPARATOR . 'NonExistent';
            
            expect(file_exists($operationPath))->toBeFalse();
        });
    });
    
    describe('Edge Cases', function () {
        
        it('handles multi-word module names', function () {
            $generator = new OperationGenerator('CreateOrder', 'UserAuthentication', 'TestService');
            $relativePath = $generator->generate();
            
            expect($relativePath)->toContain('UserAuthentication');
            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);
            expect($content)->toHaveNamespace('App\Services\TestService\Modules\UserAuthentication\Operations');
        });
        
        it('handles multi-word service names', function () {
            createService($this->tempDir, 'OrderManagement');
            
            $generator = new OperationGenerator('CreateOrder', 'Orders', 'OrderManagement');
            $relativePath = $generator->generate();
            
            expect($relativePath)->toContain('OrderManagement');
            $content = file_get_contents($this->tempDir . DIRECTORY_SEPARATOR . $relativePath);
            expect($content)->toHaveNamespace('App\Services\OrderManagement\Modules\Orders\Operations');
        });
        
        it('creates module if it does not exist', function () {
            $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services' . 
                         DIRECTORY_SEPARATOR . 'TestService' . DIRECTORY_SEPARATOR . 'Modules' . 
                         DIRECTORY_SEPARATOR . 'NewModule';
            
            expect(is_dir($modulePath))->toBeFalse();
            
            $generator = new OperationGenerator('CreateOrder', 'NewModule', 'TestService');
            $generator->generate();
            
            expect(is_dir($modulePath))->toBeTrue();
        });
        
        it('creates Operations directory if needed', function () {
            $operationsPath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services' . 
                            DIRECTORY_SEPARATOR . 'TestService' . DIRECTORY_SEPARATOR . 'Modules' . 
                            DIRECTORY_SEPARATOR . 'Checkout' . DIRECTORY_SEPARATOR . 'Operations';
            
            expect(is_dir($operationsPath))->toBeFalse();
            
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $generator->generate();
            
            expect(is_dir($operationsPath))->toBeTrue();
        });
    });
    
    describe('Module Directory Structure', function () {
        
        it('creates Operations directory', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $generator->generate();
            
            $operationsPath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services' . 
                             DIRECTORY_SEPARATOR . 'TestService' . DIRECTORY_SEPARATOR . 'Modules' . 
                             DIRECTORY_SEPARATOR . 'Checkout' . DIRECTORY_SEPARATOR . 'Operations';
            
            expect(is_dir($operationsPath))->toBeTrue();
        });
        
        it('creates module directory', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $generator->generate();
            
            $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services' . 
                          DIRECTORY_SEPARATOR . 'TestService' . DIRECTORY_SEPARATOR . 'Modules' . 
                          DIRECTORY_SEPARATOR . 'Checkout';
            
            expect(is_dir($modulePath))->toBeTrue();
        });
        
        it('nests operation file correctly', function () {
            $generator = new OperationGenerator('CreateOrder', 'Checkout', 'TestService');
            $generator->generate();
            
            $operationPath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services' . 
                          DIRECTORY_SEPARATOR . 'TestService' . DIRECTORY_SEPARATOR . 'Modules' . 
                          DIRECTORY_SEPARATOR . 'Checkout' . DIRECTORY_SEPARATOR . 'Operations' . DIRECTORY_SEPARATOR . 'CreateOrder.php';
            
            expect(file_exists($operationPath))->toBeTrue();
        });
    });
});
