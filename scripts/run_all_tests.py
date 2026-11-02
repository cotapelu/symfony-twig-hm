#!/usr/bin/env python3
"""
Integration Test Runner - Cross-Module Testing

This script orchestrates test execution across all backend services
in the Docker Compose integration environment.

Usage:
  python scripts/run_all_tests.py [--coverage] [--fail-fast] [--skip-frontend] [--skip-backend]

Options:
  --coverage      Generate and collect coverage reports from all services (default: True)
  --fail-fast     Stop at first test failure (default: False)
  --skip-frontend Skip frontend tests
  --skip-backend  Skip backend tests
"""

import os
import sys
import subprocess
import json
import argparse
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Tuple, Optional

# Configuration
SERVICES = {
    'php': {
        'name': 'PHP Symfony',
        'test_cmd': ['make', 'test-php-unit'],
        'coverage_cmd': ['make', 'coverage-php'],
        'coverage_threshold': 15.0,
        'coverage_file': 'coverage/php-coverage.xml',
        'workdir': '.'
    },
    'go': {
        'name': 'Go',
        'test_cmd': ['make', 'test-go'],
        'coverage_cmd': ['make', 'coverage-go'],
        'coverage_threshold': 20.0,
        'coverage_file': 'coverage/go-coverage.out',
        'workdir': '.'
    },
    'python': {
        'name': 'Python',
        'test_cmd': ['make', 'test-python'],
        'coverage_cmd': ['make', 'coverage-python'],
        'coverage_threshold': 80.0,
        'coverage_file': 'coverage/python-coverage.xml',
        'workdir': '.'
    },
    'kotlin': {
        'name': 'Kotlin',
        'test_cmd': ['make', 'test-kotlin'],
        'coverage_cmd': ['make', 'coverage-kotlin'],
        'coverage_threshold': 10.0,
        'coverage_file': 'kotlin/build/reports/jacoco/test/jacocoTestReport.xml',
        'workdir': '.'
    },
    'rust': {
        'name': 'Rust',
        'test_cmd': ['cd', 'rust', '&&', 'cargo', 'test'],
        'coverage_cmd': ['cd', 'rust', '&&', 'cargo', 'test', '--coverage'],
        'coverage_threshold': 60.0,
        'coverage_file': 'rust/coverage/cobertura.xml',
        'workdir': '.',
        'optional': True
    },
    'zig': {
        'name': 'Zig',
        'test_cmd': ['make', 'test-zig'],
        'coverage_cmd': None,
        'coverage_threshold': 0,
        'coverage_file': None,
        'workdir': '.',
        'optional': True
    },
    'frontend': {
        'name': 'Frontend',
        'test_cmd': ['make', 'test-frontend'],
        'coverage_cmd': ['make', 'coverage-frontend'],
        'coverage_threshold': 7.0,
        'coverage_file': 'coverage/frontend/lcov.info',
        'workdir': '.'
    }
}

class TestResult:
    def __init__(self, service: str, success: bool, coverage: Optional[float] = None, output: str = ""):
        self.service = service
        self.success = success
        self.coverage = coverage
        self.output = output
        self.timestamp = datetime.now().isoformat()

    def to_dict(self) -> Dict:
        return {
            'service': self.service,
            'success': self.success,
            'coverage': self.coverage,
            'timestamp': self.timestamp,
            'output_preview': self.output[-500:] if len(self.output) > 500 else self.output
        }

def parse_args():
    parser = argparse.ArgumentParser(description='Integration test runner for all backends')
    parser.add_argument('--coverage', action='store_true', default=True, help='Generate coverage reports')
    parser.add_argument('--fail-fast', action='store_true', default=False, help='Stop on first failure')
    parser.add_argument('--skip-frontend', action='store_true', default=False, help='Skip frontend tests')
    parser.add_argument('--skip-backend', action='store_true', default=False, help='Skip backend tests')
    return parser.parse_args()

def run_command(cmd: List[str], workdir: str = '.') -> Tuple[int, str]:
    """Run a shell command and return exit code and output."""
    print(f"\n📋 Running: {' '.join(cmd)}")
    try:
        result = subprocess.run(
            cmd,
            cwd=workdir,
            shell=isinstance(cmd, str) or (len(cmd) == 1 and isinstance(cmd[0], str)),
            capture_output=True,
            text=True,
            timeout=300  # 5 minute timeout per test
        )
        print(result.stdout)
        if result.stderr:
            print("STDERR:", result.stderr)
        return result.returncode, result.stdout + result.stderr
    except subprocess.TimeoutExpired:
        return -1, "Command timed out after 5 minutes"
    except Exception as e:
        return -1, str(e)

def check_coverage_file(filepath: str) -> Optional[float]:
    """Parse coverage file and return percentage."""
    path = Path(filepath)
    if not path.exists():
        print(f"⚠️  Coverage file not found: {filepath}")
        return None

    try:
        if filepath.endswith('.xml'):
            import xml.etree.ElementTree as ET
            tree = ET.parse(filepath)
            root = tree.getroot()

            # Try different XML formats
            # Format 1: Find overall line-rate
            metrics = root.find('.//metrics')
            if metrics is not None:
                line_rate = metrics.get('line-rate')
                if line_rate:
                    return float(line_rate) * 100

            # Format 2: Sum from packages/classes
            total_lines = 0
            covered_lines = 0
            for cls in root.findall('.//class'):
                lines = int(cls.get('lines', 0))
                line_rate = float(cls.get('line-rate', 0))
                total_lines += lines
                covered_lines += lines * line_rate

            if total_lines > 0:
                return (covered_lines / total_lines) * 100

        elif filepath.endswith('.info'):  # lcov
            total_lines = 0
            covered_lines = 0
            with open(filepath) as f:
                for line in f:
                    if line.startswith('DA:'):
                        total_lines += 1
                        hit = int(line.split(',')[1])
                        if hit > 0:
                            covered_lines += 1
            if total_lines > 0:
                return (covered_lines / total_lines) * 100

    except Exception as e:
        print(f"❌ Error parsing coverage file {filepath}: {e}")

    return None

def run_tests(args) -> List[TestResult]:
    """Run tests for all services and collect results."""
    results = []
    overall_success = True

    # Determine which services to run
    services_to_run = []
    if not args.skip_backend:
        services_to_run.extend(['php', 'go', 'python', 'kotlin'])
        if not args.skip_frontend:
            services_to_run.append('frontend')
    elif args.skip_backend and not args.skip_frontend:
        services_to_run.append('frontend')

    for service_key in services_to_run:
        service = SERVICES[service_key]
        print(f"\n{'='*60}")
        print(f"🧪 Testing {service['name']} ({service_key})")
        print(f"{'='*60}")

        # Run unit tests
        exit_code, output = run_command(service['test_cmd'], service['workdir'])
        success = exit_code == 0

        result = TestResult(service['name'], success, output=output)
        results.append(result)

        if not success:
            overall_success = False
            print(f"❌ {service['name']} tests FAILED")
            if args.fail_fast:
                print("🛑 Stopping due to --fail-fast")
                break
            continue
        else:
            print(f"✅ {service['name']} tests PASSED")

        # Run coverage if requested
        if args.coverage and service['coverage_cmd']:
            print(f"\n📊 Generating coverage for {service['name']}...")
            cov_exit, cov_output = run_command(service['coverage_cmd'], service['workdir'])

            if cov_exit == 0:
                coverage = check_coverage_file(service['coverage_file'])
                if coverage is not None:
                    result.coverage = coverage
                    print(f"   Coverage: {coverage:.2f}% (threshold: {service['coverage_threshold']}%)")
                    if coverage < service['coverage_threshold']:
                        print(f"   ⚠️  Coverage below threshold!")
                        # Do not fail overall for coverage (informational)
                else:
                    print(f"   ⚠️  Could not determine coverage")
            else:
                print(f"   ⚠️  Coverage generation failed or not supported")

    # Print summary
    print(f"\n{'='*60}")
    print("📄 TEST SUMMARY")
    print(f"{'='*60}")

    for result in results:
        status = "✅ PASS" if result.success else "❌ FAIL"
        coverage_str = f" | Coverage: {result.coverage:.2f}%" if result.coverage is not None else ""
        print(f"{result.service:<25} {status}{coverage_str}")

    print(f"\nOverall: {'✅ ALL TESTS PASSED' if overall_success else '❌ SOME TESTS FAILED'}")
    print(f"Completed: {len([r for r in results if r.success])}/{len(results)} services")

    return results

def save_report(results: List[TestResult], filename: str = 'integration-test-report.json'):
    """Save test results to JSON file."""
    report = {
        'timestamp': datetime.now().isoformat(),
        'results': [r.to_dict() for r in results],
        'summary': {
            'total': len(results),
            'passed': len([r for r in results if r.success]),
            'failed': len([r for r in results if not r.success]),
            'all_passed': all(r.success for r in results)
        }
    }
    with open(filename, 'w') as f:
        json.dump(report, f, indent=2)
    print(f"\n📁 Report saved to: {filename}")

def main():
    # Ensure we're in the correct directory
    if not Path('Makefile').exists():
        print("❌ Makefile not found. Please run from project root.")
        sys.exit(1)

    args = parse_args()

    print("🚀 Starting Cross-Module Integration Test Runner")
    print(f"   Coverage: {args.coverage}")
    print(f"   Fail fast: {args.fail_fast}")
    print(f"   Skip frontend: {args.skip_frontend}")
    print(f"   Skip backend: {args.skip_backend}")

    # Check Docker environment
    try:
        subprocess.run(['docker', 'ps'], capture_output=True, check=True)
    except (subprocess.CalledProcessError, FileNotFoundError):
        print("❌ Docker is not running or not available")
        sys.exit(1)

    # Run tests
    results = run_tests(args)

    # Save report
    report_file = f"reports/integration-test-report-{datetime.now().strftime('%Y%m%d-%H%M%S')}.json"
    Path('reports').mkdir(exist_ok=True)
    save_report(results, report_file)

    # Exit with appropriate code
    sys.exit(0 if all(r.success for r in results) else 1)

if __name__ == '__main__':
    main()
