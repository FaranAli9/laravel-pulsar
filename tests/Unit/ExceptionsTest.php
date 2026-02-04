<?php

use Faran\Pulsar\Exceptions\InvalidNameException;
use Faran\Pulsar\Exceptions\FileAlreadyExistsException;
use Faran\Pulsar\Exceptions\StubNotFoundException;

describe('Custom Exceptions', function () {
    
    describe('InvalidNameException', function () {
        
        it('creates exception with make() factory', function () {
            $exception = InvalidNameException::make('BadName', 'contains invalid characters');
            
            expect($exception)->toBeInstanceOf(InvalidNameException::class);
            expect($exception->getMessage())->toContain('BadName');
            expect($exception->getMessage())->toContain('contains invalid characters');
        });
        
        it('includes name in error message', function () {
            $exception = InvalidNameException::make('Test123', 'some reason');
            
            expect($exception->getMessage())->toContain("Invalid name 'Test123'");
        });
        
        it('includes reason in error message', function () {
            $exception = InvalidNameException::make('Test', 'must start with uppercase');
            
            expect($exception->getMessage())->toContain('must start with uppercase');
        });
        
        it('creates exception for reserved keyword', function () {
            $exception = InvalidNameException::reservedKeyword('class', 'operation');
            
            expect($exception)->toBeInstanceOf(InvalidNameException::class);
            expect($exception->getMessage())->toContain('class');
            expect($exception->getMessage())->toContain('reserved PHP keyword');
        });
        
        it('includes type in reserved keyword message', function () {
            $exception = InvalidNameException::reservedKeyword('interface', 'controller');
            
            expect($exception->getMessage())->toContain('controller name');
        });
        
        it('creates exception for invalid characters', function () {
            $exception = InvalidNameException::invalidCharacters('My-Class', 'class');
            
            expect($exception)->toBeInstanceOf(InvalidNameException::class);
            expect($exception->getMessage())->toContain('My-Class');
            expect($exception->getMessage())->toContain('Invalid class name');
        });
        
        it('provides helpful message for invalid characters', function () {
            $exception = InvalidNameException::invalidCharacters('Test@123', 'service');
            
            expect($exception->getMessage())->toContain('Use only letters, numbers, underscores, and backslashes');
        });
        
        it('different factory methods create distinct messages', function () {
            $generic = InvalidNameException::make('Test', 'generic reason');
            $keyword = InvalidNameException::reservedKeyword('class', 'type');
            $chars = InvalidNameException::invalidCharacters('Test-', 'name');
            
            expect($generic->getMessage())->not->toBe($keyword->getMessage());
            expect($keyword->getMessage())->not->toBe($chars->getMessage());
            expect($chars->getMessage())->not->toBe($generic->getMessage());
        });
    });
    
    describe('FileAlreadyExistsException', function () {
        
        it('creates exception with make() factory', function () {
            $exception = FileAlreadyExistsException::make('Operation', 'CreateOrder', 'Sales/Order');
            
            expect($exception)->toBeInstanceOf(FileAlreadyExistsException::class);
            expect($exception->getMessage())->toContain('Operation');
            expect($exception->getMessage())->toContain('CreateOrder');
            expect($exception->getMessage())->toContain('Sales/Order');
        });
        
        it('includes file type in message', function () {
            $exception = FileAlreadyExistsException::make('Controller', 'OrderController', 'Sales');
            
            expect($exception->getMessage())->toContain('Controller');
        });
        
        it('includes file name in message', function () {
            $exception = FileAlreadyExistsException::make('Operation', 'ProcessPayment', 'Billing');
            
            expect($exception->getMessage())->toContain('[ProcessPayment]');
        });
        
        it('includes location in message', function () {
            $exception = FileAlreadyExistsException::make('UseCase', 'CreateUser', 'Auth/Registration');
            
            expect($exception->getMessage())->toContain('Auth/Registration');
        });
        
        it('formats message consistently', function () {
            $exception = FileAlreadyExistsException::make('Model', 'User', 'Domain/Auth');
            
            expect($exception->getMessage())->toBe('Model [User] already exists in Domain/Auth!');
        });
    });
    
    describe('StubNotFoundException', function () {
        
        it('creates exception with make() factory', function () {
            $exception = StubNotFoundException::make('/path/to/stub.stub');
            
            expect($exception)->toBeInstanceOf(StubNotFoundException::class);
            expect($exception->getMessage())->toContain('/path/to/stub.stub');
        });
        
        it('includes stub path in message', function () {
            $stubPath = '/Users/test/project/stubs/operation.stub';
            $exception = StubNotFoundException::make($stubPath);
            
            expect($exception->getMessage())->toContain($stubPath);
        });
        
        it('provides clear error message', function () {
            $exception = StubNotFoundException::make('operation.stub');
            
            expect($exception->getMessage())->toContain('Stub file not found');
        });
        
        it('handles relative paths', function () {
            $exception = StubNotFoundException::make('../../stubs/test.stub');
            
            expect($exception->getMessage())->toContain('../../stubs/test.stub');
        });
    });
    
    describe('Exception Hierarchy', function () {
        
        it('all custom exceptions extend Exception', function () {
            expect(InvalidNameException::make('test', 'reason'))->toBeInstanceOf(\Exception::class);
            expect(FileAlreadyExistsException::make('type', 'name', 'loc'))->toBeInstanceOf(\Exception::class);
            expect(StubNotFoundException::make('path'))->toBeInstanceOf(\Exception::class);
        });
        
        it('exceptions are throwable', function () {
            expect(fn() => throw InvalidNameException::make('test', 'reason'))
                ->toThrow(InvalidNameException::class);
            
            expect(fn() => throw FileAlreadyExistsException::make('t', 'n', 'l'))
                ->toThrow(FileAlreadyExistsException::class);
            
            expect(fn() => throw StubNotFoundException::make('path'))
                ->toThrow(StubNotFoundException::class);
        });
        
        it('exceptions are catchable as base Exception', function () {
            try {
                throw InvalidNameException::make('test', 'reason');
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(InvalidNameException::class);
            }
            
            try {
                throw FileAlreadyExistsException::make('t', 'n', 'l');
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(FileAlreadyExistsException::class);
            }
            
            try {
                throw StubNotFoundException::make('path');
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(StubNotFoundException::class);
            }
        });
    });
});
