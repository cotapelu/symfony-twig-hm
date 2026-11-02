#!/usr/bin/env python3
"""Quality gate: Enforce production-readiness standards.
Runs critical checks and fails if any violation detected.
"""
import subprocess
import sys
import os

# Change to project root (scripts/ is in project root)
project_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
os.chdir(project_root)

print("Running quality gate checks...")

# 1. Run PHP unit tests (exclude integration - requires DB) and ensure they pass
print("[1/3] Running PHP unit tests (excluding integration)...")
result = subprocess.run(["make", "test-php-unit"], capture_output=False)
if result.returncode != 0:
    print("Quality gate failed: PHP unit tests did not pass")
    sys.exit(1)

# 2. TODO: Add security scan (composer audit, npm audit, etc.)
print("[2/3] Security scan (pending)")

# 3. TODO: Add architecture check (deptrac, import-linter)
print("[3/3] Architecture check (pending)")

print("Quality gate: OK")
sys.exit(0)
