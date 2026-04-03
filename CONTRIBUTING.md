# Contributing to Lalog

Thank you for considering contributing to Lalog! This document provides guidelines and instructions for contributing.

## Bug Reports

When filing a bug report, please include:

1. Your PHP version (`php -v`)
2. Your Laravel version (`php artisan --version`)
3. Steps to reproduce the issue
4. Expected vs actual behavior
5. Relevant log output or error messages

## Development Setup

1. Fork and clone the repository:

```bash
git clone https://github.com/ByteTCore/lalog.git
cd lalog
```

2. Install dependencies:

```bash
composer install
```

3. Run tests to verify everything works:

```bash
composer test
```

## Pull Request Process

1. **Fork** the repository and create your branch from `main`
2. **Write tests** for any new functionality
3. **Ensure all tests pass** before submitting
4. **Follow PSR-12** coding standards
5. **Update CHANGELOG.md** with your changes under `[Unreleased]`
6. **Submit a Pull Request** with a clear description of the changes

## Coding Standards

This project follows **PSR-12** coding standards. Please ensure your code adheres to these standards.

You can check your code style with:

```bash
composer cs-check
```

## Testing

All new features and bug fixes should include tests. Run the test suite with:

```bash
composer test
```

## Commit Messages

Use clear, descriptive commit messages:

- `feat: add custom date format support`
- `fix: resolve file rotation when disk is full`
- `docs: update configuration examples`
- `test: add test for custom extension`

## Questions?

If you have questions about contributing, please open an issue on GitHub.
