import { describe, it, expect, vi } from 'vitest';

describe('runAllTimers debug', () => {
  it('should resolve all timers', async () => {
    vi.useFakeTimers();
    let called = false;
    setTimeout(() => { called = true; }, 0);
    
    console.log('before runAllTimers, called:', called);
    vi.runAllTimers();
    console.log('after runAllTimers, called:', called);
    
    await Promise.resolve();
    console.log('after Promise.resolve, called:', called);
    
    vi.useRealTimers();
  });
});
