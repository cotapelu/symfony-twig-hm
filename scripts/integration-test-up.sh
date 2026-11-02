#!/usr/bin/env bash
#
# Start the integration test environment
# Usage: ./scripts/integration-test-up.sh [--build] [--detach]
#
# Options:
#   --build       Force rebuild images
#   --detach      Run in background (detached mode)
#   --health-timeout  Time to wait for health checks (default: 180s)

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

BUILD_FLAG=""
DETACH_FLAG=""
HEALTH_TIMEOUT=180

while [[ $# -gt 0 ]]; do
  case $1 in
    --build)
      BUILD_FLAG="--build"
      shift
      ;;
    --detach)
      DETACH_FLAG="-d"
      shift
      ;;
    --health-timeout)
      HEALTH_TIMEOUT="$2"
      shift 2
      ;;
    *)
      echo "Unknown option: $1"
      echo "Usage: $0 [--build] [--detach] [--health-timeout SECONDS]"
      exit 1
      ;;
  esac
done

cd "$PROJECT_ROOT"

echo "🚀 Starting integration test environment..."
echo "   Build: ${BUILD_FLAG:-no}"
echo "   Detach: ${DETACH_FLAG:-no}"
echo "   Health timeout: ${HEALTH_TIMEOUT}s"

# Validate docker-compose file exists
if [ ! -f "docker-compose.integration.yml" ]; then
  echo "❌ docker-compose.integration.yml not found in project root"
  exit 1
fi

# Build and start services
echo ""
echo "📦 Building and starting services..."
docker compose -f docker-compose.integration.yml up $BUILD_FLAG $DETACH_FLAG

# Wait for all services to be healthy
if [ -z "$DETACH_FLAG" ]; then
  echo ""
  echo "⏳ Waiting for all services to become healthy (timeout: ${HEALTH_TIMEOUT}s)..."

  # Function to check service health
  check_service_health() {
    local service_name="$1"
    local max_attempts=$((HEALTH_TIMEOUT / 5))
    local attempt=1

    while [ $attempt -le $max_attempts ]; do
      if docker compose -f docker-compose.integration.yml ps | grep -q "$service_name.*healthy"; then
        echo "  ✅ $service_name is healthy"
        return 0
      fi
      echo -n "."
      sleep 5
      attempt=$((attempt + 1))
    done

    echo ""
    echo "  ❌ $service_name did not become healthy within ${HEALTH_TIMEOUT}s"
    return 1
  }

  # List of critical services to check
  SERVICES=(
    "postgres"
    "redis"
    "kafka"
    "rabbitmq"
    "php-symfony"
    "go-backend"
    "python-backend"
    "kotlin-backend"
    "rust-backend"
    "zig-backend"
    "frontend"
    "wiremock"
  )

  ALL_HEALTHY=true
  for service in "${SERVICES[@]}"; do
    if ! check_service_health "$service"; then
      ALL_HEALTHY=false
      echo ""
      echo "⚠️  Some services are not healthy. Check logs:"
      echo "   docker compose -f docker-compose.integration.yml logs $service"
    fi
  done

  echo ""
  if [ "$ALL_HEALTHY" = true ]; then
    echo "✅ All services are healthy!"
    echo ""
    echo "🧪 To run integration tests:"
    echo "   docker compose -f docker-compose.integration.yml run --rm test-runner"
    echo ""
    echo "📊 To view logs:"
    echo "   docker compose -f docker-compose.integration.yml logs -f"
    echo ""
    echo "🛑 To stop:"
    echo "   $0 --down"
  else
    echo "⚠️  Environment may not be fully ready. Check logs and retry if needed."
    exit 1
  fi
fi

echo ""
echo "✅ Integration environment startup complete!"
