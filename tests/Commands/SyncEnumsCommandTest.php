<?php

declare(strict_types=1);

use Franklinogf\LaravelUtils\Commands\SyncEnumsCommand;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

beforeEach(function (): void {
    // Create necessary directories
    File::makeDirectory(app_path('Enums'), 0755, true, true);
    File::makeDirectory(resource_path('js/enums'), 0755, true, true);
});

afterEach(function (): void {
    // Clean up
    File::deleteDirectory(app_path('Enums'));
    File::deleteDirectory(resource_path('js/enums'));
});

it('generates TypeScript enums from PHP BackedEnums', function (): void {
    // Arrange
    File::put(app_path('Enums/Status.php'), <<<'PHP'
<?php
namespace App\Enums;

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}
PHP);

    /** @var TestCase $this */
    $this->artisan(SyncEnumsCommand::class)
        ->assertSuccessful()
        ->expectsOutput('Found 1 enum(s) to sync.')
        ->expectsOutput('✅ TypeScript enums synced!');

    // Assert
    $outputPath = resource_path('js/enums/Status.ts');
    expect(File::exists($outputPath))->toBeTrue();

    $content = File::get($outputPath);
    expect($content)->toContain('export enum Status')
        ->toContain("Active = 'active',")
        ->toContain("Inactive = 'inactive',")
        ->toContain("Pending = 'pending',")
        ->toContain('// Auto-generated from App\Enums\Status');
});

it('syncs multiple enums at once', function (): void {
    // Arrange
    File::makeDirectory(app_path('Enums'), 0755, true, true);
    File::put(app_path('Enums/Status.php'), <<<'PHP'
<?php
namespace App\Enums;

enum Status: string
{
    case Active = 'active';
}
PHP);

    File::put(app_path('Enums/Role.php'), <<<'PHP'
<?php
namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case User = 'user';
}
PHP);

    /** @var TestCase $this */
    $this->artisan(SyncEnumsCommand::class)
        ->assertSuccessful()
        ->expectsOutput('Found 2 enum(s) to sync.');

    // Assert
    expect(File::exists(resource_path('js/enums/Status.ts')))->toBeTrue();
    expect(File::exists(resource_path('js/enums/Role.ts')))->toBeTrue();

    $roleContent = File::get(resource_path('js/enums/Role.ts'));
    expect($roleContent)->toContain("Admin = 'admin',")
        ->toContain("User = 'user',");
});

it('syncs single enum when fileName argument is provided', function (): void {
    // Arrange
    File::put(app_path('Enums/Status.php'), <<<'PHP'
<?php
namespace App\Enums;

enum Status: string
{
    case Active = 'active';
}
PHP);

    File::put(app_path('Enums/Role.php'), <<<'PHP'
<?php
namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
}
PHP);

    /** @var TestCase $this */
    $this->artisan(SyncEnumsCommand::class, ['fileName' => 'Status.php'])
        ->assertSuccessful()
        ->expectsOutput('Found 1 enum(s) to sync.');

    // Assert
    expect(File::exists(resource_path('js/enums/Status.ts')))->toBeTrue();
    expect(File::exists(resource_path('js/enums/Role.ts')))->toBeFalse();
});

it('sync enums in a deeply nested directory structure', function (): void {
    // Arrange
    File::makeDirectory(app_path('Enums/Nested'), 0755, true, true);
    File::put(app_path('Enums/Nested/Status.php'), <<<'PHP'
<?php
namespace App\Enums\Nested;

enum Status: string
{
    case Active = 'active';
}
PHP);

    /** @var TestCase $this */
    $this->artisan(SyncEnumsCommand::class)
        ->assertSuccessful()
        ->expectsOutput('Found 1 enum(s) to sync.');

    // Assert
    expect(File::exists(resource_path('js/enums/nested/Status.ts')))->toBeTrue();

    $content = File::get(resource_path('js/enums/nested/Status.ts'));
    expect($content)->toContain('// Auto-generated from App\Enums\Nested\Status');
});

it('creates output directory if it does not exist', function (): void {
    // Arrange
    if (File::isDirectory(resource_path('js/enums'))) {
        File::deleteDirectory(resource_path('js/enums'));
    }
    File::put(app_path('Enums/Status.php'), <<<'PHP'
<?php
namespace App\Enums;

enum Status: string
{
    case Active = 'active';
}
PHP);

    /** @var TestCase $this */
    $this->artisan(SyncEnumsCommand::class)
        ->assertSuccessful();

    // Assert
    expect(File::isDirectory(resource_path('js/enums')))->toBeTrue();
    expect(File::exists(resource_path('js/enums/Status.ts')))->toBeTrue();
});

it('uses custom output path from config', function (): void {
    // Arrange
    config()->set('utils.enums.output_path', resource_path('custom/enums'));
    File::makeDirectory(resource_path('custom/enums'), 0755, true, true);
    File::put(app_path('Enums/Status.php'), <<<'PHP'
<?php
namespace App\Enums;

enum Status: string
{
    case Active = 'active';
}
PHP);

    /** @var TestCase $this */
    $this->artisan(SyncEnumsCommand::class)
        ->assertSuccessful();

    // Assert
    expect(File::exists(resource_path('custom/enums/Status.ts')))->toBeTrue();
});

it('handles enums with integer values', function (): void {
    // Arrange
    File::put(app_path('Enums/Priority.php'), <<<'PHP'
<?php
namespace App\Enums;

enum Priority: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
}
PHP);

    /** @var TestCase $this */
    $this->artisan(SyncEnumsCommand::class)
        ->assertSuccessful();

    // Assert
    $content = File::get(resource_path('js/enums/Priority.ts'));
    expect($content)->toContain("Low = '1',")
        ->toContain("Medium = '2',")
        ->toContain("High = '3',");
});

it('handles empty enums directory gracefully', function (): void {
    // Arrange
    File::deleteDirectory(app_path('Enums'));

    /** @var TestCase $this */
    $this->artisan(SyncEnumsCommand::class)
        ->assertSuccessful()
        ->expectsOutput('Found 0 enum(s) to sync.');
});

it('ignores non-PHP files in enums directory', function (): void {
    // Arrange
    File::put(app_path('Enums/Status.php'), <<<'PHP'
<?php
namespace App\Enums;

enum Status: string
{
    case Active = 'active';
}
PHP);
    File::put(app_path('Enums/readme.txt'), 'This is a readme');
    File::put(app_path('Enums/.gitkeep'), '');

    /** @var TestCase $this */
    $this->artisan(SyncEnumsCommand::class)
        ->assertSuccessful()
        ->expectsOutput('Found 1 enum(s) to sync.');

    // Assert
    expect(File::exists(resource_path('js/enums/Status.ts')))->toBeTrue();
    expect(File::exists(resource_path('js/enums/readme.ts')))->toBeFalse();
});

it('overwrites existing TypeScript enum files', function (): void {
    // Arrange
    File::put(app_path('Enums/Status.php'), <<<'PHP'
<?php
namespace App\Enums;

enum Status: string
{
    case Active = 'active';
}
PHP);

    $outputPath = resource_path('js/enums/Status.ts');
    File::put($outputPath, 'export enum Status { Old = "old" }');

    /** @var TestCase $this */
    $this->artisan(SyncEnumsCommand::class)
        ->assertSuccessful();

    // Assert
    $content = File::get($outputPath);
    expect($content)->not->toContain('Old')
        ->toContain("Active = 'active',");
});

it('ignores traits in enums directory', function (): void {
    // Arrange
    File::put(app_path('Enums/EnumTrait.php'), <<<'PHP'
<?php
namespace App\Enums;

trait EnumTrait
{
    public function getLabel(): string
    {
        return match($this) {
            default => '',
        };
    }
}
PHP);

    File::put(app_path('Enums/Status.php'), <<<'PHP'
<?php
namespace App\Enums;

enum Status: string
{
    case Active = 'active';
}
PHP);

    /** @var TestCase $this */
    $this->artisan(SyncEnumsCommand::class)
        ->assertSuccessful()
        ->expectsOutput('Found 1 enum(s) to sync.');

    // Assert
    expect(File::exists(resource_path('js/enums/Status.ts')))->toBeTrue();
    expect(File::exists(resource_path('js/enums/EnumTrait.ts')))->toBeFalse();
});

it('ignores classes in enums directory', function (): void {
    // Arrange
    File::put(app_path('Enums/Helper.php'), <<<'PHP'
<?php
namespace App\Enums;

class Helper
{
    public static function process(): void
    {
    }
}
PHP);

    File::put(app_path('Enums/Status.php'), <<<'PHP'
<?php
namespace App\Enums;

enum Status: string
{
    case Active = 'active';
}
PHP);

    /** @var TestCase $this */
    $this->artisan(SyncEnumsCommand::class)
        ->assertSuccessful()
        ->expectsOutput('Found 1 enum(s) to sync.');

    // Assert
    expect(File::exists(resource_path('js/enums/Status.ts')))->toBeTrue();
    expect(File::exists(resource_path('js/enums/Helper.ts')))->toBeFalse();
});
