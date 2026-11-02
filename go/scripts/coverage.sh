#!/bin/bash
# Go test coverage script

echo "Running Go tests with coverage..."

# Clean previous coverage data
rm -f coverage.out coverage.html coverage.txt

# Run tests with coverage
go test -v -coverprofile=coverage.out -covermode=atomic ./...

# Generate coverage report in HTML format
go tool cover -html=coverage.out -o coverage.html

# Generate coverage report in text format
go tool cover -func=coverage.out > coverage.txt

# Display overall coverage percentage
TOTAL_COVERAGE=$(go tool cover -func=coverage.out | grep total | awk '{print $3}')
echo "Total Coverage: $TOTAL_COVERAGE"

# Generate coverage profile for specific packages
go tool cover -func=coverage.out | grep -E "^modules/|^app/|^cmd/" | awk '{print $1, $3}'

echo ""
echo "Coverage reports generated:"
echo "  - coverage.out (raw data)"
echo "  - coverage.html (HTML report)"
echo "  - coverage.txt (text summary)"
