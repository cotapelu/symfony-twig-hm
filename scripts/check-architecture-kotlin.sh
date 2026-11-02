#!/bin/bash
# Kotlin Architecture Compliance Check
# Checks if modules follow Api/Application/Domain/Infrastructure pattern

MODULES_DIR="kotlin/modules"
REPORT="docs/kotlin-architecture-violations.txt"

echo "=== KOTLIN ARCHITECTURE COMPLIANCE CHECK ===" > "$REPORT"
echo "Scanning modules in $MODULES_DIR..." | tee -a "$REPORT"
echo "" | tee -a "$REPORT"

total=0
compliant=0

for module_dir in "$MODULES_DIR"/*; do
    [ -d "$module_dir" ] || continue

    module_name=$(basename "$module_dir")
    # Skip sample and test modules
    if [[ "$module_name" =~ ^(sample|test|SampleModule)$ ]]; then
        continue
    fi

    total=$((total + 1))
    echo "Module: $module_name" >> "$REPORT"

    # Check for expected directories (case insensitive)
    has_api=false
    has_handler=false
    has_application=false
    has_domain=false
    has_infrastructure=false

    for item in "$module_dir"/*; do
        [ -e "$item" ] || break
        dir_name=$(basename "$item")
        case "$dir_name" in
            Api|api) has_api=true ;;
            Handler|handler) has_handler=true ;;
            Application|application) has_application=true ;;
            Domain|domain) has_domain=true ;;
            Infrastructure|infrastructure) has_infrastructure=true ;;
        esac
    done

    # Determine compliance: need at least Api/Handler + Application + Domain + Repository/Entity
    if { [ "$has_api" = true ] || [ "$has_handler" = true ]; } && [ "$has_application" = true ] && [ "$has_domain" = true ]; then
        echo "  ✓ COMPLIANT" >> "$REPORT"
        compliant=$((compliant + 1))
    else
        echo "  ⚠ NON-COMPLIANT" >> "$REPORT"
        [ "$has_api" = true ] && [ "$has_handler" = false ] && echo "    - has Api but no Handler? (or vice versa)" >> "$REPORT"
        [ "$has_application" = true ] && echo "    - Application: ✓" >> "$REPORT" || echo "    - Application: ✗" >> "$REPORT"
        [ "$has_domain" = true ] && echo "    - Domain: ✓" >> "$REPORT" || echo "    - Domain: ✗" >> "$REPORT"
        [ "$has_infrastructure" = true ] && echo "    - Infrastructure: ✓" >> "$REPORT"
    fi

    echo "" >> "$REPORT"
done

echo "=== SUMMARY ===" | tee -a "$REPORT"
echo "Total modules checked: $total" | tee -a "$REPORT"
echo "Compliant: $compliant" | tee -a "$REPORT"
echo "Need refactoring: $((total - compliant))" | tee -a "$REPORT"

if [ $total -gt 0 ]; then
    rate=$((compliant * 100 / total))
    echo "Compliance rate: ${rate}%" | tee -a "$REPORT"
fi

echo "" | tee -a "$REPORT"
echo "Recommendation: Migrate to Api/Application/Domain/Infrastructure pattern"
echo "See docs/ARCHITECTURE_DRIFT.md for action plan"
