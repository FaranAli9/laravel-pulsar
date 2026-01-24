<?php

use Tests\Helpers\TestGenerator;
use Faran\Pulse\Exceptions\StubNotFoundException;

describe('Generator Base Class', function () {
    
    describe('replaceStubPlaceholders()', function () {
        
        it('replaces single placeholder', function () {
            $generator = new TestGenerator();
            $stub = 'Hello {{name}}!';
            $result = $generator->testReplaceStubPlaceholders($stub, ['name' => 'World']);
            
            expect($result)->toBe('Hello World!');
        });
        
        it('replaces multiple placeholders', function () {
            $generator = new TestGenerator();
            $stub = '{{namespace}}\{{class}} extends {{parent}}';
            $result = $generator->testReplaceStubPlaceholders($stub, [
                'namespace' => 'App\\Services',
                'class' => 'MyClass',
                'parent' => 'BaseClass'
            ]);
            
            expect($result)->toBe('App\\Services\MyClass extends BaseClass');
        });
        
        it('replaces repeated placeholders', function () {
            $generator = new TestGenerator();
            $stub = '{{name}} said {{name}} twice';
            $result = $generator->testReplaceStubPlaceholders($stub, ['name' => 'Alice']);
            
            expect($result)->toBe('Alice said Alice twice');
        });
        
        it('ignores placeholders not in replacements array', function () {
            $generator = new TestGenerator();
            $stub = '{{replaced}} and {{notReplaced}}';
            $result = $generator->testReplaceStubPlaceholders($stub, ['replaced' => 'REPLACED']);
            
            expect($result)->toBe('REPLACED and {{notReplaced}}');
        });
        
        it('handles empty stub', function () {
            $generator = new TestGenerator();
            $result = $generator->testReplaceStubPlaceholders('', ['name' => 'Test']);
            
            expect($result)->toBe('');
        });
        
        it('handles empty replacements array', function () {
            $generator = new TestGenerator();
            $stub = '{{name}}';
            $result = $generator->testReplaceStubPlaceholders($stub, []);
            
            expect($result)->toBe('{{name}}');
        });
        
        it('preserves stub without placeholders', function () {
            $generator = new TestGenerator();
            $stub = 'No placeholders here';
            $result = $generator->testReplaceStubPlaceholders($stub, ['name' => 'Test']);
            
            expect($result)->toBe('No placeholders here');
        });
        
        it('replaces complex PHP code placeholders', function () {
            $generator = new TestGenerator();
            $stub = "namespace {{namespace}};\n\nclass {{class}}\n{\n    // {{comment}}\n}";
            $result = $generator->testReplaceStubPlaceholders($stub, [
                'namespace' => 'App\\Services\\Order',
                'class' => 'CreateOrder',
                'comment' => 'Business logic here'
            ]);
            
            expect($result)->toContain('namespace App\\Services\\Order;');
            expect($result)->toContain('class CreateOrder');
            expect($result)->toContain('// Business logic here');
        });
    });
    
    describe('generateSlug()', function () {
        
        it('converts PascalCase to kebab-case', function () {
            $generator = new TestGenerator();
            expect($generator->testGenerateSlug('CreateOrder'))->toBe('create-order');
        });
        
        it('converts single word to lowercase', function () {
            $generator = new TestGenerator();
            expect($generator->testGenerateSlug('Order'))->toBe('order');
        });
        
        it('handles already lowercase', function () {
            $generator = new TestGenerator();
            expect($generator->testGenerateSlug('order'))->toBe('order');
        });
        
        it('handles multiple consecutive capitals', function () {
            $generator = new TestGenerator();
            expect($generator->testGenerateSlug('HTTPRequest'))->toBe('h-t-t-p-request');
        });
        
        it('handles camelCase', function () {
            $generator = new TestGenerator();
            expect($generator->testGenerateSlug('createOrder'))->toBe('create-order');
        });
        
        it('preserves existing hyphens', function () {
            $generator = new TestGenerator();
            expect($generator->testGenerateSlug('create-order'))->toBe('create-order');
        });
    });
    
    describe('getStubPath()', function () {
        
        it('returns path to operation stub', function () {
            $generator = new TestGenerator();
            $path = $generator->testGetStubPath('operation');
            
            expect($path)->toContain('stubs' . DIRECTORY_SEPARATOR . 'operation.stub');
        });
        
        it('returns path to controller stub', function () {
            $generator = new TestGenerator();
            $path = $generator->testGetStubPath('controller-plain');
            
            expect($path)->toContain('stubs' . DIRECTORY_SEPARATOR . 'controller-plain.stub');
        });
        
        it('path uses correct directory separator', function () {
            $generator = new TestGenerator();
            $path = $generator->testGetStubPath('operation');
            
            // Path should use OS-specific separator
            expect($path)->toContain(DIRECTORY_SEPARATOR);
        });
        
        it('correctly navigates from Generators directory', function () {
            $generator = new TestGenerator();
            $path = $generator->testGetStubPath('operation');
            
            // Should go up one level (..) from Generators to src, then into stubs
            expect($path)->toContain('..' . DIRECTORY_SEPARATOR . 'stubs');
        });
        
        it('does not include .stub extension in parameter', function () {
            $generator = new TestGenerator();
            $path = $generator->testGetStubPath('operation');
            
            // Should add .stub extension automatically
            expect($path)->toEndWith('.stub');
        });
    });
    
    describe('getRelativePath()', function () {
        
        it('returns path relative to Laravel root', function () {
            $generator = new TestGenerator();
            // Since Pest.php does chdir($this->tempDir), getcwd() should return temp directory
            // and findLaravelRoot() should find it since it has composer.json and artisan
            $cwd = getcwd();
            
            // Verify we're in the temp directory
            expect($cwd)->toBe($this->tempDir);
            
            // Test that a path in temp gets made relative
            $absolutePath = $cwd . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Test.php';
            $relativePath = $generator->testGetRelativePath($absolutePath);
            
            // Should be relative to Laravel root (which is getcwd() in test context)
            expect($relativePath)->toBe('app' . DIRECTORY_SEPARATOR . 'Test.php');
        });
        
        it('handles nested service paths', function () {
            $generator = new TestGenerator();
            $cwd = getcwd();
            $absolutePath = $cwd . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'Order' . DIRECTORY_SEPARATOR . 'CreateOrder.php';
            
            $relativePath = $generator->testGetRelativePath($absolutePath);
            
            expect($relativePath)->toBe('app' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'Order' . DIRECTORY_SEPARATOR . 'CreateOrder.php');
            expect($relativePath)->toStartWith('app');
        });
        
        it('preserves directory separators', function () {
            $generator = new TestGenerator();
            $cwd = getcwd();
            $absolutePath = $cwd . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services';
            
            $relativePath = $generator->testGetRelativePath($absolutePath);
            
            expect($relativePath)->toContain(DIRECTORY_SEPARATOR);
        });
    });
    
    describe('loadStub()', function () {
        
        it('loads existing stub file', function () {
            $generator = new TestGenerator();
            $stubPath = $generator->testGetStubPath('operation');
            
            $content = $generator->testLoadStub($stubPath);
            
            expect($content)->toBeString();
            expect($content)->not->toBeEmpty();
            expect($content)->toContain('{{namespace}}');
        });
        
        it('throws StubNotFoundException for missing stub', function () {
            $generator = new TestGenerator();
            $fakePath = $this->tempDir . DIRECTORY_SEPARATOR . 'nonexistent.stub';
            
            expect(fn() => $generator->testLoadStub($fakePath))
                ->toThrow(StubNotFoundException::class);
        });
        
        it('returns complete stub content', function () {
            $generator = new TestGenerator();
            $stubPath = $generator->testGetStubPath('operation');
            
            $content = $generator->testLoadStub($stubPath);
            
            // Operation stub should have namespace and class placeholders
            expect($content)->toContain('{{namespace}}');
            expect($content)->toContain('{{name}}');
        });
    });
    
    describe('createDirectory()', function () {
        
        it('creates directory if it does not exist', function () {
            $generator = new TestGenerator();
            $dirPath = $this->tempDir . DIRECTORY_SEPARATOR . 'test-dir';
            
            $generator->testCreateDirectory($dirPath);
            
            expect(is_dir($dirPath))->toBeTrue();
        });
        
        it('does not error if directory already exists', function () {
            $generator = new TestGenerator();
            $dirPath = $this->tempDir . DIRECTORY_SEPARATOR . 'existing-dir';
            mkdir($dirPath);
            
            $generator->testCreateDirectory($dirPath);
            
            expect(is_dir($dirPath))->toBeTrue();
        });
        
        it('creates nested directories recursively', function () {
            $generator = new TestGenerator();
            $dirPath = $this->tempDir . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'level2' . DIRECTORY_SEPARATOR . 'level3';
            
            $generator->testCreateDirectory($dirPath);
            
            expect(is_dir($dirPath))->toBeTrue();
        });
        
        it('respects mode parameter', function () {
            $generator = new TestGenerator();
            $dirPath = $this->tempDir . DIRECTORY_SEPARATOR . 'mode-test';
            
            $generator->testCreateDirectory($dirPath, 0755);
            
            expect(is_dir($dirPath))->toBeTrue();
            // Check permissions (only on Unix-like systems)
            if (DIRECTORY_SEPARATOR === '/') {
                $perms = fileperms($dirPath) & 0777;
                expect($perms)->toBe(0755);
            }
        });
    });
    
    describe('createFile()', function () {
        
        it('creates file with content', function () {
            $generator = new TestGenerator();
            $filePath = $this->tempDir . DIRECTORY_SEPARATOR . 'test.txt';
            $content = 'Hello World';
            
            $generator->testCreateFile($filePath, $content);
            
            expect(file_exists($filePath))->toBeTrue();
            expect(file_get_contents($filePath))->toBe($content);
        });
        
        it('overwrites existing file', function () {
            $generator = new TestGenerator();
            $filePath = $this->tempDir . DIRECTORY_SEPARATOR . 'overwrite.txt';
            file_put_contents($filePath, 'Original');
            
            $generator->testCreateFile($filePath, 'Updated');
            
            expect(file_get_contents($filePath))->toBe('Updated');
        });
        
        it('handles empty content', function () {
            $generator = new TestGenerator();
            $filePath = $this->tempDir . DIRECTORY_SEPARATOR . 'empty.txt';
            
            $generator->testCreateFile($filePath, '');
            
            expect(file_exists($filePath))->toBeTrue();
            expect(file_get_contents($filePath))->toBe('');
        });
        
        it('creates PHP file with valid content', function () {
            $generator = new TestGenerator();
            $filePath = $this->tempDir . DIRECTORY_SEPARATOR . 'Test.php';
            $content = "<?php\n\nnamespace App;\n\nclass Test\n{\n}\n";
            
            $generator->testCreateFile($filePath, $content);
            
            expect(file_get_contents($filePath))->toBeValidPhp();
        });
    });
    
    describe('createRecursiveDirectories()', function () {
        
        it('creates nested directory structure', function () {
            $generator = new TestGenerator();
            $root = $this->tempDir;
            $elements = ['app', 'Services', 'Order', 'Modules'];
            
            $fullPath = $generator->testCreateRecursiveDirectories($root, $elements);
            
            expect(is_dir($fullPath))->toBeTrue();
            expect($fullPath)->toContain('app' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'Order' . DIRECTORY_SEPARATOR . 'Modules');
        });
        
        it('returns full path', function () {
            $generator = new TestGenerator();
            $root = $this->tempDir;
            $elements = ['level1', 'level2'];
            
            $fullPath = $generator->testCreateRecursiveDirectories($root, $elements);
            
            expect($fullPath)->toBe($root . DIRECTORY_SEPARATOR . 'level1' . DIRECTORY_SEPARATOR . 'level2');
        });
        
        it('handles empty elements array', function () {
            $generator = new TestGenerator();
            $root = $this->tempDir;
            
            $fullPath = $generator->testCreateRecursiveDirectories($root, []);
            
            expect($fullPath)->toBe($root);
        });
        
        it('handles single element', function () {
            $generator = new TestGenerator();
            $root = $this->tempDir;
            $elements = ['single'];
            
            $fullPath = $generator->testCreateRecursiveDirectories($root, $elements);
            
            expect(is_dir($fullPath))->toBeTrue();
            expect($fullPath)->toBe($root . DIRECTORY_SEPARATOR . 'single');
        });
    });
    
    describe('createGitkeep()', function () {
        
        it('creates .gitkeep file in directory', function () {
            $generator = new TestGenerator();
            $dirPath = $this->tempDir . DIRECTORY_SEPARATOR . 'git-test';
            mkdir($dirPath);
            
            $generator->testCreateGitkeep($dirPath);
            
            $gitkeepPath = $dirPath . DIRECTORY_SEPARATOR . '.gitkeep';
            expect(file_exists($gitkeepPath))->toBeTrue();
        });
        
        it('creates empty .gitkeep file', function () {
            $generator = new TestGenerator();
            $dirPath = $this->tempDir . DIRECTORY_SEPARATOR . 'git-test2';
            mkdir($dirPath);
            
            $generator->testCreateGitkeep($dirPath);
            
            $gitkeepPath = $dirPath . DIRECTORY_SEPARATOR . '.gitkeep';
            expect(file_get_contents($gitkeepPath))->toBe('');
        });
        
        it('does not overwrite existing .gitkeep', function () {
            $generator = new TestGenerator();
            $dirPath = $this->tempDir . DIRECTORY_SEPARATOR . 'git-test3';
            mkdir($dirPath);
            $gitkeepPath = $dirPath . DIRECTORY_SEPARATOR . '.gitkeep';
            file_put_contents($gitkeepPath, 'existing content');
            
            $generator->testCreateGitkeep($dirPath);
            
            expect(file_get_contents($gitkeepPath))->toBe('existing content');
        });
    });
    
    describe('fileExists()', function () {
        
        it('returns true for existing file', function () {
            $generator = new TestGenerator();
            $filePath = $this->tempDir . DIRECTORY_SEPARATOR . 'exists.txt';
            file_put_contents($filePath, 'content');
            
            expect($generator->testFileExists($filePath))->toBeTrue();
        });
        
        it('returns false for non-existent file', function () {
            $generator = new TestGenerator();
            $filePath = $this->tempDir . DIRECTORY_SEPARATOR . 'does-not-exist.txt';
            
            expect($generator->testFileExists($filePath))->toBeFalse();
        });
        
        it('returns true for existing directory', function () {
            $generator = new TestGenerator();
            $dirPath = $this->tempDir . DIRECTORY_SEPARATOR . 'existing-dir';
            mkdir($dirPath);
            
            expect($generator->testFileExists($dirPath))->toBeTrue();
        });
    });
});
