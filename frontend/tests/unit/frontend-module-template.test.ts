import { describe, it, expect } from 'vitest';
import { readdirSync, statSync } from 'fs';
import { join } from 'path';

describe('Frontend Module Template', () => {
  const templateDir = join(process.cwd(), '.gsd', 'milestones', 'M001', 'slices', 'S06', 'templates', 'frontend-module');

  it('should have the correct directory structure', () => {
    const expectedDirs = ['ui', 'state', 'services', 'contracts', 'events'];
    expectedDirs.forEach(dir => {
      const dirPath = join(templateDir, dir);
      expect(statSync(dirPath).isDirectory()).toBe(true);
    });
  });

  it('should have template files for each layer', () => {
    const expectedFiles = [
      'ui/{{moduleName}}.svelte',
      'state/{{moduleName}}.store.ts',
      'services/{{moduleName}}.service.ts',
      'contracts/{{moduleName}}.types.ts',
      'events/{{moduleName}}.events.ts',
      'index.ts.hbs'
    ];
    
    expectedFiles.forEach(file => {
      const filePath = join(templateDir, file);
      expect(() => statSync(filePath)).not.toThrow();
    });
  });

  it('should have proper handlebars templating syntax', () => {
    const indexTemplate = join(templateDir, 'index.ts.hbs');
    const content = require('fs').readFileSync(indexTemplate, 'utf8');
    
    expect(content).toContain('{{#pascalCase moduleName}}');
    expect(content).toContain('{{#snakeCase moduleName}}');
    expect(content).toContain('{{moduleName}}');
  });
});
