# Statamic Context CLI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kauffinger/statamic-context-cli.svg?style=flat-square)](https://packagist.org/packages/kauffinger/statamic-context-cli)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/kauffinger/statamic-context-cli/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/kauffinger/statamic-context-cli/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/kauffinger/statamic-context-cli/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/kauffinger/statamic-context-cli/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/kauffinger/statamic-context-cli.svg?style=flat-square)](https://packagist.org/packages/kauffinger/statamic-context-cli)

A Laravel package that provides fast, searchable access to Statamic and Statamic Peak documentation directly from your command line. Designed to work seamlessly with AI assistants like Claude Code for enhanced Statamic development workflows.

## Installation

You can install the package via composer:

```bash
composer require kauffinger/statamic-context-cli
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="statamic-context-cli-config"
```

## Usage

### Available Commands

View all available commands:

```bash
php artisan statamic-context
```

### Statamic Documentation

**Search Documentation:**
```bash
# Interactive search
php artisan statamic-context:docs:search --interactive

# Direct search
php artisan statamic-context:docs:search collections
php artisan statamic-context:docs:search blueprints
```

**Get Specific Documentation:**
```bash
php artisan statamic-context:docs:get core:collections
```

**Update Documentation Database:**
```bash
php artisan statamic-context:docs:update
```

### Statamic Peak Documentation

**Search Peak Documentation:**
```bash
# Interactive search
php artisan statamic-context:peak:search --interactive

# Direct search
php artisan statamic-context:peak:search page-builder
php artisan statamic-context:peak:search seo
```

**Get Specific Peak Documentation:**
```bash
php artisan statamic-context:peak:get features:page-builder
```

**Update Peak Documentation Database:**
```bash
php artisan statamic-context:peak:update
```

### Interactive Mode

Use the `--interactive` flag for a guided experience with:
- Search through documentation with real-time filtering
- Browse search results with pagination
- View full documentation content
- Seamless navigation between different actions

### Integration with Claude Code

This tool is designed to work with [Claude Code](https://claude.ai/code):

1. **Search for relevant documentation** using the CLI commands
2. **Copy the documentation content** from search results  
3. **Provide context to Claude Code** for more informed assistance

Example workflow:
```bash
# Search for information about collections
php artisan statamic-context:docs:search collections --interactive

# Use the documentation content to inform Claude Code about:
# - Collection configuration
# - Available field types
# - Templating patterns
# - Best practices
```

### Benefits

- **Fast Documentation Access**: Search thousands of docs instantly from your terminal
- **Offline-Ready**: Documentation is cached locally after initial update
- **Context for AI**: Provide accurate, up-to-date Statamic context to AI assistants
- **Dual Coverage**: Both core Statamic and Peak documentation included

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Konstantin Auffinger](https://github.com/62616071+kauffinger)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
