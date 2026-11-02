import { describe, it, expect, vi } from 'vitest';

function createResolvedMock(value = {}) {
  return vi.fn().mockImplementation(() => {
    return new Promise((resolve) => {
      setTimeout(() => resolve(value), 0);
    });
  });
}

describe('tinyspy wrapper with fake timers', () => {
  it('direct mock should resolve', async () => {
    vi.useFakeTimers();
    const inner = createResolvedMock();
    
    let resolved = false;
    const promise = inner().then(() => { resolved = true; });
    
    vi.advanceTimersByTime(10000);
    for (let i = 0; i < 3; i++) await Promise.resolve();
    
    console.log('direct mock resolved:', resolved);
    vi.useRealTimers();
  });
  
  it('wrapped mock should resolve', async () => {
    vi.useFakeTimers();
    const inner = createResolvedMock();
    const outer = vi.fn().mockImplementation(inner);
    
    let resolved = false;
    const promise = outer().then(() => { resolved = true; });
    
    vi.advanceTimersByTime(10000);
    for (let i = 0; i < 3; i++) await Promise.resolve();
    
    console.log('wrapped mock resolved:', resolved);
    vi.useRealTimers();
  });
  
  it('mockImplementation with mock should resolve', async () => {
    vi.useFakeTimers();
    const inner = createResolvedMock();
    const outer = vi.fn();
    outer.mockImplementation(inner);
    
    let resolved = false;
    const promise = outer().then(() => { resolved = true; });
    
    vi.advanceTimersByTime(10000);
    for (let i = 0; i < 3; i++) await Promise.resolve();
    
    console.log('mockImplementation with mock resolved:', resolved);
    vi.useRealTimers();
  });
});
