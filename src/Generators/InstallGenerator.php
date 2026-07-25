<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\UnexpectedBootstrapFileException;
use RuntimeException;

class InstallGenerator extends Generator
{
    private const COMMANDS_PATH = 'Pulsar/Services/*/Modules/*/Commands';

    private const EVENTS_PATH = 'Pulsar/Domain/*/Listeners';

    private const PROVIDER_CLASS = 'App\\Providers\\PulsarServiceProvider::class';

    /**
     * Create a new installer.
     */
    public function __construct(
        private readonly bool $dryRun = false,
        private readonly bool $force = false,
    ) {}

    /**
     * Generate the provider and wire it into a Laravel application.
     */
    public function generate(): InstallResult
    {
        $root = $this->findLaravelRoot();
        $providerPath = $root.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Providers'
            .DIRECTORY_SEPARATOR.'PulsarServiceProvider.php';
        $providersPath = $root.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'providers.php';
        $applicationPath = $root.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';

        $applicationBefore = $this->readBootstrapFile($applicationPath);
        $providersBefore = $this->readBootstrapFile($providersPath);
        $providerBefore = file_exists($providerPath) ? $this->readFile($providerPath) : null;

        // Plan and validate every mutation before writing anything.
        try {
            $applicationAfter = $this->patchApplicationBootstrap($applicationBefore, $applicationPath);
        } catch (UnexpectedBootstrapFileException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            throw UnexpectedBootstrapFileException::make(
                $this->relativeBootstrapPath($applicationPath),
                $exception->getMessage(),
            );
        }
        $providersAfter = $this->patchProvidersBootstrap($providersBefore, $providersPath);
        $providerAfter = $this->loadStub($this->getStubPath('pulsar-service-provider'));

        /** @var list<array{path: string, relative: string, before: string|null, after: string}> $changes */
        $changes = [];

        if ($providerBefore === null || ($this->force && $providerBefore !== $providerAfter)) {
            $changes[] = $this->change($root, $providerPath, $providerBefore, $providerAfter);
        }

        if ($providersBefore !== $providersAfter) {
            $changes[] = $this->change($root, $providersPath, $providersBefore, $providersAfter);
        }

        if ($applicationBefore !== $applicationAfter) {
            $changes[] = $this->change($root, $applicationPath, $applicationBefore, $applicationAfter);
        }

        $changedPaths = array_column($changes, 'relative');
        $diffs = [];

        foreach ($changes as $change) {
            $diffs[] = $this->renderDiff($change['relative'], $change['before'], $change['after']);
        }

        $diff = implode("\n", $diffs);

        if ($this->dryRun || $changes === []) {
            return new InstallResult($this->dryRun, $changedPaths, $diff);
        }

        $backupPath = null;

        foreach ($changes as $change) {
            $directory = dirname($change['path']);
            $this->createDirectory($directory);

            if ($change['path'] === $applicationPath) {
                $backupPath = $this->nextBackupPath($applicationPath);
                $this->createFile($backupPath, $applicationBefore);
            }

            $this->createFile($change['path'], $change['after']);
        }

        return new InstallResult(
            false,
            $changedPaths,
            $diff,
            $backupPath === null ? null : $this->getRelativePath($backupPath),
        );
    }

    /**
     * Build a normalized change record.
     *
     * @return array{path: string, relative: string, before: string|null, after: string}
     */
    private function change(string $root, string $path, ?string $before, string $after): array
    {
        return [
            'path' => $path,
            'relative' => str_replace($root.DIRECTORY_SEPARATOR, '', $path),
            'before' => $before,
            'after' => $after,
        ];
    }

    /**
     * Read a bootstrap file or fail with manual recovery instructions.
     */
    private function readBootstrapFile(string $path): string
    {
        if (! is_file($path)) {
            throw UnexpectedBootstrapFileException::make($this->relativeBootstrapPath($path), 'the file does not exist');
        }

        return $this->readFile($path);
    }

    /**
     * Read a file and fail loudly on an I/O error.
     */
    private function readFile(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read [{$path}].");
        }

        return $contents;
    }

    /**
     * Add Pulsar's provider to bootstrap/providers.php.
     */
    private function patchProvidersBootstrap(string $source, string $path): string
    {
        if (str_contains($source, self::PROVIDER_CLASS)) {
            return $source;
        }

        if (preg_match('/\A<\?php\b.*\breturn\s*\[.*\];\s*\z/s', $source) !== 1) {
            throw UnexpectedBootstrapFileException::make(
                $this->relativeBootstrapPath($path),
                'expected a PHP file returning a provider array',
            );
        }

        $closing = strrpos($source, '];');

        if ($closing === false) {
            throw UnexpectedBootstrapFileException::make(
                $this->relativeBootstrapPath($path),
                'could not find the provider array closing bracket',
            );
        }

        $beforeClosing = substr($source, 0, $closing);
        $separator = str_ends_with($beforeClosing, "\n") ? '' : "\n";

        return $beforeClosing.$separator.'    '.self::PROVIDER_CLASS.",\n".substr($source, $closing);
    }

    /**
     * Add or merge event and command discovery in bootstrap/app.php.
     */
    private function patchApplicationBootstrap(string $source, string $path): string
    {
        $chain = $this->parseApplicationChain($source, $path);
        $tokens = $chain['tokens'];
        $calls = $chain['calls'];

        /** @var list<array{start: int, length: int, replacement: string}> $replacements */
        $replacements = [];

        foreach ([
            ['method' => 'withEvents', 'argument' => 'discover', 'path' => self::EVENTS_PATH],
            ['method' => 'withCommands', 'argument' => 'commands', 'path' => self::COMMANDS_PATH],
        ] as $wiring) {
            $matching = array_values(array_filter(
                $calls,
                static fn (array $call): bool => $call['name'] === $wiring['method'],
            ));

            if (count($matching) > 1) {
                throw UnexpectedBootstrapFileException::make(
                    $this->relativeBootstrapPath($path),
                    "found more than one {$wiring['method']}() call",
                );
            }

            if ($matching === []) {
                continue;
            }

            $call = $matching[0];
            $callSource = substr(
                $source,
                $tokens[$call['open']]['end'],
                $tokens[$call['close']]['start'] - $tokens[$call['open']]['end'],
            );

            if ($this->containsWiring($callSource, $wiring['method'], $wiring['path'])) {
                continue;
            }

            $array = $this->findArrayArgument(
                $tokens,
                $call['open'],
                $call['close'],
                $wiring['argument'],
            );

            if ($array === null) {
                throw UnexpectedBootstrapFileException::make(
                    $this->relativeBootstrapPath($path),
                    "could not safely merge the existing {$wiring['method']}() arguments",
                );
            }

            $replacement = $this->appendArrayItem(
                $source,
                $tokens[$array['open']],
                $tokens[$array['close']],
                $wiring['method'] === 'withCommands'
                    ? "...(glob(app_path('{$wiring['path']}'), GLOB_ONLYDIR) ?: [])"
                    : "app_path('{$wiring['path']}')",
            );
            $replacements[] = [
                'start' => $tokens[$array['open']]['end'],
                'length' => $tokens[$array['close']]['start'] - $tokens[$array['open']]['end'],
                'replacement' => $replacement,
            ];
        }

        $missing = [];

        foreach (['withEvents', 'withCommands'] as $method) {
            $hasPath = $method === 'withEvents' ? self::EVENTS_PATH : self::COMMANDS_PATH;
            $call = array_values(array_filter(
                $calls,
                static fn (array $candidate): bool => $candidate['name'] === $method,
            ));

            if ($call === []) {
                $missing[] = $method;

                continue;
            }

            $callSource = substr(
                $source,
                $tokens[$call[0]['open']]['end'],
                $tokens[$call[0]['close']]['start'] - $tokens[$call[0]['open']]['end'],
            );

            if ($this->containsWiring($callSource, $method, $hasPath)) {
                continue;
            }
        }

        if ($missing !== []) {
            $createCall = array_values(array_filter(
                $calls,
                static fn (array $call): bool => $call['name'] === 'create',
            ))[0];
            $createStart = $tokens[$createCall['operator']]['start'];
            $lineStart = strrpos(substr($source, 0, $createStart), "\n");
            $createPrefix = substr(
                $source,
                $lineStart === false ? 0 : $lineStart + 1,
                $createStart - ($lineStart === false ? 0 : $lineStart + 1),
            );
            $indent = $this->chainIndent($source, $tokens, $calls);
            $blocks = [];

            foreach ($missing as $method) {
                $blocks[] = $method === 'withEvents'
                    ? "->withEvents(discover: [\n{$indent}    app_path('".self::EVENTS_PATH."'),\n{$indent}])"
                    : "->withCommands([\n{$indent}    ...(glob(app_path('".self::COMMANDS_PATH."'), GLOB_ONLYDIR) ?: []),\n{$indent}])";
            }

            $replacements[] = [
                'start' => $createStart,
                'length' => 0,
                'replacement' => (trim($createPrefix) === '' ? '' : "\n{$indent}")
                    .implode("\n{$indent}", $blocks)
                    ."\n{$indent}",
            ];
        }

        usort(
            $replacements,
            static fn (array $left, array $right): int => $right['start'] <=> $left['start'],
        );

        foreach ($replacements as $replacement) {
            $source = substr_replace(
                $source,
                $replacement['replacement'],
                $replacement['start'],
                $replacement['length'],
            );
        }

        return $source;
    }

    /**
     * Infer the indentation used by top-level configure-chain methods.
     *
     * @param  list<array{id: int|null, text: string, start: int, end: int}>  $tokens
     * @param  list<array{name: string, operator: int, open: int, close: int}>  $calls
     */
    private function chainIndent(string $source, array $tokens, array $calls): string
    {
        foreach ($calls as $call) {
            $operatorStart = $tokens[$call['operator']]['start'];
            $lineStart = strrpos(substr($source, 0, $operatorStart), "\n");
            $prefix = substr(
                $source,
                $lineStart === false ? 0 : $lineStart + 1,
                $operatorStart - ($lineStart === false ? 0 : $lineStart + 1),
            );

            if ($prefix !== '' && trim($prefix) === '') {
                return $prefix;
            }
        }

        return '    ';
    }

    /**
     * Parse the outer Application::configure() fluent chain.
     *
     * @return array{
     *     tokens: list<array{id: int|null, text: string, start: int, end: int}>,
     *     calls: list<array{name: string, operator: int, open: int, close: int}>
     * }
     */
    private function parseApplicationChain(string $source, string $path): array
    {
        $tokens = $this->tokenize($source);

        foreach ($tokens as $index => $token) {
            if (! in_array($token['id'], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
                || ($token['text'] !== 'Application' && ! str_ends_with($token['text'], '\\Application'))) {
                continue;
            }

            $doubleColon = $this->nextSignificant($tokens, $index + 1);
            $configure = $doubleColon === null ? null : $this->nextSignificant($tokens, $doubleColon + 1);
            $open = $configure === null ? null : $this->nextSignificant($tokens, $configure + 1);
            $return = $this->previousSignificant($tokens, $index - 1);

            if ($doubleColon === null || $configure === null || $open === null || $return === null
                || $tokens[$doubleColon]['text'] !== '::'
                || $tokens[$configure]['text'] !== 'configure'
                || $tokens[$open]['text'] !== '('
                || $tokens[$return]['id'] !== T_RETURN) {
                continue;
            }

            $configureClose = $this->matchingDelimiter($tokens, $open, '(', ')');
            $cursor = $this->nextSignificant($tokens, $configureClose + 1);
            $calls = [];

            while ($cursor !== null && $tokens[$cursor]['text'] === '->') {
                $method = $this->nextSignificant($tokens, $cursor + 1);
                $methodOpen = $method === null ? null : $this->nextSignificant($tokens, $method + 1);

                if ($method === null || $methodOpen === null
                    || $tokens[$method]['id'] !== T_STRING
                    || $tokens[$methodOpen]['text'] !== '(') {
                    throw UnexpectedBootstrapFileException::make(
                        $this->relativeBootstrapPath($path),
                        'the configure chain contains an unsupported method shape',
                    );
                }

                $methodClose = $this->matchingDelimiter($tokens, $methodOpen, '(', ')');
                $calls[] = [
                    'name' => $tokens[$method]['text'],
                    'operator' => $cursor,
                    'open' => $methodOpen,
                    'close' => $methodClose,
                ];
                $cursor = $this->nextSignificant($tokens, $methodClose + 1);
            }

            if ($calls === [] || $calls[array_key_last($calls)]['name'] !== 'create'
                || $cursor === null || $tokens[$cursor]['text'] !== ';') {
                throw UnexpectedBootstrapFileException::make(
                    $this->relativeBootstrapPath($path),
                    'expected the fluent chain to terminate with ->create();',
                );
            }

            $create = $calls[array_key_last($calls)];
            $createArguments = substr(
                $source,
                $tokens[$create['open']]['end'],
                $tokens[$create['close']]['start'] - $tokens[$create['open']]['end'],
            );

            if (trim($createArguments) !== '') {
                throw UnexpectedBootstrapFileException::make(
                    $this->relativeBootstrapPath($path),
                    'expected create() to have no arguments',
                );
            }

            return ['tokens' => $tokens, 'calls' => $calls];
        }

        throw UnexpectedBootstrapFileException::make(
            $this->relativeBootstrapPath($path),
            'expected return Application::configure(...)->create();',
        );
    }

    /**
     * Convert token_get_all output into offset-aware tokens.
     *
     * @return list<array{id: int|null, text: string, start: int, end: int}>
     */
    private function tokenize(string $source): array
    {
        $tokens = [];
        $offset = 0;

        foreach (token_get_all($source) as $token) {
            $id = is_array($token) ? $token[0] : null;
            $text = is_array($token) ? $token[1] : $token;
            $length = strlen($text);
            $tokens[] = [
                'id' => $id,
                'text' => $text,
                'start' => $offset,
                'end' => $offset + $length,
            ];
            $offset += $length;
        }

        return $tokens;
    }

    /**
     * Locate the next non-trivia token.
     *
     * @param  list<array{id: int|null, text: string, start: int, end: int}>  $tokens
     */
    private function nextSignificant(array $tokens, int $start): ?int
    {
        for ($index = $start, $count = count($tokens); $index < $count; $index++) {
            if (! $this->isTrivia($tokens[$index]['id'])) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Locate the previous non-trivia token.
     *
     * @param  list<array{id: int|null, text: string, start: int, end: int}>  $tokens
     */
    private function previousSignificant(array $tokens, int $start): ?int
    {
        for ($index = $start; $index >= 0; $index--) {
            if (! $this->isTrivia($tokens[$index]['id'])) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Determine whether a token is whitespace or a comment.
     */
    private function isTrivia(?int $id): bool
    {
        return in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
    }

    /**
     * Find a matching closing delimiter.
     *
     * @param  list<array{id: int|null, text: string, start: int, end: int}>  $tokens
     */
    private function matchingDelimiter(array $tokens, int $open, string $opening, string $closing): int
    {
        $depth = 0;

        for ($index = $open, $count = count($tokens); $index < $count; $index++) {
            if ($tokens[$index]['text'] === $opening) {
                $depth++;
            } elseif ($tokens[$index]['text'] === $closing) {
                $depth--;

                if ($depth === 0) {
                    return $index;
                }
            }
        }

        throw new RuntimeException("Unbalanced {$opening}{$closing} delimiters.");
    }

    /**
     * Find a supported array argument in a wiring method.
     *
     * @param  list<array{id: int|null, text: string, start: int, end: int}>  $tokens
     * @return array{open: int, close: int}|null
     */
    private function findArrayArgument(array $tokens, int $callOpen, int $callClose, string $argument): ?array
    {
        $first = $this->nextSignificant($tokens, $callOpen + 1);

        if ($first === null || $first >= $callClose) {
            return null;
        }

        if ($tokens[$first]['text'] === '[') {
            return [
                'open' => $first,
                'close' => $this->matchingDelimiter($tokens, $first, '[', ']'),
            ];
        }

        for ($index = $first; $index < $callClose; $index++) {
            if ($tokens[$index]['text'] !== $argument) {
                continue;
            }

            $colon = $this->nextSignificant($tokens, $index + 1);
            $array = $colon === null ? null : $this->nextSignificant($tokens, $colon + 1);

            if ($colon !== null && $array !== null
                && $tokens[$colon]['text'] === ':'
                && $tokens[$array]['text'] === '[') {
                return [
                    'open' => $array,
                    'close' => $this->matchingDelimiter($tokens, $array, '[', ']'),
                ];
            }
        }

        return null;
    }

    /**
     * Add an item immediately before an array's closing bracket.
     *
     * @param  array{id: int|null, text: string, start: int, end: int}  $open
     * @param  array{id: int|null, text: string, start: int, end: int}  $close
     */
    private function appendArrayItem(string $source, array $open, array $close, string $item): string
    {
        $inner = substr($source, $open['end'], $close['start'] - $open['end']);
        $lineStart = strrpos(substr($source, 0, $close['start']), "\n");
        $closingIndent = substr(
            $source,
            $lineStart === false ? 0 : $lineStart + 1,
            $close['start'] - ($lineStart === false ? 0 : $lineStart + 1),
        );

        if (trim($closingIndent) !== '') {
            $closingIndent = '    ';
        }

        $itemIndent = $closingIndent.'    ';

        if (trim($inner) === '') {
            return "\n{$itemIndent}{$item},\n{$closingIndent}";
        }

        $content = rtrim($inner);
        $comma = str_ends_with($content, ',') ? '' : ',';

        return "{$content}{$comma}\n{$itemIndent}{$item},\n{$closingIndent}";
    }

    /**
     * Detect the configured app_path regardless of quote style or spacing.
     */
    private function containsAppPath(string $source, string $path): bool
    {
        return preg_match(
            '/app_path\s*\(\s*([\'"])'.preg_quote($path, '/').'\\1\s*\)/',
            $source,
        ) === 1;
    }

    /**
     * Detect functional wiring; Laravel expands event globs but command paths need glob().
     */
    private function containsWiring(string $source, string $method, string $path): bool
    {
        if (! $this->containsAppPath($source, $path)) {
            return false;
        }

        if ($method !== 'withCommands') {
            return true;
        }

        return preg_match(
            '/glob\s*\(\s*app_path\s*\(\s*([\'"])'.preg_quote($path, '/').'\\1\s*\)\s*,\s*GLOB_ONLYDIR\s*\)/',
            $source,
        ) === 1;
    }

    /**
     * Select a non-destructive backup name.
     */
    private function nextBackupPath(string $applicationPath): string
    {
        $base = $applicationPath.'.pulsar.bak';

        if (! file_exists($base)) {
            return $base;
        }

        for ($suffix = 1; ; $suffix++) {
            $candidate = $base.'.'.$suffix;

            if (! file_exists($candidate)) {
                return $candidate;
            }
        }
    }

    /**
     * Render a valid, intentionally full-file unified diff.
     */
    private function renderDiff(string $path, ?string $before, string $after): string
    {
        $beforeLines = $before === null ? [] : explode("\n", rtrim($before, "\n"));
        $afterLines = explode("\n", rtrim($after, "\n"));
        $oldPath = $before === null ? '/dev/null' : "a/{$path}";

        $lines = [
            "--- {$oldPath}",
            "+++ b/{$path}",
            '@@ -1,'.count($beforeLines).' +1,'.count($afterLines).' @@',
        ];

        foreach ($beforeLines as $line) {
            $lines[] = '-'.$line;
        }

        foreach ($afterLines as $line) {
            $lines[] = '+'.$line;
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Present bootstrap paths relative to the Laravel root.
     */
    private function relativeBootstrapPath(string $path): string
    {
        return str_replace($this->findLaravelRoot().DIRECTORY_SEPARATOR, '', $path);
    }
}
