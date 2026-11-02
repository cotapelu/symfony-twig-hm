#!/usr/bin/env python3
"""
Audit architecture compliance for all backends.
Checks if modules follow Api/Application/Domain/Infrastructure pattern.
"""

import os
import json
from pathlib import Path

def audit_backend(name, base_path):
    result = {
        "backend": name,
        "modules": [],
        "stats": {
            "total_modules": 0,
            "compliant": 0,
            "non_compliant": 0,
            "unknown": 0
        }
    }

    modules_dir = Path(base_path) / "modules"
    if not modules_dir.exists():
        return result

    for module_dir in modules_dir.iterdir():
        if not module_dir.is_dir():
            continue

        module_name = module_dir.name
        result["stats"]["total_modules"] += 1

        # Check for architecture layers
        has_api = (module_dir / "Api").exists() or (module_dir / "api").exists()
        has_app = (module_dir / "Application").exists() or (module_dir / "application").exists()
        has_domain = (module_dir / "Domain").exists() or (module_dir / "domain").exists()
        has_infra = (module_dir / "Infrastructure").exists() or (module_dir / "infrastructure").exists()

        layers = sum([has_api, has_app, has_domain, has_infra])

        if layers >= 3:
            status = "compliant"
            result["stats"]["compliant"] += 1
        elif layers >= 1:
            status = "partial"
            result["stats"]["non_compliant"] += 1
        else:
            status = "unknown"
            result["stats"]["unknown"] += 1

        result["modules"].append({
            "name": module_name,
            "status": status,
            "layers": {
                "Api": has_api,
                "Application": has_app,
                "Domain": has_domain,
                "Infrastructure": has_infra
            }
        })

    return result

def main():
    backends = [
        ("PHP Symfony", "php-symfony"),
        ("Go", "go"),
        ("Python", "python"),
        ("Kotlin", "kotlin"),
        ("Rust", "rust"),
        ("Zig", "zig"),
    ]

    all_results = []
    total_modules = 0
    total_compliant = 0

    for name, path in backends:
        result = audit_backend(name, path)
        all_results.append(result)
        total_modules += result["stats"]["total_modules"]
        total_compliant += result["stats"]["compliant"]

    # Print summary
    print("=== ARCHITECTURE COMPLIANCE AUDIT ===")
    print()
    for result in all_results:
        stats = result["stats"]
        compliance_rate = (stats["compliant"] / stats["total_modules"] * 100) if stats["total_modules"] > 0 else 0
        print(f"{result['backend']}:")
        print(f"  Total modules: {stats['total_modules']}")
        print(f"  Compliant: {stats['compliant']} ({compliance_rate:.1f}%)")
        print(f"  Partial: {stats['non_compliant']}")
        print(f"  Unknown: {stats['unknown']}")
        print()

    overall_rate = (total_compliant / total_modules * 100) if total_modules > 0 else 0
    print(f"OVERALL: {total_compliant}/{total_modules} modules compliant ({overall_rate:.1f}%)")
    print()

    # Save detailed JSON
    output_file = Path("docs/architecture-audit.json")
    output_file.parent.mkdir(exist_ok=True)
    with open(output_file, 'w') as f:
        json.dump(all_results, f, indent=2)
    print(f"Detailed report saved to {output_file}")

if __name__ == "__main__":
    main()
