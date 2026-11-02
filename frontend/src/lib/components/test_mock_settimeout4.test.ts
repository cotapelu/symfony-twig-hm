import { describe, it, expect, vi } from 'vitest';

describe('setTimeout mock with fake timers debug 2', () => {
  it('manual resolve should work', async () => {
    vi.useFakeTimers();
    
    let resolveFn: (value: any) => void;
    
    const mock = vi.fn().mockImplementation(() => {
      return new Promise((resolve) => {
        resolveFn = resolve;
      });
    });
    
    let resolved = false;
    const promise = mock().then(() => { resolved = true; });
    
    console.log('before manual resolve, resolved:', resolved);
    resolveFn({});
    await Promise.resolve();
    console.log('after manual resolve + Promise.resolve, resolved:', resolved);
    
    vi.useRealTimers();
  });
  
  it('setTimeout with fake timers', async () => {
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
    
    // Try pumping microtasks multiple times
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
    console.log('after 3x Promise.resolve, resolved:', resolved);
    
    vi.useRealTimers();
  });
});
