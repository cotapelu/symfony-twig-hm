#!/usr/bin/env python3
"""
Audit all Python API endpoints for:
1. Authentication dependency (get_current_user_id or rate-limited variant)
2. Correct import path (should be from app.shared.middleware.auth)
3. Public unprotected endpoints (excluding explicit public ones)
"""
import os
import sys
import re
from pathlib import Path
from typing import Dict, List, Tuple

BASE_DIR = Path(__file__).parents[2] / "app" / "modules"

# Modules that may contain routers/API definitions
ROUTER_FILES = list((BASE_DIR).rglob("router.py")) + list((BASE_DIR).rglob("routes.py")) + list((BASE_DIR).rglob("api/routes.py"))

# Known intentionally public endpoints (by path & method)
PUBLIC_ENDPOINTS = {
    ("/auth/token", "POST"): "SocialFeed dev token",
    ("/health", "GET"): "Health check",
    ("/metrics", "GET"): "Prometheus metrics",
    ("/ad-types", "GET"): "Ads reference data",
    ("/statuses", "GET"): "Ads reference data",
    ("/specialties", "GET"): "Telemedicine reference data",
}

def extract_routes(file_path: Path) -> List[Tuple[str, str, List[str]]]:
    """
    Parse a router file and extract (method, path, decorator_lines) for each route.
    Returns list of (http_method, path, decorator_lines).
    """
    routes = []
    try:
        content = file_path.read_text()
    except Exception as e:
        return []
    
    # Match @router.<method>("path", ...\n) followed by def func(...):
    pattern = r'@router\.(get|post|put|delete|patch)\s*\(\s*["\']([^"\']+)["\']([^)]*)\)\s*\n\s*async\s+def\s+\w+\s*\('
    for match in re.finditer(pattern, content, re.IGNORECASE | re.DOTALL):
        method = match.group(1).upper()
        path = match.group(2)
        # Get full decorator lines (from @router... to closing paren)
        start = match.start()
        # Find matching closing paren... simple heuristic: count parentheses from start
        paren_count = 0
        end = start
        for i, c in enumerate(content[start:], start=start):
            if c == '(':
                paren_count += 1
            elif c == ')':
                paren_count -= 1
                if paren_count == 0:
                    end = i + 1
                    break
        decorator_lines = content[start:end].strip()
        routes.append((method, path, decorator_lines))
    return routes

def check_auth_in_decorator(decorator_lines: str, full_content: str, func_name: str = None) -> Tuple[bool, str]:
    """
    Determine if the route has authentication dependency.
    Returns (has_auth, reason).
    """
    # Check if there's a Depends(...) in the decorator
    if 'Depends(' in decorator_lines:
        return True, "Depends in decorator"
    
    # Check if the function parameters include current_user_id: str = Depends(...)
    # Extract the function body starting from 'async def ...'
    func_match = re.search(r'async\s+def\s+\w+\s*\(([^)]*)\)\s*:', full_content)
    if func_match:
        params = func_match.group(1)
        # Look for Depends(get_current_user_id) or Depends(..._with_rate_limit)
        if re.search(r'current_user_id\s*:\s*str\s*=\s*Depends\([^)]+\)', params):
            return True, "Parameter Depends"
    
    return False, "No auth dependency found"

def main():
    print("=" * 80)
    print("PYTHON API ENDPOINT SECURITY AUDIT")
    print("=" * 80)
    
    all_routes = []
    modules_scanned = set()
    for router_file in ROUTER_FILES:
        rel_path = router_file.relative_to(BASE_DIR.parents[3])
        module = rel_path.parts[2] if len(rel_path.parts) > 2 else "unknown"
        modules_scanned.add(module)
        routes = extract_routes(router_file)
        for method, path, decorator in routes:
            all_routes.append({
                "module": module,
                "file": str(rel_path),
                "method": method,
                "path": path,
                "decorator": decorator,
            })
    
    print(f"\nScanned {len(modules_scanned)} modules: {sorted(modules_scanned)}")
    print(f"Total routes found: {len(all_routes)}")
    
    # Analyze each route
    unprotected = []
    for route in all_routes:
        file_full = BASE_DIR.parents[3] / route['file']
        try:
            content = file_full.read_text()
        except:
            content = ""
        
        has_auth, reason = check_auth_in_decorator(route['decorator'], content)
        route['has_auth'] = has_auth
        route['auth_reason'] = reason
        
        if not has_auth:
            # Check if it's a known public endpoint
            key = (route['path'], route['method'])
            if key not in PUBLIC_ENDPOINTS:
                unprotected.append(route)
    
    # Report
    print(f"\nProtected routes: {len(all_routes) - len(unprotected)}")
    print(f"Unprotected routes: {len(unprotected)}")
    
    if unprotected:
        print("\n!!! UNPROTECTED ENDPOINTS (need auth) !!!")
        for r in unprotected:
            print(f"  [{r['module']}] {r['method']} {r['path']}")
            print(f"    File: {r['file']}")
    else:
        print("\n✅ All endpoints (except known public) are authenticated!")
    
    # Check import paths
    bad_imports = []
    for router_file in ROUTER_FILES:
        content = router_file.read_text()
        if "from app.main import get_current_user_id" in content or "from app.main import verify_token" in content:
            bad_imports.append(str(router_file.relative_to(BASE_DIR.parents[3])))
    
    if bad_imports:
        print("\n!!! FILES WITH INCORRECT IMPORTS (should use app.shared.middleware.auth) !!!")
        for f in bad_imports:
            print(f"  - {f}")
    else:
        print("\n✅ All imports use correct path (app.shared.middleware.auth)!")
    
    return len(unprotected) == 0 and len(bad_imports) == 0

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)
