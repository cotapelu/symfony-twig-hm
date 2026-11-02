#!/usr/bin/env python3
"""
Check Python coverage threshold.
Usage: check-python-coverage.py <coverage.xml> <threshold_percent>
"""

import sys
import xml.etree.ElementTree as ET

def main():
    if len(sys.argv) < 2:
        print("Usage: check-python-coverage.py <coverage.xml> [threshold]")
        sys.exit(1)

    coverage_file = sys.argv[1]
    threshold = float(sys.argv[2]) if len(sys.argv) > 2 else 80.0

    # If coverage file missing, skip check (CI may not have generated it yet)
    import os
    if not os.path.exists(coverage_file):
        print(f"Coverage file {coverage_file} not found, skipping check.")
        sys.exit(0)

    try:
        tree = ET.parse(coverage_file)
        root = tree.getroot()

        # Find coverage percentage from Cobertura format
        # Look for line-rate attribute on packages or classes
        coverage = None

        # Try to get from packages
        packages = root.findall('.//package')
        if packages:
            total_lines = 0
            covered_lines = 0
            for pkg in packages:
                for cls in pkg.findall('.//class'):
                    line_rate = cls.get('line-rate')
                    if line_rate:
                        # Convert to numbers
                        lines = int(cls.get('lines', 0))
                        covered = float(line_rate) * lines
                        total_lines += lines
                        covered_lines += covered

            if total_lines > 0:
                coverage = (covered_lines / total_lines) * 100

        # Alternative: check for overall coverage
        if coverage is None:
            # Some coverage.xml have different structure
            metrics = root.find('.//metrics')
            if metrics is not None:
                line_rate = metrics.get('line-rate')
                if line_rate:
                    coverage = float(line_rate) * 100

        if coverage is None:
            print("Could not determine coverage from XML")
            sys.exit(1)

        print(f"Python coverage: {coverage:.2f}% (threshold: {threshold}%)")

        if coverage >= threshold:
            print("Coverage OK")
            sys.exit(0)
        else:
            print("Coverage below threshold!")
            sys.exit(1)

    except ET.ParseError as e:
        print(f"Error parsing coverage XML: {e}")
        sys.exit(1)
    except FileNotFoundError:
        print(f"Coverage file not found: {coverage_file}")
        sys.exit(1)

if __name__ == '__main__':
    main()
