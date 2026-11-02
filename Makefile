.PHONY: help test coverage clean quality integration-test-go integration-test-go

help:
	@echo "Phường/Xã Super App - Full Polyglot Quality Commands"
	@echo ""
	@echo "🚀 TESTING (all backends):"
	@echo "  make test                 - Run ALL tests (PHP, Go, Python, Kotlin, Rust, Zig, Frontend)"
	@echo "  make test-php             - Run PHP unit and integration tests"
	@echo "  make test-go             - Run Go tests"
	@echo "  make test-python         - Run Python tests"
	@echo "  make test-kotlin         - Run Kotlin tests"
	@echo "  make test-rust           - Run Rust tests"
	@echo "  make test-zig            - Run Zig tests"
	@echo "  make test-frontend       - Run frontend unit tests"
	@echo ""
	@echo "🔍 QUALITY GATES (per backend):"
	@echo "  make quality-php         - PHP: lint + phpstan + tests + coverage ≥80%"
	@echo "  make quality-go         - Go: vet + staticcheck + tests + coverage ≥80%"
	@echo "  make quality-python     - Python: mypy + lint + tests + coverage ≥80%"
	@echo "  make quality-kotlin     - Kotlin: detekt + ktlint + tests + jacoco ≥80%"
	@echo "  make quality-rust       - Rust: clippy + tests + tarpaulin coverage"
	@echo "  make quality-zig        - Zig: test + release-safe build"
	@echo "  make quality-frontend   - Frontend: eslint + type-check + tests + coverage ≥80%"
	@echo ""
	@echo "🛡️  CROSS-CUTTING:"
	@echo "  make quality-security   - Scan dependencies: composer audit, npm audit, govulncheck, safety, cargo audit"
	@echo "  make quality-architecture - Architecture compliance: deptrac, import-linter"
	@echo ""
	@echo "📊 COVERAGE (all backends):"
	@echo "  make coverage           - Generate coverage reports (PHP, Go, Python, Kotlin, Frontend + Rust/Zig if available)"
	@echo "  make coverage-php       - PHP coverage (Xdebug)"
	@echo "  make coverage-go       - Go coverage"
	@echo "  make coverage-python   - Python coverage"
	@echo "  make coverage-kotlin   - Kotlin coverage (Jacoco)"
	@echo "  make coverage-frontend - Frontend coverage (Istanbul)"
	@echo ""
	@echo "✅ FULL APPLICATION QUALITY:"
	@echo "  make quality           - Run ALL quality gates (all backends + security + architecture)"
	@echo "  make quality-dashboard - Generate quality dashboard"
	@echo "  make quality-daily     - Daily quality tracker"
	@echo "  make quality-weekly    - Weekly meta-analysis"
	@echo ""
	@echo "🧹 UTILITIES:"
	@echo "  make clean             - Remove temporary files and coverage reports"
	@echo "  make install-deps      - Install dependencies for all backends"

# Test commands
test-php:
	@echo "Running PHP tests..."
	cd php-symfony && php vendor/bin/phpunit -c phpunit.xml

test-php-unit:
	@echo "Running PHP unit tests (Unit suite only)..."
	cd php-symfony && php vendor/bin/phpunit -c phpunit.xml --testsuite Unit

test-go:
	@echo "Running Go tests..."
	cd go && go test ./...

integration-test-go:
	@echo "Running Go integration tests (requires PostgreSQL test DB)..."
	cd go && INTEGRATION_TEST_DATABASE_URL=postgres://test_user:test_pass@localhost:5433/community_test go test ./modules/Banking -v -run .
	@cd go && INTEGRATION_TEST_DATABASE_URL=postgres://test_user:test_pass@localhost:5433/community_test go test ./modules/BillsPayment -v -run .
	@cd go && INTEGRATION_TEST_DATABASE_URL=postgres://test_user:test_pass@localhost:5433/community_test go test ./modules/WalletService -v -run .
	@cd go && INTEGRATION_TEST_DATABASE_URL=postgres://test_user:test_pass@localhost:5433/community_test go test ./modules/QRPayment -v -run .
	@echo "Go integration tests complete"

test-python:
	@echo "Running Python tests..."
	cd python && pytest

test-kotlin:
	@echo "Running Kotlin tests..."
	cd kotlin && ./gradlew test

test-zig:
	@echo "Running Zig tests..."
	cd zig && zig test src/tests -R

test-rust:
	@echo "Running Rust tests..."
	cd rust && cargo test

test-frontend:
	@echo "Running frontend unit tests..."
	cd frontend && npm run test -- --run

test: test-php test-go test-python test-kotlin test-rust test-zig test-frontend integration-test-go

# Coverage commands
coverage-dir:
	@mkdir -p coverage

coverage-php: coverage-dir
	@echo "Generating PHP coverage (using Docker image with Xdebug)..."
	@if podman inspect localhost/phuongxa-php-cli-xdebug >/dev/null 2>&1; then \
		podman run --rm -v $(PWD):/app -w /app localhost/phuongxa-php-cli-xdebug bash -lc "cd php-symfony && php vendor/bin/phpunit -c phpunit.xml --coverage-clover ../coverage/php-coverage.xml --coverage-filter modules --coverage-html ../coverage/php-html"; \
	else \
		echo "Docker image localhost/phuongxa-php-cli-xdebug not found. Building..."; \
		podman build -t phuongxa-php-cli-xdebug -f Dockerfile.php-cli .; \
		podman run --rm -v $(PWD):/app -w /app localhost/phuongxa-php-cli-xdebug bash -lc "cd php-symfony && php vendor/bin/phpunit -c phpunit.xml --coverage-clover ../coverage/php-coverage.xml --coverage-filter modules --coverage-html ../coverage/php-html"; \
	fi
	@echo "Checking PHP coverage threshold..."
	php ../scripts/check-php-coverage.php ../coverage/php-coverage.xml 15
	@echo "PHP coverage written to coverage/php-coverage.xml and HTML in coverage/php-html"

coverage-go: coverage-dir
	@echo "Generating Go coverage for payment modules..."
	cd go && go test ./modules/Banking/... ./modules/BillsPayment/... ./modules/WalletService/... ./modules/QRPayment/... -coverprofile=../coverage/go-coverage.out
	@echo "Checking Go coverage threshold..."
	@bash $$(pwd)/scripts/check-go-coverage.sh $$(pwd)/coverage/go-coverage.out 20 || exit 1
	@go tool cover -html=coverage/go-coverage.out -o coverage/go-coverage.html
	@echo "Go coverage written to coverage/go-coverage.out and coverage/go-coverage.html"

coverage-python: coverage-dir
	@echo "Generating Python coverage..."
	cd python && pytest --cov --cov-report=xml:../coverage/python-coverage.xml --cov-report=html:../coverage/python-html
	@echo "Checking Python coverage threshold..."
	@python3 scripts/check-python-coverage.py ../coverage/python-coverage.xml
	@echo "Python coverage written to coverage/python-coverage.xml"

coverage-kotlin: coverage-dir
	@echo "Generating Kotlin coverage..."
	cd kotlin && ./gradlew clean test jacocoTestCoverageVerification jacocoTestReport
	@echo "Kotlin coverage report generated in kotlin/build/reports/jacoco"

coverage-frontend: coverage-dir
	@echo "Generating frontend coverage..."
	cd frontend && npm run test -- --run --coverage
	@echo "Checking frontend coverage threshold..."
	@node scripts/check-frontend-coverage.js
	@echo "Frontend coverage written to frontend/coverage (lcov)"

coverage: coverage-php coverage-go coverage-python coverage-kotlin coverage-frontend coverage-rust coverage-zig
	@echo "All coverage reports generated in coverage/ directory"

coverage-rust:
	@echo "Generating Rust coverage..."
	@which cargo-tarpaulin > /dev/null && cd rust && cargo tarpaulin --out Xml --output-dir ../coverage || echo "  ⚠️  cargo-tarpaulin not installed, skipping Rust coverage"

coverage-zig:
	@echo "Generating Zig coverage (if supported)..."
	@echo "  ⚠️  Zig coverage tooling not yet integrated"

test: test-php test-go test-python test-kotlin test-zig test-frontend integration-test-go

# === QUALITY COMMANDS ===

# Individual backend quality gates (scripts ensure proper commands)
quality-php:
	@chmod +x scripts/quality-gate-php
	@scripts/quality-gate-php

quality-go:
	@chmod +x scripts/quality-gate-go
	@scripts/quality-gate-go

quality-python:
	@chmod +x scripts/quality-gate-python
	@scripts/quality-gate-python

quality-kotlin:
	@chmod +x scripts/quality-gate-kotlin
	@scripts/quality-gate-kotlin

quality-rust:
	@chmod +x scripts/quality-gate-rust
	@scripts/quality-gate-rust

quality-zig:
	@chmod +x scripts/quality-gate-zig
	@scripts/quality-gate-zig

quality-frontend:
	@chmod +x scripts/quality-gate-frontend
	@scripts/quality-gate-frontend

# Cross-cutting concerns
quality-security:
	@chmod +x scripts/quality-gate-security
	@scripts/quality-gate-security

quality-architecture:
	@chmod +x scripts/quality-gate-architecture
	@scripts/quality-gate-architecture

# Full application quality gate (all backends)
quality: quality-php quality-go quality-python quality-kotlin quality-rust quality-zig quality-frontend quality-security quality-architecture
	@echo "✅ Full application quality gate PASSED"


quality-dashboard:
	@echo "Generating quality dashboard..."
	python3 scripts/quality-dashboard.py

quality-daily:
	@echo "Daily quality tracker..."
	python3 scripts/daily-tracker.py

quality-weekly:
	@echo "Weekly meta-analysis..."
	python3 scripts/weekly-analyzer.py

trend:
	@cat reports/quality-trend.json | python3 -m json.tool

clean:
	rm -rf coverage
	rm -rf php-symfony/var/test.db
	rm -rf python/.pytest_cache
	rm -rf go/coverage.out
	rm -rf kotlin/build
	rm -rf frontend/coverage
	rm -rf frontend/test-results
	rm -rf zig/.zig-cache
	@echo "Cleaned temporary files and coverage reports"

install-deps:
	@echo "Installing PHP dependencies..."
	cd php-symfony && composer install --no-interaction --prefer-dist
	@echo "Installing Go dependencies..."
	cd go && go mod download
	@echo "Installing Python dependencies..."
	cd python && pip install -r requirements.txt && pip install -e .
	@echo "Installing Kotlin dependencies..."
	cd kotlin && ./gradlew dependencies
	@echo "Installing frontend dependencies..."
	cd frontend && npm ci
	@echo "All runtime dependencies installed"

install-quality-tools:
	@echo "=== Installing Quality Gate Tools ==="
	@echo "[PHP] php-cs-fixer..."; cd php-symfony && composer require --dev friendsofphp/php-cs-fixer || true
	@echo "[Go] govulncheck..."; go install golang.org/x/vuln/cmd/govulncheck@latest
	@echo "[Go] staticcheck..."; go install honnef.co/go/tools/cmd/staticcheck@latest
	@echo "[Python] mypy..."; pip install mypy
	@echo "[Python] pylint..."; pip install pylint
	@echo "[Python] ruff (optional)..."; pip install ruff || true
	@echo "[Python] safety..."; pip install safety
	@echo "[Rust] cargo-audit..."; cargo install cargo-audit
	@echo "[Rust] cargo-tarpaulin..."; cargo install cargo-tarpaulin
	@echo "[Kotlin] detekt & ktlint (via gradle)..."
	@cd kotlin && ./gradlew wrapper --gradle-version 9.2.1
	@echo "All quality tools installed!"
	@echo ""
	@echo "NOTE: Add \(GOPATH/bin\) to your PATH if Go tools not found."

install-all: install-deps install-quality-tools
	@echo "✅ All dependencies and quality tools installed"
