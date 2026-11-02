#!/bin/bash
# Simple PHP architecture check for BusinessAccounts module
# Checks if files follow expected layer patterns

MODULE="BusinessAccounts"
BASE_DIR="php-symfony/modules/$MODULE"

echo "=== PHP Architecture Check: $MODULE ==="

# Expected directories per layer
EXPECTED_API=("Controller" "DTO")
EXPECTED_APP=("Service")
EXPECTED_DOMAIN=("Entity" "Event" "ValueObject")
EXPECTED_INFRA=("Repository")

check_layer() {
    local layer=$1
    local dirs=("${@:2}")
    local missing=()

    for dir in "${dirs[@]}"; do
        if [ -d "$BASE_DIR/$dir" ]; then
            echo "  ✓ $layer/$dir exists"
        else
            echo "  ✗ $layer/$dir missing"
            missing+=("$dir")
        fi
    done

    if [ ${#missing[@]} -eq 0 ]; then
        return 0
    else
        return 1
    fi
}

check_dependency_violations() {
    echo ""
    echo "Checking for dependency violations..."

    # Check if Service imports Repository directly (should go through Domain interface)
    if [ -f "$BASE_DIR/Service/BusinessAccountsService.php" ]; then
        if grep -q "use.*Repository" "$BASE_DIR/Service/BusinessAccountsService.php"; then
            # Should use interface, not concrete
            echo "  ⚠ Service imports Repository - verify it's using interface"
        fi
    fi
}

check_layer "Api" "${EXPECTED_API[@]}"
check_layer "Application" "${EXPECTED_APP[@]}"
check_layer "Domain" "${EXPECTED_DOMAIN[@]}"
check_layer "Infrastructure" "${EXPECTED_INFRA[@]}"

check_dependency_violations

echo ""
echo "NOTE: Full architecture check requires deptrac with proper config."
echo "This script is a quick sanity check only."
