#!/bin/bash
# Check Kotlin JaCoCo coverage threshold
# Usage: check-kotlin-coverage.sh <jacoco.xml> <threshold_percent>

COVERAGE_FILE="$1"
THRESHOLD="${2:-80}"

if [ ! -f "$COVERAGE_FILE" ]; then
    echo "JaCoCo coverage file not found: $COVERAGE_FILE"
    echo "Expected: build/reports/jacoco/jacoco.xml"
    exit 1
fi

# Parse JaCoCo XML (Cobertura format)
# Look for <counter type="INSTRUCTION" missed="..." covered="..."/>
COVERAGE=$(xmllint --xpath "string(//counter[@type='INSTRUCTION']/@covered)" "$COVERAGE_FILE" 2>/dev/null)
TOTAL=$(xmllint --xpath "string(//counter[@type='INSTRUCTION']/@missed)" "$COVERAGE_FILE" 2>/dev/null)

if [ -z "$COVERAGE" ] || [ -z "$TOTAL" ]; then
    # Fallback: try to extract from summary
    echo "Could not parse JaCoCo XML directly. Checking line-rate..."
    LINE_RATE=$(xmllint --xpath "string(//counter[@type='LINE']/@covered)" "$COVERAGE_FILE" 2>/dev/null)
    LINE_TOTAL=$(xmllint --xpath "string(//counter[@type='LINE']/@missed)" "$COVERAGE_FILE" 2>/dev/null)

    if [ -n "$LINE_RATE" ] && [ -n "$LINE_TOTAL" ]; then
        TOTAL=$((LINE_RATE + LINE_TOTAL))
        COVERAGE=$LINE_RATE
    fi
fi

if [ -z "$COVERAGE" ] || [ -z "$TOTAL" ]; then
    echo "Failed to extract coverage from JaCoCo report"
    exit 1
fi

# Calculate percentage
PERCENTAGE=$(awk -v cov="$COVERAGE" -v tot="$TOTAL" 'BEGIN {printf "%.2f", (cov / tot) * 100}')

echo "Kotlin coverage: ${PERCENTAGE}% (threshold: ${THRESHOLD}%)"

if awk -v cov="$PERCENTAGE" -v thr="$THRESHOLD" 'BEGIN {exit (cov >= thr) ? 0 : 1}'; then
    echo "Coverage OK"
    exit 0
else
    echo "Coverage below threshold!"
    exit 1
fi
