# Contributing to the Symfony Twig HM Project

## Development Standards

### JSON Request Body Decoding

All Symfony controller actions that accept JSON request bodies **must** use the centralized helper:

```php
$data = $this->decodeJsonOr400($request);
if ($data instanceof JsonResponse) {
    return $data;
}
```

This ensures consistent error handling and returns HTTP 400 for malformed JSON. Direct calls to `json_decode($request->getContent(), true)` are prohibited in public endpoints.

Exceptions:
- Private helper methods (e.g., `getUserIdFromRequest`) may use inline `json_decode` with `json_last_error()` checks.
- The `BaseController` itself contains the `decodeJsonOr400` implementation.

### Static Analysis

We provide a script to detect violations:

```bash
php scripts/check_json_decode.php
```

This script is run automatically in the PHP CI pipeline. Ensure it passes before merging.

## Testing

- Run unit tests: `php php-symfony/vendor/bin/phpunit`
- Maintain or increase coverage. New code should include tests.
- All tests must pass; 0 failures, 0 errors, 0 warnings.

## Code Style

Follow PSR-12. Use PHP-CS-Fixer to check formatting:

```bash
cd php-symfony
vendor/bin/php-cs-fixer fix --dry-run --diff
```

CI will fail on style violations.

## Pull Request Process

1. Fork the repository and create a feature branch.
2. Ensure code follows standards and passes all CI checks.
3. Include unit tests for new logic.
4. Update documentation if needed.
5. Request review from maintainers.

## Architecture

- Keep controllers thin; delegate to domain/application services.
- Use dependency injection; avoid static calls.
- Follow hexagonal/clean architecture principles as described in `docs/ARCHITECTURE.md`.

Thank you for contributing!
