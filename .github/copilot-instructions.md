# Laravel Utils Package - Copilot Instructions

## Project Overview

This is a Laravel package (`franklinogf/laravel-utils`) that provides utilities for TypeScript integration in Laravel projects. The package focuses on bridging PHP and TypeScript through automated code generation.

## Architecture & Structure

**Namespace**: `Franklinogf\LaravelUtils` (PSR-4 autoloaded from `src/`)

**Key Components**:
- **Service Provider**: `src/Providers/LaravelUtilsServiceProvider.php` - Publishes config to Laravel apps
- **Commands**: Located in `src/Providers/Commands/` (currently 2 commands)
  - `ExportLangKeys` - Generates TypeScript types from Laravel translation files
  - `SyncEnums` - Syncs PHP BackedEnums to TypeScript enums

**Important**: Commands are in `App\Console\Commands` namespace but should be in `Franklinogf\LaravelUtils\Console\Commands` for package consistency. This is a bug that needs fixing.

## Configuration

The package publishes `config/utils.php` which defines:
- `enums.output_path`: TypeScript enum output location (default: `js/enums`)

Users can customize via `php artisan vendor:publish` with the `config` tag.

**Planned Enhancements**:
- Add `lang_keys.output_path` to make translation type output configurable (currently hardcoded to `resources/js/types/lang-keys.d.ts`)
- Add `lang_keys.locales` array to support multiple languages beyond `en`
- Add `lang_keys.excluded_files` to make exclusion list configurable
- Add `enums.input_path` to support custom enum directories beyond `app/enums`

## Command Details

### `utils:ts-lang-keys`
- Scans `lang/en.json` and `lang/en/*.php` (excludes: auth, flash, pagination, passwords, permission, validation)
- Generates `resources/js/types/lang-keys.d.ts` with:
  - `TranslationKey` union type of all translation keys
  - `TranslationParams` mapping keys to their parameter types (extracts `:param` placeholders)
  - Helper types: `TranslationWithParams`, `TranslationWithoutParams`
- Uses recursive flattening for nested PHP translation arrays (e.g., `messages.welcome`)

### `enums:sync {fileName?}`
- Scans `app/enums/*.php` for BackedEnum classes
- Outputs to `resources/{config('utils.enums.output_path')}/*.ts`
- Generates TypeScript enums matching PHP enum cases
- Accepts optional `fileName` to sync a single enum file
- Creates output directory if missing

## Development Conventions

**PHP Standards**:
- PHP 8.3+ required
- Strict types (`declare(strict_types=1)`) in all files
- Final classes for commands
- Type hints on all methods (including void)
- PHPDoc blocks for arrays with generic annotations (`@var array<string, string>`)

**Code Style**:
- Laravel conventions for service providers
- Command signatures use `utils:` prefix
- Progress bars for long-running operations
- Informative console output with emojis (✅)

## Package Installation & Auto-Discovery

The package uses Laravel's auto-discovery via `composer.json`:
```json
"extra": {
    "laravel": {
        "providers": ["Franklinogf\\LaravelUtils\\LaravelUtilsServiceProvider"]
    }
}
```

## Testing

- PHPUnit configured in `phpunit.xml`
- Tests directory: `tests/` (currently empty)
- Source coverage: `./src`
- Run with: `vendor/bin/phpunit`


## When Adding Features

- New commands go in `src/Console/Commands/` with proper namespace
- Register commands in service provider's `boot()` method
- Use `utils:` prefix for command signatures
- Output to `resources/` directory by default (configurable)
- Support both JSON and PHP file formats where applicable
- Add progress bars for file scanning operations

## Contribution Guidelines

**Before Submitting PRs**:
1. Fix namespace issues - ensure all commands use `Franklinogf\LaravelUtils\Commands`
2. Add Pest tests for new features in `tests/` directory
3. Update `config/utils.php` if adding configurable options
4. Register new commands in `LaravelUtilsServiceProvider::boot()`
5. Follow existing code style: strict types, final classes, full type hints, PHPDoc generics

**Testing Locally**:
```bash
# Run tests
vendor/bin/phpunit

# Test in a Laravel app via composer path repository
composer config repositories.laravel-utils path ../path/to/laravel-utils
composer require franklinogf/laravel-utils:@dev
```

**Code Organization**:
- Commands: `src/Console/Commands/`
- Service Providers: `src/Providers/`
- Tests mirror `src/` structure in `tests/`
- Config files in `config/` (published to consuming apps)

## Release Process

**Versioning**: Follows SemVer (currently `0.0.1`)

**Before Release**:
1. Ensure all tests pass
2. Update version in `composer.json`
3. Tag release with `git tag v0.0.x`
4. Verify package auto-discovery works in fresh Laravel app

**Breaking Changes to Watch**:
- Changing command signatures
- Modifying TypeScript output structure
- Altering config file schema (requires migration guide)
