import { describe, it, expect, vi } from 'vitest';

describe('async mock with fake timers', () => {
  it('async function should resolve', async () => {
    vi.useFakeTimers();
    const mock = vi.fn().mockImplementation(async () => {
      return {};
    });
    
    let resolved = false;
    const promise = mock().then(() => { resolved = true; });
    
    console.log('before advance, resolved:', resolved);
    vi.advanceTimersByTime(1000);
    console.log('after advance 1000, resolved:', resolved);
    
    vi.useRealTimers();
  });
  
  it('mockResolvedValue should resolve', async () => {
    vi.useFakeTimers();
    const mock = vi.fn().mockResolvedValue({});
    
    let resolved = false;
    const promise = mock().then(() => { resolved = true; });
    
    console.log('before advance, resolved:', resolved);
    vi.advanceTimersByTime(1000);
    console.log('after advance 1000, resolved:', resolved);
    
    vi.useRealTimers();
  });
});
