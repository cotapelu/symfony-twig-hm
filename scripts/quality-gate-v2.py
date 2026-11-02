#!/usr/bin/env python3
"""
GOAL.md Comprehensive Quality Gate v2
Enforces production-readiness standards across polyglot codebase.
Checks: Functions size, complexity, duplication, error handling, input validation,
secrets, coverage (≥80%), tests pass, architecture, security scanning.
"""

import os
import re
import sys
import json
import subprocess
import xml.etree.ElementTree as ET
from pathlib import Path
from datetime import datetime

# Project root
project_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
os.chdir(project_root)

# Configuration
COVERAGE_THRESHOLD = 80.0
MAX_FUNCTION_LINES = 20
MAX_COMPLEXITY = 10
MAX_DUPLICATION_LINES = 5

# Results
violations = []
metrics = {}
passed = []

def run(cmd, cwd=None):
    """Run command and return (returncode, stdout, stderr)"""
    result = subprocess.run(cmd, shell=True, cwd=cwd or project_root,
                          capture_output=True, text=True, timeout=120)
    return result.returncode, result.stdout, result.stderr

def check_test_results():
    """Check that all tests pass for each backend"""
    print("[1/10] Checking test results...")
    # For now, we only check PHP unit tests quickly
    if os.path.exists("php-symfony"):
        rc, out, err = run("make test-php-unit")
        if rc != 0:
            violations.append("PHP unit tests failing (see output)")
            return False
    passed.append("All unit tests passing (spot check)")
    return True

def check_coverage():
    """Check coverage thresholds from existing reports"""
    print("[2/10] Checking coverage thresholds...")
    coverage_data = {}
    all_ok = True

    # PHP coverage
    php_cov = Path("coverage/php-coverage.xml")
    if php_cov.exists():
        try:
            tree = ET.parse(php_cov)
            root = tree.getroot()
            # Find metrics element (Clover format)
            metrics_el = root.find('.//project/metrics')
            if metrics_el is None:
                metrics_el = root.find('metrics')
            if metrics_el is None:
                raise Exception('Could not find <metrics> element in coverage XML')
            statements = int(metrics_el.get('statements', 0))
            covered = int(metrics_el.get('coveredstatements', 0))
            if statements > 0:
                pct = (covered / statements) * 100
                coverage_data['php'] = pct
                if pct >= COVERAGE_THRESHOLD:
                    passed.append(f"PHP coverage: {pct:.1f}% (≥{COVERAGE_THRESHOLD}%)")
                else:
                    violations.append(f"PHP coverage {pct:.1f}% < {COVERAGE_THRESHOLD}%")
                    all_ok = False
        except Exception as e:
            violations.append(f"Failed to parse PHP coverage: {e}")
            all_ok = False
    else:
        violations.append("PHP coverage report not found")
        all_ok = False

    # Go coverage
    go_cov = Path("coverage/go-coverage.out")
    if go_cov.exists():
        rc, out, err = run("go tool cover -func=coverage/go-coverage.out")
        if rc == 0:
            lines = out.strip().split('\n')
            if lines:
                total_line = lines[-1]
                parts = total_line.split()
                if len(parts) >= 3:
                    pct_str = parts[2].rstrip('%')
                    try:
                        pct = float(pct_str)
                        coverage_data['go'] = pct
                        if pct >= COVERAGE_THRESHOLD:
                            passed.append(f"Go coverage: {pct:.1f}% (≥{COVERAGE_THRESHOLD}%)")
                        else:
                            violations.append(f"Go coverage {pct:.1f}% < {COVERAGE_THRESHOLD}%")
                            all_ok = False
                    except:
                        violations.append("Could not parse Go coverage total")
                        all_ok = False
        else:
            violations.append("Failed to run go tool cover")
            all_ok = False
    else:
        violations.append("Go coverage report not found")
        all_ok = False

    # Python coverage
    py_cov = Path("coverage/python-coverage.xml")
    if py_cov.exists():
        try:
            tree = ET.parse(py_cov)
            root = tree.getroot()
            metrics_el = root.find('.//metrics')
            if metrics_el is not None:
                line_rate = metrics_el.get('line-rate')
                if line_rate:
                    pct = float(line_rate) * 100
                    coverage_data['python'] = pct
                    if pct >= COVERAGE_THRESHOLD:
                        passed.append(f"Python coverage: {pct:.1f}% (≥{COVERAGE_THRESHOLD}%)")
                    else:
                        violations.append(f"Python coverage {pct:.1f}% < {COVERAGE_THRESHOLD}%")
                        all_ok = False
        except Exception as e:
            violations.append(f"Failed to parse Python coverage: {e}")
            all_ok = False
    else:
        violations.append("Python coverage report not found")
        all_ok = False

    metrics['coverage'] = coverage_data
    return all_ok

def check_duplication():
    """Check for code duplication > 5 lines using jscpd (via npx)"""
    print("[3/10] Checking code duplication...")
    # Use npx to run jscpd without global install
    cmd = "npx --yes jscpd --min-lines 5 --format 'python' --exclude '**/vendor/**' --exclude '**/node_modules/**' --exclude '**/coverage/**' --exclude '**/.git/**' ."
    rc, out, err = run(cmd)
    if rc != 0:
        # jscpd returns non-zero when duplicates are found OR if npx fails
        if 'EBADENGINE' in out or 'Cannot find package' in out:
            violations.append("Duplication scanner (jscpd) not available (npx error)")
            return False
        violations.append(f"Code duplication detected (jscpd):\n{out}")
        return False
    else:
        passed.append("No code duplication found (jscpd)")
        return True

def check_function_sizes():
    """Check for functions exceeding MAX_FUNCTION_LINES"""
    print("[4/10] Checking function sizes...")
    violations.append("Function size analysis not implemented - needs AST parser")
    return False

def check_complexity():
    """Check for cyclomatic complexity > MAX_COMPLEXITY"""
    print("[5/10] Checking complexity...")
    violations.append("Complexity analysis not implemented - needs PHPStan/Psalm/ruff")
    return False

def check_secrets():
    """Scan for hardcoded secrets"""
    print("[6/10] Scanning for hardcoded secrets...")
    patterns = [
        r'password\s*=\s*["\'][^"\']+["\']',
        r'api[_-]?key\s*=\s*["\'][^"\']+["\']',
        r'secret\s*=\s*["\'][^"\']+["\']',
        r'token\s*=\s*["\'][^"\']+["\']',
        r'HOOK_SECRET',
    ]
    found_any = False
    for pattern in patterns:
        rc, out, err = run(f"grep -r -n -E '{pattern}' --exclude-dir=node_modules --exclude-dir=vendor --exclude='*.min.js' .")
        if rc == 0:
            lines = [l for l in out.split('\n') if 'NO' not in l and '.env.example' not in l and 'test' not in l.lower()]
            if lines:
                violations.append(f"Potential secrets found: pattern '{pattern}' matches {len(lines)} locations")
                found_any = True
    if not found_any:
        passed.append("No obvious hardcoded secrets found")
    return not found_any

def check_security_scan():
    """Run security scans (composer audit, npm audit, etc.)"""
    print("[7/10] Running security scans...")
    if os.path.exists("php-symfony/composer.json"):
        rc, out, err = run("cd php-symfony && composer audit --no-dev")
        if rc != 0:
            violations.append("Composer audit failed or found vulnerabilities")
            return False
        else:
            passed.append("Composer audit: no known vulnerabilities")
    violations.append("Security scans for other languages not implemented")
    return False

def check_architecture():
    """Check layer boundaries with deptrac (import-linter pending)"""
    print("[8/10] Checking architecture...")
    # Run deptrac for layer analysis
    if os.path.exists("deptrac.yaml"):
        rc, out, err = run("deptrac analyze --config=deptrac.yaml")
        if rc != 0:
            violations.append("Deptrac layer violations:\n" + out)
            return False
        else:
            passed.append("Deptrac: clean")
            return True
    violations.append("Deptrak not configured")
    return False

def check_error_handling():
    """Check that all public APIs have error handling"""
    print("[9/10] Checking error handling coverage...")
    violations.append("Error handling analysis not implemented - needs coverage diff")
    return False

def check_input_validation():
    """Check that all external inputs are validated"""
    print("[10/10] Checking input validation...")
    violations.append("Input validation analysis not implemented")
    return False

def main():
    print("=" * 60)
    print("GOAL.md Quality Gate V2 - Comprehensive Assessment")
    print("=" * 60)

    check_test_results()
    check_coverage()
    check_duplication()
    check_function_sizes()
    check_complexity()
    check_secrets()
    check_security_scan()
    check_architecture()
    check_error_handling()
    check_input_validation()

    self_score = (len(passed) / 10) * 100

    print("\n" + "=" * 60)
    print("QUALITY GATE REPORT")
    print("=" * 60)
    print(f"\nSelf-Score: {self_score:.1f}/100 (Target: ≥90)")
    print(f"Passed Checks: {len(passed)}/10")
    print(f"Violations: {len(violations)}")

    if passed:
        print("\n✓ Passed:")
        for p in passed:
            print(f"  - {p}")

    if violations:
        print("\n✗ Violations:")
        for v in violations:
            print(f"  - {v}")
    else:
        print("\n✓ No violations detected (spot check only)")

    if metrics:
        print("\nMetrics:")
        for key, val in metrics.items():
            if isinstance(val, dict):
                print(f"  {key}:")
                for k2, v2 in val.items():
                    print(f"    {k2}: {v2:.1f}%")
            else:
                print(f"  {key}: {val}")

    if violations:
        print("\n❌ Quality gate FAILED")
        sys.exit(1)
    else:
        print("\n✅ Quality gate PASSED")
        sys.exit(0)

if __name__ == '__main__':
    main()