import { describe, it, expect } from 'vitest';
import { getApiUrl, API_CONFIG } from './api';

describe('API Config', () => {
  it('getApiUrl returns gateway + path', () => {
    const url = getApiUrl('go', '/api/test');
    expect(url).toBe('http://localhost:8080/api/test');
  });

  it('API_CONFIG has expected structure', () => {
    expect(API_CONFIG.gateway).toBe('http://localhost:8080');
    expect(API_CONFIG.direct.go).toBe('http://localhost:8081');
    expect(API_CONFIG.direct.php).toBe('http://localhost:8080');
    expect(API_CONFIG.direct.python.telemedicine).toBe('http://localhost:8000');
    expect(API_CONFIG.direct.kotlin.flights).toBe('http://localhost:9000');
  });
});
