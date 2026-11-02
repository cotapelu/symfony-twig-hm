import { describe, it, expect, vi } from 'vitest';

function createResolvedMock(value = {}) {
  return () => {
    return new Promise((resolve) => {
      setTimeout(() => resolve(value), 0);
    });
  };
}

describe('tinyspy wrapper with fake timers 2', () => {
  it('direct implementation on mock', async () => {
    vi.useFakeTimers();
    const outer = vi.fn();
    outer.mockImplementation(createResolvedMock());
    
    let resolved = false;
    const promise = outer().then(() => { resolved = true; });
    
    console.log('before advance, resolved:', resolved);
    vi.advanceTimersByTime(10000);
    console.log('after advance 10000, resolved:', resolved);
    
    for (let i = 0; i < 3; i++) {
      await Promise.resolve();
      console.log(`after pump ${i + 1}, resolved:`, resolved);
    }
    
    vi.useRealTimers();
  });
  
  it('mockReturnValue with promise', async () => {
    vi.useFakeTimers();
    const outer = vi.fn().mockReturnValue(new Promise((resolve) => {
      setTimeout(() => resolve({}), 0);
    }));
    
    let resolved = false;
    const promise = outer().then(() => { resolved = true; });
    
    console.log('before advance, resolved:', resolved);
    vi.advanceTimersByTime(10000);
    console.log('after advance 10000, resolved:', resolved);
    
    for (let i = 0; i < 3; i++) {
      await Promise.resolve();
      console.log(`after pump ${i + 1}, resolved:`, resolved);
    }
    
    vi.useRealTimers();
  });
});
