const fs = require('fs');
const path = require('path');

const lcovPath = path.resolve('coverage/frontend/lcov.info');
const data = fs.readFileSync(lcovPath, 'utf8');
const lines = data.split('\n');

let currentFile = null;
const fileStats = new Map(); // Map: file -> { total, covered }

for (let line of lines) {
  if (line.startsWith('SF:')) {
    currentFile = line.slice(3);
    // strip base path maybe
  } else if (line.startsWith('DA:')) {
    if (!currentFile) continue;
    const parts = line.slice(3).split(',');
    const hits = parseInt(parts[1], 10);
    if (!fileStats.has(currentFile)) {
      fileStats.set(currentFile, { total: 0, covered: 0 });
    }
    const stats = fileStats.get(currentFile);
    stats.total++;
    if (hits > 0) stats.covered++;
  }
}

// compute percentages and sort by coverage asc
const entries = Array.from(fileStats.entries())
  .filter(([, stats]) => stats.total > 0)
  .map(([file, stats]) => ({
    file,
    total: stats.total,
    covered: stats.covered,
    percent: (stats.covered / stats.total) * 100
  }))
  .sort((a, b) => a.percent - b.percent);

// print top 20 lowest coverage files
console.log('Top 20 lowest coverage files:');
entries.slice(0, 20).forEach(e => {
  console.log(`${e.percent.toFixed(2)}% (${e.covered}/${e.total}) ${e.file}`);
});

console.log(`\nTotal files analyzed: ${entries.length}`);
console.log(`Overall coverage: ${entries.reduce((sum, e) => sum + e.covered, 0)} / ${entries.reduce((sum, e) => sum + e.total, 0)} lines = ${(entries.reduce((sum, e) => sum + e.covered, 0) / entries.reduce((sum, e) => sum + e.total, 0) * 100).toFixed(2)}%`);
