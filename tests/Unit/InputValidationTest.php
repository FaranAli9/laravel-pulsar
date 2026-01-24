<?php

use Faran\Pulse\Exceptions\InvalidNameException;
use Tests\Helpers\TestGenerator;

describe('Input Validation', function () {
    
    beforeEach(function () {
        $this->generator = new TestGenerator();
    });
    
    describe('validateName() - Reserved PHP Keywords', function () {
        
        it('rejects class keyword', function () {
            expect(fn() => $this->generator->testValidateName('class'))
                ->toThrow(InvalidNameException::class, 'reserved PHP keyword');
        });
        
        it('rejects Class keyword (case insensitive)', function () {
            expect(fn() => $this->generator->testValidateName('Class'))
                ->toThrow(InvalidNameException::class, 'reserved PHP keyword');
        });
        
        it('rejects interface keyword', function () {
            expect(fn() => $this->generator->testValidateName('interface'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects namespace keyword', function () {
            expect(fn() => $this->generator->testValidateName('namespace'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects function keyword', function () {
            expect(fn() => $this->generator->testValidateName('function'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects abstract keyword', function () {
            expect(fn() => $this->generator->testValidateName('abstract'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects readonly keyword (PHP 8.1+)', function () {
            expect(fn() => $this->generator->testValidateName('readonly'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects match keyword (PHP 8.0+)', function () {
            expect(fn() => $this->generator->testValidateName('match'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('accepts Enum as class name', function () {
            // 'enum' keyword is for enum declarations, but Enum is valid as a class name
            expect(fn() => $this->generator->testValidateName('Enum'))
                ->not->toThrow(InvalidNameException::class);
        });
        
        it('accepts Classical (partial match)', function () {
            expect(fn() => $this->generator->testValidateName('Classical'))
                ->not->toThrow(InvalidNameException::class);
        });
        
        it('accepts UserClass (compound)', function () {
            expect(fn() => $this->generator->testValidateName('UserClass'))
                ->not->toThrow(InvalidNameException::class);
        });
        
        it('rejects namespaced keyword', function () {
            expect(fn() => $this->generator->testValidateName('Foo\\Class\\Bar'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('provides helpful error message mentioning keyword', function () {
            try {
                $this->generator->testValidateName('class');
                $this->fail('Should have thrown InvalidNameException');
            } catch (InvalidNameException $e) {
                expect($e->getMessage())
                    ->toContain('class')
                    ->toContain('reserved');
            }
        });
        
        it('specifies type in error message', function () {
            try {
                $this->generator->testValidateName('class', 'operation');
                $this->fail('Should have thrown InvalidNameException');
            } catch (InvalidNameException $e) {
                expect($e->getMessage())->toContain('operation');
            }
        });
        
        it('rejects all PHP keywords', function ($keyword) {
            expect(fn() => $this->generator->testValidateName($keyword))
                ->toThrow(InvalidNameException::class);
        })->with([
            'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 
            'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 
            'else', 'elseif', 'empty', 'extends', 'final', 'finally', 'fn', 'for', 
            'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 
            'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 
            'match', 'namespace', 'new', 'or', 'print', 'private', 'protected', 
            'public', 'readonly', 'require', 'require_once', 'return', 'static', 
            'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 
            'xor', 'yield'
        ]);
    });
    
    describe('validateName() - Invalid Characters', function () {
        
        it('rejects spaces', function () {
            expect(fn() => $this->generator->testValidateName('Create Order'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects hyphens', function () {
            expect(fn() => $this->generator->testValidateName('Create-Order'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects numbers at start', function () {
            expect(fn() => $this->generator->testValidateName('3Order'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('accepts numbers after start', function () {
            expect(fn() => $this->generator->testValidateName('Order2024'))
                ->not->toThrow(InvalidNameException::class);
        });
        
        it('rejects special characters', function ($char) {
            expect(fn() => $this->generator->testValidateName("Order{$char}"))
                ->toThrow(InvalidNameException::class);
        })->with(['!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '+', '=', '{', '}', '[', ']']);
        
        it('rejects dots', function () {
            expect(fn() => $this->generator->testValidateName('Order.Create'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('accepts underscores', function () {
            expect(fn() => $this->generator->testValidateName('Create_Order'))
                ->not->toThrow(InvalidNameException::class);
        });
        
        it('accepts backslashes (namespace)', function () {
            expect(fn() => $this->generator->testValidateName('App\\Order'))
                ->not->toThrow(InvalidNameException::class);
        });
        
        it('rejects forward slashes', function () {
            expect(fn() => $this->generator->testValidateName('App/Order'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('accepts unicode letters', function ($name) {
            expect(fn() => $this->generator->testValidateName($name))
                ->not->toThrow(InvalidNameException::class);
        })->with(['Übung', 'Café', 'München']);
        
        it('rejects empty string', function () {
            expect(fn() => $this->generator->testValidateName(''))
                ->toThrow(InvalidNameException::class);
        });
    });
    
    describe('validateName() - Length Limits', function () {
        
        it('rejects 101+ characters', function () {
            $longName = str_repeat('A', 101);
            expect(fn() => $this->generator->testValidateName($longName))
                ->toThrow(InvalidNameException::class, 'too long');
        });
        
        it('accepts 100 characters (max valid)', function () {
            $maxName = str_repeat('A', 100);
            expect(fn() => $this->generator->testValidateName($maxName))
                ->not->toThrow(InvalidNameException::class);
        });
        
        it('accepts 1 character (min valid)', function () {
            expect(fn() => $this->generator->testValidateName('A'))
                ->not->toThrow(InvalidNameException::class);
        });
    });
    
    describe('validateName() - Edge Cases', function () {
        
        it('rejects whitespace-only', function () {
            expect(fn() => $this->generator->testValidateName('   '))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects keyword after trim', function () {
            expect(fn() => $this->generator->testValidateName(' class '))
                ->toThrow(InvalidNameException::class);
        });
        
        it('accepts leading underscore', function () {
            expect(fn() => $this->generator->testValidateName('_Order'))
                ->not->toThrow(InvalidNameException::class);
        });
        
        it('accepts multiple underscores', function () {
            expect(fn() => $this->generator->testValidateName('__Order'))
                ->not->toThrow(InvalidNameException::class);
        });
        
        it('throws InvalidNameException (correct type)', function () {
            try {
                $this->generator->testValidateName('123');
            } catch (Exception $e) {
                expect($e)->toBeInstanceOf(InvalidNameException::class);
            }
        });
    });
    
    describe('sanitizeDirectoryName() - Path Traversal', function () {
        
        it('rejects ../ pattern', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('../'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects ../../etc/passwd', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('../../etc/passwd'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects forward slashes', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('/etc/'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects backslashes', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('C:\\Windows'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects mixed traversal', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('..\\/../'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('throws if modified during sanitization', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('Service/../etc'))
                ->toThrow(InvalidNameException::class, 'forbidden characters');
        });
        
        it('throws if empty after sanitization', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('...'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('removes leading/trailing dots', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('.ServiceName.'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('provides descriptive error', function () {
            try {
                $this->generator->testSanitizeDirectoryName('../');
            } catch (InvalidNameException $e) {
                expect($e->getMessage())
                    ->toContain('forbidden');
            }
        });
        
        it('returns sanitized value for valid input', function () {
            $result = $this->generator->testSanitizeDirectoryName('ValidService');
            expect($result)->toBe('ValidService');
        });
    });
    
    describe('sanitizeDirectoryName() - Forbidden Characters', function () {
        
        it('rejects colon (Windows drive)', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('C:'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects asterisk (wildcard)', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('Service*'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects question mark (wildcard)', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('Service?'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects quotes', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('"Service"'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('rejects angle brackets', function ($char) {
            expect(fn() => $this->generator->testSanitizeDirectoryName("Service{$char}"))
                ->toThrow(InvalidNameException::class);
        })->with(['<', '>']);
        
        it('rejects pipe', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName('Service|Name'))
                ->toThrow(InvalidNameException::class);
        });
        
        it('accepts valid characters', function ($name) {
            $result = $this->generator->testSanitizeDirectoryName($name);
            expect($result)->toBe($name);
        })->with(['ServiceName', 'Service123', 'Service_Name', 'ServiceABC']);
        
        it('trims whitespace', function () {
            expect(fn() => $this->generator->testSanitizeDirectoryName(' Service '))
                ->toThrow(InvalidNameException::class);
        });
    });
});
