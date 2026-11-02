#!/usr/bin/env bash
#
# Stop the integration test environment and clean up
# Usage: ./scripts/integration-test-down.sh [--volumes] [--all]
#
# Options:
#   --volumes   Also remove volumes (WARNING: data loss)
#   --all       Remove everything including networks

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

VOLUMES_FLAG=""
ALL_FLAG=""

while [[ $# -gt 0 ]]; do
  case $1 in
    --volumes)
      VOLUMES_FLAG="-v"
      shift
      ;;
    --all)
      ALL_FLAG="--all"
      shift
      ;;
    *)
      echo "Unknown option: $1"
      echo "Usage: $0 [--volumes] [--all]"
      exit 1
      ;;
  esac
done

cd "$PROJECT_ROOT"

echo "🛑 Stopping integration test environment..."
echo "   Remove volumes: ${VOLUMES_FLAG:-no}"
echo "   Remove all: ${ALL_FLAG:-no}"

if [ -f "docker-compose.integration.yml" ]; then
  docker compose -f docker-compose.integration.yml down $VOLUMES_FLAG $ALL_FLAG
  echo "✅ Environment stopped"
else
  echo "❌ docker-compose.integration.yml not found"
  exit 1
fi

# Additional cleanup
if [ "$VOLUMES_FLAG" = "-v" ]; then
  echo "🧹 Cleaning up coverage directories..."
  rm -rf coverage/php-html coverage/python-html coverage/frontend coverage/lcov.info coverage/*.out coverage/*.xml
  rm -rf go/coverage.out python/coverage.xml frontend/coverage
  echo "✅ Cleanup complete"
fi
