#!/usr/bin/env bash
# Autonomous Agent Cycle Runner
# GOAL.md §2.1 & §6.2 - Runs every 2 hours via systemd timer or cron

set -euo pipefail

# Configuration
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG_FILE="$PROJECT_ROOT/logs/autonomous-cycle.log"
SESSION_ID="autonomous-evolution"
PI_CMD="${PI_CMD:-pi}"  # Allow override via env

# Ensure logs directory exists
mkdir -p "$(dirname "$LOG_FILE")"

# Function to log with timestamp
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

# Start cycle
log "=== Starting autonomous cycle ==="

# Change to project directory
cd "$PROJECT_ROOT"

# Run the agent in non-interactive mode with a prompt that triggers the full cycle
# The agent will auto-discover AGENTS.md and AUTO-CONTINUE.md and execute the workflow
if $PI_CMD --session-id "$SESSION_ID" --no-session -p "Begin autonomous evolution cycle. Follow GOAL.md framework: Scan → Detect violations → Plan → Implement → Test → Audit → Verify → Commit. Update docs/AGENT_METRICS.md, docs/AGENT_PROFILE.md, docs/EVOLUTION.md." >> "$LOG_FILE" 2>&1; then
    log "✅ Autonomous cycle completed successfully"
    exit 0
else
    log "❌ Autonomous cycle failed (exit code $?)"
    # Review failures manually
    exit 1
fi
