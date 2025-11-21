<?php

declare(strict_types=1);

use Franklinogf\LaravelUtils\Commands\ExportLangKeysCommand;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

function cleanup(): void
{
    $langPath = lang_path('en');
    if (File::isDirectory($langPath)) {
        File::deleteDirectory($langPath);
    }

    $langJsonPath = lang_path('en.json');
    if (File::exists($langJsonPath)) {
        File::delete($langJsonPath);
    }

    $jsPath = resource_path('js/types');
    if (File::isDirectory($jsPath)) {
        File::deleteDirectory($jsPath);
    }

}
beforeEach(fn () => cleanup());

afterEach(fn () => cleanup());

it('generates TypeScript definitions from JSON language file', function (): void {
    // Arrange
    $jsonPath = lang_path('en.json');
    File::put($jsonPath, json_encode([
        'Welcome' => 'Welcome to our application',
        'Login' => 'Please log in',
        'Goodbye' => 'See you later',
    ]));

    /** @var TestCase $this */
    $this->artisan(ExportLangKeysCommand::class)
        ->assertSuccessful();

    // Assert
    $outputPath = resource_path('js/types/lang-keys.d.ts');
    expect(File::exists($outputPath))->toBeTrue();

    $content = File::get($outputPath);
    expect($content)->toContain('export type TranslationKeys =')
        ->toContain("| 'Welcome'")
        ->toContain("| 'Login'")
        ->toContain("| 'Goodbye'");
});

it('does not generate from json file if it does not exist', function (): void {
    // Arrange - no JSON file created

    /** @var TestCase $this */
    $this->artisan(ExportLangKeysCommand::class)
        ->assertSuccessful();

    // Assert - command should still succeed with 0 translations
    $outputPath = resource_path('js/types/lang-keys.d.ts');
    expect(File::exists($outputPath))->toBeTrue();

    $content = File::get($outputPath);

    expect($content)->toContain('export type TranslationKeys =')
        ->not->toContain("| '");
});

it('generates TypeScript definitions from PHP language files', function (): void {
    // Arrange
    File::makeDirectory(lang_path('en'), 0755, true, true);
    File::put(lang_path('en/messages.php'), <<<'PHP'
<?php
return [
    'welcome' => 'Welcome',
    'goodbye' => 'Goodbye',
    'nested' => [
        'key' => 'Nested value',
    ],
];
PHP);

    /** @var TestCase $this */
    $this->artisan(ExportLangKeysCommand::class)
        ->assertSuccessful();

    // Assert
    $outputPath = resource_path('js/types/lang-keys.d.ts');
    $content = File::get($outputPath);
    expect($content)->toContain("| 'messages.welcome'")
        ->toContain("| 'messages.goodbye'")
        ->toContain("| 'messages.nested.key'");
});

it('excludes configured files from PHP scanning', function (): void {
    // Arrange
    File::makeDirectory(lang_path('en'), 0755, true, true);
    File::put(lang_path('en/auth.php'), "<?php\nreturn ['failed' => 'Auth failed'];");
    File::put(lang_path('en/validation.php'), "<?php\nreturn ['required' => 'Required'];");
    File::put(lang_path('en/messages.php'), "<?php\nreturn ['welcome' => 'Welcome'];");

    /** @var TestCase $this */
    $this->artisan(ExportLangKeysCommand::class)
        ->assertSuccessful();

    // Assert
    $content = File::get(resource_path('js/types/lang-keys.d.ts'));
    expect($content)->not->toContain('auth.')
        ->not->toContain('validation.')
        ->toContain("| 'messages.welcome'");
});

it('extracts translation parameters correctly', function (): void {
    // Arrange
    File::put(lang_path('en.json'), json_encode([
        'greeting' => 'Hello :name, you have :count messages',
        'simple' => 'No parameters here',
    ]));

    /** @var TestCase $this */
    $this->artisan(ExportLangKeysCommand::class)
        ->assertSuccessful();

    // Assert
    $content = File::get(resource_path('js/types/lang-keys.d.ts'));
    expect($content)->toContain('export type TranslationParams')
        ->toContain("'greeting': { name: string | number; count: string | number }")
        ->not->toContain("'simple':");
});

it('uses custom output path from config', function (): void {
    // Arrange
    config()->set('utils.lang_keys.output_path', 'custom/path/translations.d.ts');
    File::put(lang_path('en.json'), json_encode(['test' => 'Test']));
    File::makeDirectory(resource_path('custom/path'), 0755, true, true);

    /** @var TestCase $this */
    $this->artisan(ExportLangKeysCommand::class)
        ->assertSuccessful();

    // Assert
    $customPath = resource_path('custom/path/translations.d.ts');
    expect(File::exists($customPath))->toBeTrue();
});

it('uses custom type name from config', function (): void {
    // Arrange
    config()->set('utils.lang_keys.type_name', 'CustomTranslationType');
    File::put(lang_path('en.json'), json_encode(['test' => 'Test']));

    /** @var TestCase $this */
    $this->artisan(ExportLangKeysCommand::class)
        ->assertSuccessful();

    // Assert
    $content = File::get(resource_path('js/types/lang-keys.d.ts'));
    expect($content)->toContain('export type CustomTranslationType =')
        ->toContain('Exclude<CustomTranslationType, TranslationWithParams>');
});

it('handles missing JSON file gracefully', function (): void {
    // Arrange - no JSON file created

    /** @var TestCase $this */
    $this->artisan(ExportLangKeysCommand::class)
        ->assertSuccessful();

    // Assert - command should still succeed with 0 translations
    expect(File::exists(resource_path('js/types/lang-keys.d.ts')))->toBeTrue();
});

it('merges JSON and PHP translations', function (): void {
    // Arrange
    File::put(lang_path('en.json'), json_encode(['json_key' => 'JSON value']));
    File::makeDirectory(lang_path('en'), 0755, true, true);
    File::put(lang_path('en/messages.php'), "<?php\nreturn ['php_key' => 'PHP value'];");

    /** @var TestCase $this */
    $this->artisan(ExportLangKeysCommand::class)
        ->assertSuccessful();

    // Assert
    $content = File::get(resource_path('js/types/lang-keys.d.ts'));
    expect($content)->toContain("| 'json_key'")
        ->toContain("| 'messages.php_key'");
});

it('escapes single quotes in translation keys', function (): void {
    // Arrange
    File::put(lang_path('en.json'), json_encode(["It's working" => 'Test']));

    /** @var TestCase $this */
    $this->artisan(ExportLangKeysCommand::class)
        ->assertSuccessful();

    // Assert
    $content = File::get(resource_path('js/types/lang-keys.d.ts'));
    expect($content)->toContain("| 'It\\'s working'");
});
