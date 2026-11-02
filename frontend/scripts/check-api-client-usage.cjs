#!/usr/bin/env node

/**
 * Scan frontend services to identify which ones use direct fetch vs apiClient
 * Usage: node check-api-client-usage.js
 */

const fs = require('fs');
const path = require('path');

const servicesDir = path.join(__dirname, '..', 'src', 'modules');

function scanDirectory(dir) {
  let results = [];
  const entries = fs.readdirSync(dir, { withFileTypes: true });

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      // Look for services in subdirectories (e.g., wallet/services)
      const servicesPath = path.join(fullPath, 'services');
      if (fs.existsSync(servicesPath)) {
        results = results.concat(scanServices(servicesPath));
      }
    }
  }

  return results;
}

function scanServices(servicesPath) {
  let results = [];
  const files = fs.readdirSync(servicesPath, { withFileTypes: true });

  for (const file of files) {
    if (file.name.endsWith('.ts') && !file.name.endsWith('.test.ts') && !file.name.endsWith('.spec.ts')) {
      const filePath = path.join(servicesPath, file.name);
      const content = fs.readFileSync(filePath, 'utf8');
      const usesDirectFetch = /['"]fetch\(\s*['"]/.test(content) || /^[^}]*\bfetch\s*\(/.test(content);
      const usesApiClient = content.includes('apiClient') || content.includes('apiRequest');

      let importLine = '';
      const importMatch = content.match(/import\s+\{[^}]*\}/);
      if (importMatch) {
        importLine = importMatch[0];
      }

      results.push({
        module: path.basename(servicesPath),
        file: file.name,
        usesDirectFetch,
        usesApiClient,
        importLine
      });
    }
  }

  return results;
}

// Main execution
const allServices = scanDirectory(servicesDir);

console.log(`\n=== Frontend Service API Usage Report ===\n`);
console.log(`Total service files: ${allServices.length}\n`);

const usingDirectFetch = allServices.filter(s => s.usesDirectFetch && !s.usesApiClient);
const usingApiClient = allServices.filter(s => s.usesApiClient);
const hybrid = allServices.filter(s => s.usesDirectFetch && s.usesApiClient);

console.log(`✅ Already using apiClient: ${usingApiClient.length}`);
console.log(`❌ Still using direct fetch: ${usingDirectFetch.length}`);
console.log(`🔄 Hybrid (partial migration): ${hybrid.length}\n`);

if (usingDirectFetch.length > 0) {
  console.log('--- Services needing migration (Priority batches) ---');
  // Group by module for batch planning
  const byModule = {};
  for (const s of usingDirectFetch) {
    if (!byModule[s.module]) byModule[s.module] = [];
    byModule[s.module].push(s.file);
  }

  // Priority: financial, citizen, business modules
  const priorityBatches = {
    'Priority 1 - Financial & Auth': ['payments', 'billing', 'bookings', 'directory', 'wishlist', 'budgeting'],
    'Priority 2 - Citizen Services': ['calls', 'civil', 'security-order', 'queue', 'complaints', 'support'],
    'Priority 3 - Business Modules': ['campaigns', 'deals', 'profile', 'reports', 'traffic', 'marketing'],
    'Priority 4 - Other': []
  };

  Object.entries(byModule).forEach(([module, files]) => {
    let batch = 'Priority 4 - Other';
    for (const [batchName, modules] of Object.entries(priorityBatches)) {
      if (modules.includes(module.toLowerCase().replace(/-/g, ''))) {
        batch = batchName;
        break;
      }
    }
    console.log(`\n[${batch}] Module: ${module} (${files.length} services)`);
    files.forEach(f => console.log(`  - ${f}`));
  });
}

// Export to CSV
const csvPath = path.join(__dirname, 'api-client-migration-report.csv');
let csv = 'Module,File,UsesDirectFetch,UsesApiClient,ImportLine\n';
for (const s of allServices) {
  csv += `${s.module},${s.file},${s.usesDirectFetch},${s.usesApiClient},"${s.importLine.replace(/"/g, '""')}"\n`;
}
fs.writeFileSync(csvPath, csv);
console.log(`\n📊 Detailed report saved to: ${csvPath}\n`);
