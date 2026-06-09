<?php

namespace Froxlor\Core\Support;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

abstract class PackageServiceProvider extends ServiceProvider
{
    public function loadCommandsFrom(string $path): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        if (! is_dir($path)) {
            return;
        }

        $commands = [];

        foreach (File::allFiles($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $class = $this->classFromFile($file->getPathname());

            if (! $class) {
                continue;
            }

            if (is_subclass_of($class, Command::class)) {
                $commands[] = $class;
            }
        }

        if ($commands !== []) {
            $this->commands($commands);
        }
    }

    private function classFromFile(string $path): ?string
    {
        $source = @file_get_contents($path);

        if ($source === false) {
            return null;
        }

        $tokens = token_get_all($source);
        $namespace = '';
        $class = null;

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $namespace = '';
                $i++;

                while ($i < $count) {
                    $current = $tokens[$i];

                    if (is_array($current) && in_array($current[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                        $namespace .= $current[1];
                        $i++;
                        continue;
                    }

                    if ($current === ';' || $current === '{') {
                        break;
                    }

                    $i++;
                }

                continue;
            }

            if (is_array($token) && $token[0] === T_CLASS) {
                $previous = $this->previousNonWhitespaceToken($tokens, $i);

                if (is_array($previous) && $previous[0] === T_NEW) {
                    continue;
                }

                $i++;

                while ($i < $count) {
                    $current = $tokens[$i];

                    if (is_array($current) && $current[0] === T_STRING) {
                        $class = $current[1];
                        break 2;
                    }

                    $i++;
                }
            }
        }

        if (! $class) {
            return null;
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }

    private function previousNonWhitespaceToken(array $tokens, int $index): mixed
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_WHITESPACE) {
                continue;
            }

            return $token;
        }

        return null;
    }
}
