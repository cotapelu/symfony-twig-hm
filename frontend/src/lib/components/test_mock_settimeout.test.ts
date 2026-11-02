import { describe, it, expect, vi } from 'vitest';

describe('setTimeout mock with fake timers', () => {
  it('setTimeout should resolve with advanceTimersByTime', async () => {
    vi.useFakeTimers();
    const mock = vi.fn().mockImplementation(() => {
      return new Promise((resolve) => {
        setTimeout(() => resolve({}), 0);
      });
    });
    
    let resolved = false;
    const promise = mock().then(() => { resolved = true; });
    
    console.log('before advance, resolved:', resolved);
    vi.advanceTimersByTime(0);
    console.log('after advance 0, resolved:', resolved);
    
    vi.useRealTimers();
  });
  
  it('setTimeout 100ms should resolve with advanceTimersByTime(100)', async () => {
    vi.useFakeTimers();
    const mock = vi.fn().mockImplementation(() => {
      return new Promise((resolve) => {
        setTimeout(() => resolve({}), 100);
      });
    });
    
    let resolved = false;
    const promise = mock().then(() => { resolved = true; });
    
    console.log('before advance, resolved:', resolved);
    vi.advanceTimersByTime(100);
    console.log('after advance 100, resolved:', resolved);
    
    vi.useRealTimers();
  });
});
