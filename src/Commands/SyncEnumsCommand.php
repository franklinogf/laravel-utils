<?php

declare(strict_types=1);

namespace Franklinogf\LaravelUtils\Commands;

use BackedEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

final class SyncEnumsCommand extends Command
{
    protected $signature = 'utils:enums-sync {fileName?}';

    protected $description = 'Generate TypeScript enums from PHP enums';

    public function handle(): int
    {
        $fileName = $this->argument('fileName');
        if (! is_string($fileName) || $fileName === '') {
            $fileName = null;
        }
        $outputPath = config()->string('utils.enums.output_path', resource_path('js/enums'));

        $fileName = $fileName !== null ? Str::of($fileName)->trim()->whenEmpty(fn (): null => null) : null;

        $enumsPath = config()->string('utils.enums.input_path', app_path('Enums'));

        if (! File::isDirectory($enumsPath)) {
            $this->info('Found 0 enum(s) to sync.');

            return self::SUCCESS;
        }

        /**
         * @var array<int, array{className:string,namespace:class-string<BackedEnum>,relativePathForFile:string}> $enums
         */
        $enums = collect(File::allFiles($enumsPath))
            ->filter(fn (SplFileInfo $file): bool => $fileName !== null ? $file->getFilename() === $fileName->value() && str($file->getFilename())->endsWith('.php') : str($file->getFilename())->endsWith('.php'))
            ->map(function (SplFileInfo $file) use ($enumsPath): array {
                $className = $file->getFilenameWithoutExtension();
                $fullPath = $file->getRealPath();
                $namespace = $this->getNamespaceFromFile($file);

                // Get relative path to preserve directory structure
                $relativePath = str_replace($enumsPath.DIRECTORY_SEPARATOR, '', $fullPath);
                $relativePath = str_replace('.php', '', $relativePath);
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

                // Ensure forward slashes in relative path for consistent output
                $relativePathForFile = str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

                // Include the file to load the enum class
                require_once $fullPath;

                return [
                    'className' => $className,
                    'namespace' => $namespace,
                    'relativePathForFile' => $relativePathForFile,
                ];
            })
            ->filter(function (array $enumData): bool {
                $namespace = $enumData['namespace'];

                // Check if the namespace actually exists and is a BackedEnum
                if (! class_exists($namespace)) {
                    return false;
                }

                $reflection = new ReflectionClass($namespace);

                return $reflection->isEnum() && $reflection->implementsInterface(BackedEnum::class);
            })
            ->toArray();

        $this->info('Found '.count($enums).' enum(s) to sync.');

        if (! File::isDirectory($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        foreach ($enums as $enumData) {
            $className = $enumData['className'];
            $namespace = $enumData['namespace'];
            $relativePathForFile = $enumData['relativePathForFile'];

            $cases = collect($namespace::cases())
                ->map(function (BackedEnum $case): string {
                    $caseName = $case->name;
                    $caseValue = $case->value;

                    if (is_string($caseValue)) {
                        return "  {$caseName} = '{$caseValue}',";
                    }

                    return "  {$caseName} = {$caseValue},";
                })
                ->implode("\n");

            $ts = <<<TS
// Auto-generated from {$namespace}. DO NOT EDIT MANUALLY.
export enum {$className} {
{$cases}
}

TS;

            $outputFile = "{$outputPath}".DIRECTORY_SEPARATOR."{$relativePathForFile}.ts";
            $outputDir = dirname($outputFile);

            if (! File::isDirectory($outputDir)) {
                File::makeDirectory($outputDir, 0755, true);
            }

            file_put_contents($outputFile, $ts);
        }

        $this->info('✅ TypeScript enums synced!');

        return self::SUCCESS;
    }

    private function getNamespaceFromFile(SplFileInfo $file): string
    {
        $fullPath = $file->getRealPath();
        $relative = str($fullPath)
            ->after(app_path().DIRECTORY_SEPARATOR)
            ->ucfirst()
            ->replace(['/', '.php'], ['\\', ''])->value();

        return "App\\{$relative}";
    }
}
