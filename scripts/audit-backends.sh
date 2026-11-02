#!/bin/bash
# Quick audit script for all backends
# Reports module count, file count, test count, basic structure

echo "=== POLYGLOT BACKEND AUDIT ==="
echo ""

audit_dir() {
    local dir=$1
    local name=$2
    echo "## $name"
    if [ -d "$dir" ]; then
        local modules=$(find "$dir" -maxdepth 1 -type d | tail -n +2 | wc -l)
        local files=$(find "$dir" -type f | wc -l)
        local tests=$(find "$dir" -name "*test*" -o -name "*Test*" | wc -l)
        echo "- Modules: $modules"
        echo "- Files: $files"
        echo "- Test files: $tests"
        # Check for architecture markers
        if [ -d "$dir/Api" ] || [ -d "$dir/Application" ] || [ -d "$dir/Domain" ]; then
            echo "- Structure: Api/Application/Domain/Infrastructure ✅"
        else
            echo "- Structure: Non-standard ⚠️"
        fi
    else
        echo "- NOT FOUND"
    fi
    echo ""
}

audit_dir "php-symfony/modules" "PHP Symfony Modules"
audit_dir "go/modules" "Go Modules"
audit_dir "python/modules" "Python Modules"
audit_dir "kotlin/modules" "Kotlin Modules"
audit_dir "rust/modules" "Rust Modules"
audit_dir "zig/modules" "Zig Modules"
audit_dir "frontend/src/modules" "Frontend Modules"

echo "=== SUMMARY ==="
echo "Total code files:"
find . -type f \( -name "*.php" -o -name "*.go" -o -name "*.py" -o -name "*.kt" -o -name "*.rs" -o -name "*.zig" -o -name "*.ts" -o -name "*.js" \) | wc -l
