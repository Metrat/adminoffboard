# Contributing to Admin Offboard

## How to Contribute

### Reporting Issues

1. Check if the issue already exists
2. Provide clear steps to reproduce
3. Include relevant logs and error messages
4. Mention your Nextcloud and PHP versions

### Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Run tests: `composer test`
5. Run code style check: `composer cs:check`
6. Fix code style: `composer cs:fix`
7. Commit with clear message
8. Push to your fork
9. Open a Pull Request

### Code Standards

- Follow PSR-12
- Use PHP 8.2+ features
- Use type hints
- Write unit tests for new features
- Keep backward compatibility

### Development Setup

```bash
# Clone the repository
git clone https://github.com/your-username/adminoffboard.git

# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Check code style
vendor/bin/phpcs --standard=PSR12 lib/ appinfo/

# Fix code style
vendor/bin/phpcbf --standard=PSR12 lib/ appinfo/