import { describe, it, expect, vi } from 'vitest';

describe('setTimeout mock with fake timers debug', () => {
  it('inspect setTimeout behavior', async () => {
    vi.useFakeTimers();
    
    let setTimeoutCalled = false;
    let resolveFn: (value: any) => void;
    
    const mock = vi.fn().mockImplementation(() => {
      return new Promise((resolve) => {
        resolveFn = resolve;
        setTimeout(() => {
          setTimeoutCalled = true;
          resolve({});
        }, 0);
      });
    });
    
    const promise = mock();
    console.log('mock called:', mock.mock.calls.length);
    console.log('setTimeout registered, resolveFn exists:', !!resolveFn);
    
    // Try to manually resolve
    resolveFn({});
    await Promise.resolve();
    
    console.log('after manual resolve, setTimeoutCalled:', setTimeoutCalled);
    
    vi.useRealTimers();
  });
});
