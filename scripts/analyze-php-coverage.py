#!/usr/bin/env python3
"""
Analyze PHP coverage XML and report modules with lowest coverage.
Usage: analyze-php-coverage.py [coverage.xml] [min_files]
"""

import sys
import xml.etree.ElementTree as ET
from pathlib import Path
from collections import defaultdict

def main():
    coverage_file = sys.argv[1] if len(sys.argv) > 1 else 'coverage/php-coverage.xml'
    min_files = int(sys.argv[2]) if len(sys.argv) > 2 else 5

    if not Path(coverage_file).exists():
        print(f"Coverage file not found: {coverage_file}")
        sys.exit(1)

    tree = ET.parse(coverage_file)
    root = tree.getroot()

    # Gather metrics per file
    file_metrics = []
    for file_elem in root.iter('file'):
        filename = file_elem.get('name')
        # Skip test files
        if '/tests/' in filename or 'Tests/' in filename or filename.endswith('Test.php'):
            continue
        # Get coverage metrics
        metrics = file_elem.find('metrics')
        if metrics is not None:
            statements = int(metrics.get('statements', 0))
            covered = int(metrics.get('coveredstatements', 0))
            if statements > 0:
                coverage_pct = (covered / statements) * 100
                file_metrics.append({
                    'file': filename,
                    'statements': statements,
                    'covered': covered,
                    'coverage': coverage_pct
                })

    # Sort by coverage ascending (lowest first)
    file_metrics.sort(key=lambda x: x['coverage'])

    print(f"=== PHP Coverage Analysis ===\n")
    print(f"Total non-test files analyzed: {len(file_metrics)}\n")

    # Show files with 0% coverage
    zero_cov = [f for f in file_metrics if f['coverage'] == 0]
    print(f"Files with 0% coverage: {len(zero_cov)}")
    for f in zero_cov[:min_files]:
        print(f"  {f['coverage']:5.1f}%  {f['statements']:4d} stmts  {f['file']}")
    if len(zero_cov) > min_files:
        print(f"  ... and {len(zero_cov) - min_files} more")

    # Show lowest coverage files (>0% but low)
    low_cov = [f for f in file_metrics if 0 < f['coverage'] < 50]
    print(f"\nFiles with <50% coverage: {len(low_cov)}")
    for f in low_cov[:min_files]:
        print(f"  {f['coverage']:5.1f}%  {f['covered']:4d}/{f['statements']:4d}  {f['file']}")
    if len(low_cov) > min_files:
        print(f"  ... and {len(low_cov) - min_files} more")

    # Group by module (directory under modules/)
    module_stats = defaultdict(lambda: {'statements': 0, 'covered': 0, 'files': 0})
    for f in file_metrics:
        # Extract module name: modules/{ModuleName}/...
        parts = Path(f['file']).parts
        if 'modules' in parts:
            idx = parts.index('modules')
            if idx + 1 < len(parts):
                module = parts[idx + 1]
                module_stats[module]['statements'] += f['statements']
                module_stats[module]['covered'] += f['covered']
                module_stats[module]['files'] += 1

    # Compute module coverage and sort
    module_list = []
    for module, stats in module_stats.items():
        if stats['statements'] > 0:
            cov = (stats['covered'] / stats['statements']) * 100
            module_list.append({
                'module': module,
                'coverage': cov,
                'statements': stats['statements'],
                'covered': stats['covered'],
                'files': stats['files']
            })

    module_list.sort(key=lambda x: x['coverage'])
    print("\n=== Module Coverage (lowest first) ===")
    for m in module_list[:15]:
        print(f"  {m['coverage']:5.1f}%  {m['covered']:6d}/{m['statements']:6d} stmts  {m['files']:3d} files  {m['module']}")

    print("\n=== Recommendations ===")
    print("1. Focus on modules with lowest coverage and highest statement count (high impact).")
    print("2. Add unit tests for service layer (highest business logic density).")
    print("3. Add integration tests for controller/API endpoints.")
    print("4. Prioritize security-critical modules: User, Auth, Payment, Notification.")

if __name__ == '__main__':
    main()
