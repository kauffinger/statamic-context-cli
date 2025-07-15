# Statamic Context CLI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kauffinger/statamic-context-cli.svg?style=flat-square)](https://packagist.org/packages/kauffinger/statamic-context-cli)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/kauffinger/statamic-context-cli/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/kauffinger/statamic-context-cli/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/kauffinger/statamic-context-cli/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/kauffinger/statamic-context-cli/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/kauffinger/statamic-context-cli.svg?style=flat-square)](https://packagist.org/packages/kauffinger/statamic-context-cli)

A Laravel package that provides intelligent context generation for Statamic CMS development with AI assistants like Claude Code. This tool enables more informed and accurate assistance by providing relevant documentation, code examples, and contextual information about your Statamic project.

## Installation

You can install the package via composer:

```bash
composer require kauffinger/statamic-context-cli
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="statamic-context-cli-config"
```

## Usage with Claude Code

This package is designed to work seamlessly with [Claude Code](https://claude.ai/code) for agentic coding assistance. Here's how to use it:

### Basic Usage

Run the context generation command in your Statamic project:

```bash
php artisan statamic-context-cli
```

This command will:
- Analyze your Statamic project structure
- Generate relevant context about your fieldsets, blueprints, collections, and taxonomies
- Provide documentation links and code examples specific to your setup
- Output formatted context that Claude Code can use for more accurate assistance

### Integration with Claude Code

1. **Install the package** in your Statamic project
2. **Run the context command** when starting a coding session with Claude Code
3. **Share the generated context** with Claude Code to get more informed assistance

Example workflow:

```bash
# Generate context for your project
php artisan statamic-context-cli

# The output can be copied and shared with Claude Code
# for more contextual assistance with your Statamic development
```

### What Context is Generated

The tool provides context about:
- **Project Structure**: Your collections, taxonomies, and navigation
- **Blueprints & Fieldsets**: Available fields and their configurations  
- **Templates & Views**: Your template structure and available partials
- **Add-ons**: Installed Statamic add-ons and their capabilities
- **Configuration**: Relevant Statamic configuration options
- **Documentation**: Links to relevant Statamic documentation and Peak docs
- **Best Practices**: Statamic-specific coding patterns and conventions

### Benefits for Agentic Coding

- **Faster Development**: Claude Code understands your project structure immediately
- **Accurate Suggestions**: Context-aware recommendations based on your actual setup
- **Best Practices**: Guidance follows Statamic conventions and your project patterns
- **Error Prevention**: Avoid common mistakes by providing accurate field names, blueprint structures, etc.

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
