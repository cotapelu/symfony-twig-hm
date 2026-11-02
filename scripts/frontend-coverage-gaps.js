#!/usr/bin/env node
const fs = require('fs');
const path = require('path');

const lcovPath = path.resolve('coverage/frontend/lcov.info');
if (!fs.existsSync(lcovPath)) {
  console.error('Coverage file not found:', lcovPath);
  process.exit(1);
}

const data = fs.readFileSync(lcovPath, 'utf8');
const lines = data.split('\n');
let currentFile = null;
const stats = new Map();

for (let line of lines) {
  if (line.startsWith('SF:')) {
    currentFile = line.slice(3);
  } else if (line.startsWith('DA:')) {
    if (!currentFile) continue;
    const [ln, hit] = line.slice(3).split(',').map(Number);
    if (!stats.has(currentFile)) stats.set(currentFile, { total: 0, covered: 0 });
    const s = stats.get(currentFile);
    s.total++;
    if (hit > 0) s.covered++;
  }
}

// compute percent and sort asc
const files = Array.from(stats.entries())
  .filter(([, s]) => s.total > 0)
  .map(([file, s]) => ({ file, total: s.total, covered: s.covered, pct: (s.covered / s.total) * 100 }))
  .sort((a, b) => a.pct - b.pct);

console.log('Top 30 lowest coverage files:');
console.log('Coverage% | Lines | File');
console.log('---------+-------+----------------------------------------------');
for (const f of files.slice(0, 30)) {
  console.log(`${f.pct.toFixed(2)}% | ${f.covered}/${f.total} | ${f.file}`);
}

const overallCov = files.reduce((sum, f) => sum + f.covered, 0) / files.reduce((sum, f) => sum + f.total, 0) * 100;
console.log(`\nOverall coverage: ${overallCov.toFixed(2)}% (${files.reduce((sum,f)=>sum+f.covered,0)}/${files.reduce((sum,f)=>sum+f.total,0)} lines)`);
