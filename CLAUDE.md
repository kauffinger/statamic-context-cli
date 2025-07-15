# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel package called `statamic-context-cli` that provides LLM context generation capabilities for Statamic CMS. The package follows Laravel package conventions and uses the Spatie package tools structure.

### Purpose
The goal of this package is to create a CLI tool that enables Claude Code to search through relevant context for Statamic development, including:
- Searching through Statamic documentation
- Providing contextual information for Statamic development tasks
- Enabling more informed assistance with Statamic-specific development questions

## Key Development Commands

### Testing
- `composer test` - Run all tests using Pest
- Use `vendor/bin/testbench` instead of `artisan` - this is a package

### Code Quality
- `composer format` - Format code (runs Rector and Pint)
- `composer types` - Run type checking with memory limit

### Package Development
- `composer prepare` - Discover packages for testbench
- `php artisan vendor:publish --tag="statamic-context-cli-config"` - Publish config
- `php artisan vendor:publish --tag="statamic-context-cli-migrations"` - Publish migrations
- `php artisan vendor:publish --tag="statamic-context-cli-views"` - Publish views

### Main Command
- `php artisan statamic-context-cli` - Run the main package command

## Architecture

### Core Structure
- **Main Class**: `StatamicContext\StatamicContext\StatamicContext` - Empty core class (to be implemented)
- **Service Provider**: `StatamicContextServiceProvider` - Registers package components
- **Command**: `StatamicContextCommand` - Artisan command interface
- **Facade**: `StatamicContext` - Laravel facade for easy access

### Namespace
All classes use the namespace `StatamicContext\StatamicContext\`

### Dependencies
- PHP 8.4+ required
- Laravel 10.0+ / 11.0+ / 12.0+ support
- Uses Spatie Laravel Package Tools for structure
- Testing with Pest framework
- Code quality tools: PHPStan, Rector, Pint

### Configuration
- Config file: `config/statamic-context-cli.php` (currently empty)
- Database migrations available
- Views can be published

## Testing Framework

The package uses Pest for testing with Orchestra Testbench:
- Base test class: `StatamicContext\StatamicContext\Tests\TestCase`
- Factory namespace: `StatamicContext\StatamicContext\Database\Factories\`
- Test database configured for SQLite testing

## Code Standards

- Uses `declare(strict_types=1)` in all PHP files
- Follows PSR-4 autoloading
- Uses PHP 8.4+ features including the `#[Override]` attribute
- Minimum stability: dev with prefer-stable

## Development Workflow

- After implementing new features or fixes, run:
  - `composer format` to format code
  - `composer types` to ensure type safety
  - `composer test` to run all tests
