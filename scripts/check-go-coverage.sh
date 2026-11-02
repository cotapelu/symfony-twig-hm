#!/bin/bash
# Check Go coverage threshold
# Usage: check-go-coverage.sh <coverage.out> <threshold_percent>

COVERAGE_FILE="$1"
THRESHOLD="${2:-80}"

if [ ! -f "$COVERAGE_FILE" ]; then
    echo "Coverage file not found: $COVERAGE_FILE"
    exit 1
fi

# Extract coverage percentage from go tool cover
COVERAGE=$(go tool cover -func="$COVERAGE_FILE" | tail -1 | awk '{print $3}' | sed 's/%//')

echo "Go coverage: ${COVERAGE}% (threshold: ${THRESHOLD}%)"

# Compare using awk (no bc needed)
if awk -v cov="$COVERAGE" -v thr="$THRESHOLD" 'BEGIN {exit (cov >= thr) ? 0 : 1}'; then
    echo "Coverage OK"
    exit 0
else
    echo "Coverage below threshold!"
    exit 1
fi
