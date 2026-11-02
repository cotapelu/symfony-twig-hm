#!/usr/bin/env python3
import re
import sys

def process_file(path):
    with open(path, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    out_lines = []
    # State for removing method
    skip_until_closing_brace = False
    brace_depth = 0

    for line in lines:
        # Check for start of getRequestBody method
        if not skip_until_closing_brace and re.search(r'private\s+function\s+getRequestBody\s*\(\s*Request\s*\$\w+\s*\)\s*:\s*array', line):
            skip_until_closing_brace = True
            brace_depth = line.count('{') - line.count('}')
            # If no brace on same line, brace_depth may be 0, but we expect next line has {
            continue

        if skip_until_closing_brace:
            # Track braces to find end of method
            brace_depth += line.count('{') - line.count('}')
            if brace_depth <= 0:
                skip_until_closing_brace = False
            continue

        # Replace getRequestBody calls inline
        if re.match(r'^(\s*)\$data\s*=\s*\$this->getRequestBody\(\$request\);', line):
            m = re.match(r'^(\s*)\$data\s*=\s*\$this->getRequestBody\(\$request\);', line)
            indent = m.group(1)
            out_lines.append(indent + "$data = json_decode($request->getContent(), true);\n")
            out_lines.append(indent + "if (json_last_error() !== JSON_ERROR_NONE) {\n")
            out_lines.append(indent + "    throw new InvalidArgumentException('Invalid JSON');\n")
            out_lines.append(indent + "}\n")
            continue

        out_lines.append(line)

    with open(path, 'w', encoding='utf-8') as f:
        f.writelines(out_lines)
    print(f"Processed {path}")

if __name__ == "__main__":
    files = [
        "php-symfony/modules/BusinessAccount/Controller/BusinessAccountController.php",
        "php-symfony/modules/BusinessAccounts/Controller/BusinessAccountsController.php"
    ]
    for f in files:
        process_file(f)
