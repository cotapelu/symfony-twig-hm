#!/usr/bin/env node
/**
 * Frontend coverage check.
 * Reads lcov.info and enforces a minimum line coverage threshold.
 * Usage: node scripts/check-frontend-coverage.js [threshold]
 *   threshold: minimum line coverage percentage (default: 80.0)
 */
const fs = require('fs');
const path = require('path');

const lcovPath = path.resolve(__dirname, '..', 'coverage', 'frontend', 'lcov.info');
const thresholdArg = process.argv[2];
const defaultThreshold = 80.0;
const threshold = thresholdArg ? parseFloat(thresholdArg) : defaultThreshold;

if (!fs.existsSync(lcovPath)) {
  console.error(`Coverage file not found: ${lcovPath}`);
  process.exit(1);
}

const data = fs.readFileSync(lcovPath, 'utf8');
let totalLines = 0;
let coveredLines = 0;
for (const line of data.split('\n')) {
  if (line.startsWith('DA:')) {
    totalLines++;
    const hit = parseInt(line.split(',')[1], 10);
    if (hit > 0) coveredLines++;
  }
}

const coveragePercent = totalLines > 0 ? (coveredLines / totalLines) * 100 : 0;
console.log(`Frontend coverage: ${coveragePercent.toFixed(2)}% (${coveredLines}/${totalLines} lines)`);

if (coveragePercent < threshold) {
  console.error(`ERROR: Coverage ${coveragePercent.toFixed(2)}% is below threshold of ${threshold}%`);
  process.exit(1);
}

console.log(`Coverage meets threshold (>=${threshold}%).`);
process.exit(0);
