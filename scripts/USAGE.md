# Development Scripts Usage Guide

**Project:** Phường/Xã Super App
**Purpose:** Quick reference for running tests, coverage, and architecture checks across all backends.

---

## Quick Commands

### Test All Backends
```bash
make test
```

### Generate Coverage (All Backends)
```bash
make coverage
```

**Note:** PHP coverage requires Docker image with Xdebug. If not available, script will attempt to build it.

---

## Architecture Audit

### Scan All Backends (Quick Stats)
```bash
./scripts/audit-backends.sh
```
Output: Module counts, file counts, test counts, structure detection.

### Detailed Architecture Compliance Check
```bash
# Python (requires import-linter)
cd python && import-linter check

# PHP (deptrac - config in progress)
cd php-symfony && vendor/bin/deptrac analyse --config deptrac.yaml

# Go (custom script)
./scripts/check-architecture-go.sh

# Kotlin (custom script)
./scripts/check-architecture-kotlin.sh

# Rust (cargo clippy for code style, no architecture check yet)
cd rust && cargo clippy
```

### Compliance Dashboard (Generate)
```bash
python3 scripts/audit-architecture.py
```
Output: `docs/architecture-audit.json` with compliance stats per backend.

---

## Coverage Checks

### Individual Backend Coverage
```bash
# Go
cd go && go test ./... -coverprofile=coverage.out
bash ../scripts/check-go-coverage.sh coverage.out 80

# Python
cd python && pytest --cov --cov-report=xml:coverage.xml
python3 ../scripts/check-python-coverage.py coverage.xml 80

# Kotlin
cd kotlin && ./gradlew jacocoTestReport
python3 ../scripts/check-kotlin-coverage.py build/reports/jacoco/jacoco.xml 80

# PHP
cd php-symfony && php vendor/bin/phpunit --coverage-clover ../coverage/php-coverage.xml
php ../scripts/check-php-coverage.php ../coverage/php-coverage.xml 80
# Note: Requires Xdebug in PHP CLI

# Frontend
cd frontend && npm run test -- --coverage
node ../scripts/check-frontend-coverage.js  # if exists
```

---

## Security Auditing

```bash
# PHP
cd php-symfony && composer audit

# Frontend
cd frontend && npm audit

# Go
cd go && govulncheck ./...

# Python
cd python && pip install safety && safety check

# Kotlin (needs OWASP plugin)
cd kotlin && ./gradlew dependencyCheckAnalyze
```

---

## CI/CD Reference

All checks should run in GitHub Actions:

- `ci-php.yml` - PHP unit tests, PHPStan, deptrac
- `ci-go.yml` - Go tests, staticcheck, coverage
- `ci-python.yml` - pytest, mypy, import-linter
- `ci-kotlin.yml` - Gradle test, detekt, ArchUnit, JaCoCo
- `ci-rust.yml` - cargo test, clippy, fmt, tarpaulin
- `test-all-backends.yml` - orchestrates all above

---

## Adding New Modules

**Mandatory checklist:**

- [ ] Create module with standard structure: `Api/`, `Application/`, `Domain/`, `Infrastructure/`
- [ ] Write unit tests for domain and application layers (coverage ≥80%)
- [ ] Add integration tests for API endpoints
- [ ] Document API contract (OpenAPI/Swagger)
- [ ] Register module in AppCore (if applicable)
- [ ] Ensure no cross-module imports (use Domain contracts)
- [ ] Run architecture check locally before PR
- [ ] Verify coverage meets threshold

---

## Troubleshooting

### PHP coverage not generated
- Ensure Xdebug installed: `php -m | grep xdebug`
- Rebuild Docker image: `podman build -t phuongxa-php-cli-xdebug -f Dockerfile.php-cli .`
- Verify phpunit.xml has `<coverage>` section

### Go test fails with DB connection
- Set `DATABASE_URL` env var pointing to test database
- Use SQLite for unit tests: `sqlite:///:memory:`
- Run integration tests separately with proper DB

### Python import-linter errors
- Ensure module folder names are capitalized: `Api`, `Application`, `Domain`, `Infrastructure`
- Check `pyproject.toml` pattern matches your module structure
- Use `import-linter --debug` to see which import violated

### Kotlin ArchUnit tests failing
- Add `archunit-junit5` dependency to build.gradle.kts
- Place tests in `src/test/kotlin/architecture/`
- Use `@AnalyzeClasses(packages = ["com.phuongxa.modules.yourmodule"])`

---

## Contact & Escalation

- **Architecture questions:** See `docs/ARCHITECTURE_DRIFT.md`
- **Security issues:** See `docs/TODO.md` security section
- **Coverage problems:** See `docs/PROJECT_STATE.md`
- **Full audit:** See `docs/AUDIT.md`

---

*Keep this guide updated as workflows evolve.*
