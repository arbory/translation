# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`arbory/translation` is a maintained fork of `waavi/translation` — a Laravel package (Laravel 10–13) that adds database and cache support to Laravel's translation system. The original `Waavi\Translation\` namespace is kept (`src/` → `Waavi\Translation\`, `tests/` → `Waavi\Translation\Test\`). Host apps install it by replacing Laravel's `Illuminate\Translation\TranslationServiceProvider` with `Waavi\Translation\TranslationServiceProvider`.

## Commands

```bash
composer install              # install dependencies
composer test                 # run the full test suite (vendor/bin/phpunit)
vendor/bin/phpunit tests/Loaders/CacheLoaderTest.php          # run one test file
vendor/bin/phpunit --filter it_returns_from_cache_if_hit     # run one test by name
```

There is no linter or CI configured.

## Architecture

The package is a drop-in replacement of Laravel's translation loader, structured around a loader chain assembled in `src/TranslationServiceProvider.php`:

- **Loaders** (`src/Loaders/`): the `translator.source` config value (`files` | `database` | `mixed` | `mixed_db`) determines which loaders compose the `translation.loader` singleton. `FileLoader` wraps Laravel's native file loader, `DatabaseLoader` reads via `TranslationRepository`, and `MixedLoader` merges two loaders with one taking priority (`mixed` = files win, `mixed_db` = database wins). If `translator.cache.enabled` is on, the whole chain is wrapped in `CacheLoader`, a decorator that falls back to the inner loader on cache miss. All extend the abstract `Loader`.
- **Cache** (`src/Cache/`): `RepositoryFactory` picks `TaggedRepository` when the configured cache store supports tags, otherwise `SimpleRepository`; both implement `CacheRepositoryInterface` and are bound as the `translation.cache.repository` singleton (exposed via the `TranslationCache` facade).
- **Repositories** (`src/Repositories/`): `LanguageRepository` and `TranslationRepository` are the intended public API over the `Language` and `Translation` Eloquent models — they run validation that direct model saves skip. Translations carry two important flags: `locked` (protected from being overwritten by `translator:load`; update via `updateAndLock`) and `unstable`/pending-review (set on all sibling translations when the default-locale text changes).
- **Artisan commands** (`src/Commands/`): `translator:load` imports lang files (including vendor files and subdirectories) into the database; `translator:flush` clears the translation cache.
- **URI localization**: `UriLocalizer` manipulates the locale segment in URLs. `TranslationMiddleware` (registered as the `localize` route middleware alias) redirects GET requests lacking a locale — checking session, then browser `Accept-Language`, then the default locale — and shares `currentLanguage`, `selectableLanguages`, and `altLocalizedUrls` with all views. A custom `Routes\ResourceRegistrar` is bound over Laravel's to keep the locale prefix out of resource route names.
- **Model attribute translation** (`src/Traits/`): the `Translatable` trait hijacks `getAttribute`/`setAttribute` for attributes listed in the model's `$translatableAttributes` array. Each translatable attribute requires a matching `{attribute}_translation` column; `TranslatableObserver` syncs translation entries on save/delete. Prefixing an attribute with `raw` returns the untranslated value.

Database tables (`translator_languages`, `translator_translations`) are created by migrations in `database/migrations/` on the connection named by `translator.connection`. All runtime behavior is configured in `config/translator.php` (env vars: `TRANSLATION_SOURCE`, `TRANSLATION_CACHE_ENABLED`, `TRANSLATION_CACHE_TIMEOUT`, `TRANSLATION_CACHE_SUFFIX`).

## Testing

Tests use Orchestra Testbench. `tests/TestCase.php` registers the service provider and facades, sets up an in-memory database with the package migrations, seeds `en` (English) and `es` (Spanish) languages, and registers test routes — many tests rely on those two seeded languages. Fixture language files live in `tests/lang/` and `tests/temp/`, both excluded from test discovery in `phpunit.xml`. Unit tests mock collaborators with Mockery (`Mockery::close()` in `tearDown`).
