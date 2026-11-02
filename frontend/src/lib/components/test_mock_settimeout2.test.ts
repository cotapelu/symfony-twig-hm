import { describe, it, expect, vi } from 'vitest';

describe('setTimeout mock with fake timers + microtask pump', () => {
  it('setTimeout should resolve with advanceTimersByTime + Promise.resolve', async () => {
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
    await Promise.resolve();
    console.log('after advance 0 + Promise.resolve, resolved:', resolved);
    
    vi.useRealTimers();
  });
  
  it('setTimeout 100ms should resolve with advanceTimersByTime(100) + Promise.resolve', async () => {
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
    await Promise.resolve();
    console.log('after advance 100 + Promise.resolve, resolved:', resolved);
    
    vi.useRealTimers();
  });
});
