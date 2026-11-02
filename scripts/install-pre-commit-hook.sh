#!/usr/bin/env bash
# Install pre-commit hook to enforce quality gate locally

set -e

echo "=== Installing Pre-commit Hook ==="

# Check if pre-commit is installed
if ! command -v pre-commit &> /dev/null; then
    echo "pre-commit is not installed. Installing..."
    pip install --user pre-commit || {
        echo "❌ Failed to install pre-commit. Install manually: pip install pre-commit"
        exit 1
    }
    export PATH="$HOME/.local/bin:$PATH"
fi

# Install the git hook(s)
echo "Installing pre-commit hooks..."
pre-commit install

# Optional: install for all git hooks (commit, push, etc.)
read -p "Also install for pre-push hook? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    pre-commit install --hook-type pre-push
fi

echo "✅ Pre-commit hook installed!"
echo ""
echo "Configuration:"
echo "  - Quality gate (make quality) runs on every commit/push"
echo "  - Additional linters run on relevant files"
echo ""
echo "To run manually: pre-commit run --all-files"
echo "To skip: git commit --no-verify"
echo ""
echo "Note: If quality gate fails, commit will be aborted."
echo "      Fix issues or use '--no-verify' to skip (not recommended)."
