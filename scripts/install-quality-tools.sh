#!/usr/bin/env bash
# Quality Tools Installation Script
# Installs all required quality gate tools for the full polyglot app

set -e  # Exit on error

echo "=== Installing Quality Gate Tools ==="

# PHP
echo "[PHP] Installing php-cs-fixer..."
cd php-symfony
composer require --dev friendsofphp/php-cs-fixer || echo "  ⚠️  php-cs-fixer already installed or failed"
cd ..

# Go
echo "[Go] Installing govulncheck..."
go install golang.org/x/vuln/cmd/govulncheck@latest

echo "[Go] Installing staticcheck..."
go install honnef.co/go/tools/cmd/staticcheck@latest

# Python
echo "[Python] Installing mypy, pylint, safety..."
pip install --user mypy pylint safety ruff

# Rust
echo "[Rust] Installing cargo-audit..."
cargo install cargo-audit

echo "[Rust] Installing cargo-tarpaulin..."
cargo install cargo-tarpaulin

# Kotlin (detekt, ktlint via gradle wrapper - already in build)
echo "[Kotlin] Ensuring gradle wrapper..."
cd kotlin
./gradlew wrapper --gradle-version 9.2.1 || echo "  ⚠️  Gradle wrapper already exists"
cd ..

echo ""
echo "✅ All quality tools installed!"
echo ""
echo "IMPORTANT:"
echo "- Add \$HOME/go/bin to your PATH if Go tools not found:"
echo "    export PATH=\"\$HOME/go/bin:\$PATH\""
echo "- Verify installation:"
echo "    which govulncheck staticcheck mypy pylint safety cargo-audit cargo-tarpaulin"
