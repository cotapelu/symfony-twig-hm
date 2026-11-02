#!/bin/bash
# Go Architecture Compliance Checker
# Checks if Go modules follow Api/Application/Domain/Infrastructure pattern

set -e

MODULES_DIR="go/modules"
REPORT="docs/go-architecture-violations.txt"

echo "=== GO ARCHITECTURE COMPLIANCE CHECK ===" > "$REPORT"
echo "Scanning modules in $MODULES_DIR..." | tee -a "$REPORT"
echo "" | tee -a "$REPORT"

total_modules=0
compliant=0
violations=0

# List of high-value modules to check thoroughly
HIGH_VALUE_MODULES=(
    "Banking"
    "BillsPayment"
    "WalletService"
    "QRPayment"
    "authservice"
    "citizen"
    "notifications"
)

check_module() {
    local module=$1
    local module_path="$MODULES_DIR/$module"

    if [ ! -d "$module_path" ]; then
        return
    fi

    total_modules=$((total_modules + 1))
    echo "Module: $module" >> "$REPORT"

    # Check for expected directories
    has_api=false
    has_handler=false
    has_service=false
    has_domain=false
    has_internal=false
    has_repository=false

    if [ -d "$module_path/handler" ] || [ -d "$module_path/api" ]; then
        has_api=true
        has_handler=true
    fi
    if [ -d "$module_path/service" ]; then
        has_service=true
    fi
    if [ -d "$module_path/internal" ] && [ -d "$module_path/internal/models" ]; then
        has_domain=true
    fi
    if [ -d "$module_path/repository" ]; then
        has_repository=true
    fi

    # Determine compliance
    if [ "$has_handler" = true ] && [ "$has_service" = true ] && [ "$has_domain" = true ] && [ "$has_repository" = true ]; then
        echo "  ✓ COMPLIANT: handler, service, internal/models, repository" >> "$REPORT"
        compliant=$((compliant + 1))
    else
        echo "  ⚠ PARTIAL:" >> "$REPORT"
        [ "$has_handler" = true ] && echo "    - handler/api" >> "$REPORT"
        [ "$has_service" = true ] && echo "    - service" >> "$REPORT"
        [ "$has_domain" = true ] && echo "    - internal/models (domain)" >> "$REPORT"
        [ "$has_repository" = true ] && echo "    - repository" >> "$REPORT"
        violations=$((violations + 1))
    fi

    # Check for import violations: handler importing repository directly
    if [ -f "$module_path/handler/handlers.go" ]; then
        if grep -q "repository" "$module_path/handler/handlers.go"; then
            # Handler should only use service interface, not repository directly
            echo "  ⚠ Handler imports repository - should use service interface" >> "$REPORT"
        fi
    fi

    echo "" >> "$REPORT"
}

# Check all modules first
echo "Scanning all modules..." | tee -a "$REPORT"
for module_dir in "$MODULES_DIR"/*; do
    if [ -d "$module_dir" ]; then
        module_name=$(basename "$module_dir")
        # Skip non-business modules
        if [[ "$module_name" == "testing" || "$module_name" == "sample_module" || "$module_name" == "SampleModule" ]]; then
            continue
        fi
        check_module "$module_name"
    fi
done

# Then detailed check high-value modules
echo "=== HIGH-VALUE MODULES DETAILED CHECK ===" | tee -a "$REPORT"
echo "" | tee -a "$REPORT"
for module in "${HIGH_VALUE_MODULES[@]}"; do
    check_module "$module"
done

# Summary
echo "=== SUMMARY ===" | tee -a "$REPORT"
echo "Total modules checked: $total_modules" | tee -a "$REPORT"
echo "Compliant: $compliant" | tee -a "$REPORT"
echo "Need refactoring: $violations" | tee -a "$REPORT"

if [ $total_modules -gt 0 ]; then
    compliance_rate=$((compliant * 100 / total_modules))
    echo "Compliance rate: ${compliance_rate}%" | tee -a "$REPORT"
fi

echo "" | tee -a "$REPORT"
echo "Detailed violations saved to $REPORT"
echo "Next steps: Refactor non-compliant modules to Api/Application/Domain/Infrastructure pattern"
