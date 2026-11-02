#!/usr/bin/env python3
"""
Check Kotlin JaCoCo coverage threshold.
Usage: check-kotlin-coverage.py <jacoco.xml> <threshold_percent>
"""

import sys
import xml.etree.ElementTree as ET

def main():
    if len(sys.argv) < 2:
        print("Usage: check-kotlin-coverage.py <jacoco.xml> [threshold]")
        sys.exit(1)

    coverage_file = sys.argv[1]
    threshold = float(sys.argv[2]) if len(sys.argv) > 2 else 80.0

    try:
        tree = ET.parse(coverage_file)
        root = tree.getroot()

        # JaCoCo XML format: <report><counter type="INSTRUCTION" missed="X" covered="Y"/>
        # Calculate coverage = covered / (missed + covered) * 100

        # Try INSTRUCTION first
        instr = root.find('.//counter[@type="INSTRUCTION"]')
        if instr is not None:
            covered = int(instr.get('covered', 0))
            missed = int(instr.get('missed', 0))
            total = covered + missed
            if total > 0:
                coverage = (covered / total) * 100
                print(f"Kotlin coverage (INSTRUCTION): {coverage:.2f}% (threshold: {threshold}%)")
                if coverage >= threshold:
                    print("Coverage OK")
                    sys.exit(0)
                else:
                    print("Coverage below threshold!")
                    sys.exit(1)

        # Fallback to LINE
        line = root.find('.//counter[@type="LINE"]')
        if line is not None:
            covered = int(line.get('covered', 0))
            missed = int(line.get('missed', 0))
            total = covered + missed
            if total > 0:
                coverage = (covered / total) * 100
                print(f"Kotlin coverage (LINE): {coverage:.2f}% (threshold: {threshold}%)")
                if coverage >= threshold:
                    print("Coverage OK")
                    sys.exit(0)

        print("Could not find INSTRUCTION or LINE counters in JaCoCo report")
        sys.exit(1)

    except ET.ParseError as e:
        print(f"Error parsing JaCoCo XML: {e}")
        sys.exit(1)
    except FileNotFoundError:
        print(f"Coverage file not found: {coverage_file}")
        sys.exit(1)

if __name__ == '__main__':
    main()
