import { describe, it, expect, vi } from 'vitest';

describe('setTimeout mock microtask pumping', () => {
  it('how many Promise.resolve needed', async () => {
    vi.useFakeTimers();
    
    const mock = vi.fn().mockImplementation(() => {
      return new Promise((resolve) => {
        setTimeout(() => resolve({}), 0);
      });
    });
    
    let resolved = false;
    const promise = mock().then(() => { resolved = true; });
    
    vi.advanceTimersByTime(0);
    
    for (let i = 0; i < 5; i++) {
      await Promise.resolve();
      console.log(`after ${i + 1} Promise.resolve, resolved:`, resolved);
      if (resolved) break;
    }
    
    vi.useRealTimers();
  });
});
