<?php

declare(strict_types=1);

namespace Franklinogf\LaravelUtils\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\ProgressBar;

use function count;
use function in_array;
use function is_array;

final class ExportLangKeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'utils:ts-lang-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate TypeScript translation keys from Laravel language files';

    private ProgressBar $progressBar;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $outputPath = resource_path(config()->string('utils.lang_keys.output_path', 'js/types/lang-keys.d.ts'));

        $this->info('Generating TypeScript translation keys...');
        $this->newLine(2);

        $translations = $this->getLangKeys();

        $this->newLine(2);

        $output = $this->generateTypeScriptDefinitions($translations);

        $outputDir = dirname($outputPath);
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        file_put_contents($outputPath, $output);

        $this->info("TypeScript definition written to: $outputPath");
        $this->info('Done! Found '.count($translations).' unique translation keys.');

    }

    /**
     * Get the translation keys from the language files.
     *
     * @return array<string, string>
     */
    private function getLangKeys(): array
    {

        $this->progressBar = $this->output->createProgressBar();
        $this->progressBar->setFormat(' [%bar%] %percent:3s%% | %current%/%max% | %message%');
        $this->progressBar->start();

        $phpKeys = $this->getPhpFilesKeys();

        $jsonKeys = $this->getJsonFilesKeys();
        /** @var array<string, string> $keys */
        $keys = array_merge($jsonKeys, $phpKeys);
        ksort($keys);
        $this->progressBar->setMessage('Finished processing files...');
        $this->progressBar->finish();

        return $keys;
    }

    /**
     * Get the translation keys from the JSON language file.
     *
     * @return array<string, string>
     */
    private function getJsonFilesKeys(): array
    {
        $mainLocale = config()->string('utils.lang_keys.main_locale', 'en');
        $jsonPath = lang_path("$mainLocale.json");
        if (! File::exists($jsonPath)) {
            $this->info('JSON language file not found.');

            return [];
        }

        $this->progressBar->setMessage('Processing JSON files...');
        $keys = [];

        $jsonFile = File::get($jsonPath);

        /**
         * @var string[] $json
         */
        $json = json_decode($jsonFile, true);
        $count = count(array_keys($json));
        $this->progressBar->setMaxSteps($this->progressBar->getMaxSteps() + $count);
        foreach ($json as $key => $value) {
            $keys[$key] = $value;
            $this->progressBar->advance();
        }

        /** @var array<string, string> $keys */
        return $keys;
    }

    /**
     * Get the translation keys from the PHP language files.
     *
     * @return array<string,string>
     */
    private function getPhpFilesKeys(): array
    {
        $this->progressBar->setMessage('Processing PHP files...');
        $keys = [];
        $separator = DIRECTORY_SEPARATOR;
        $excludedFiles = config()->array('utils.lang_keys.excluded_files', [
            'auth',
            'pagination',
            'passwords',
            'validation',
        ]);

        $langPath = lang_path(config()->string('utils.lang_keys.main_locale', 'en'));

        $files = File::glob("{$langPath}{$separator}*.php");
        /**
         * @var string[] $phpFiles
         */
        $phpFiles = collect($files)
            ->filter(fn (mixed $file): bool => is_string($file) && ! in_array(basename($file, '.php'), $excludedFiles))
            ->toArray();

        foreach ($phpFiles as $file) {
            $filename = basename($file, '.php');
            /**
             * @var array<string, array<string, string>|string> $translations
             */
            $translations = include $file;
            $count = count(array_keys($translations));
            $this->progressBar->setMaxSteps($this->progressBar->getMaxSteps() + $count);
            $this->flattenLang($translations, $filename, $keys);
        }

        return $keys;
    }

    /**
     * Recursively flatten the language array.
     *
     * @param  array<string, array<string, string>|string>  $array
     * @param  array<string, string>  $keys
     */
    private function flattenLang(array $array, string $prefix, array &$keys, string $parent = ''): void
    {
        /** @var array<string, string>|string $value */
        foreach ($array as $key => $value) {

            $fullKey = $parent !== '' && $parent !== '0' ? "$parent.$key" : "$prefix.$key";

            if (is_array($value)) {
                /** @var array<string, string> $value */
                $this->flattenLang($value, $prefix, $keys, $fullKey);
            } else {
                $keys[$fullKey] = (string) $value;
            }
            $this->progressBar->advance();
        }
    }

    /**
     * Generate TypeScript definitions from translations.
     *
     * @param  array<string, string>  $translations
     */
    private function generateTypeScriptDefinitions(array $translations): string
    {
        $typeName = config()->string('utils.lang_keys.type_name', 'TranslationKeys');
        $translationKeys = array_keys($translations);

        $output = "export type {$typeName} =\n";
        $output .= collect($translationKeys)
            ->map(fn (string $key): string => "  | '".str($key)->replace("'", "\\'")."'")
            ->implode("\n");
        $output .= ";\n\n";

        // Generate TranslationParams for translations with variables
        $output .= "export type TranslationParams = {\n";

        foreach ($translations as $key => $value) {
            $params = $this->extractTranslationParams($value);
            if ($params !== []) {
                $output .= "  '{$key}': { ";
                $output .= collect($params)
                    ->map(fn (string $param): string => "{$param}: string | number")
                    ->implode('; ');
                $output .= " };\n";
            }
        }

        $output .= "};\n\n";
        $output .= "export type TranslationWithParams = keyof TranslationParams;\n";

        return $output."export type TranslationWithoutParams = Exclude<{$typeName}, TranslationWithParams>;\n";
    }

    /**
     * Extract parameter names from a translation string.
     *
     * @return array<int, string>
     */
    private function extractTranslationParams(string $translation): array
    {
        // Match Laravel translation placeholders like :name, :count, etc.
        preg_match_all('/:(\w+)/', $translation, $matches);

        return array_unique($matches[1]);
    }
}
