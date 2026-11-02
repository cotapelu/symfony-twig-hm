import { describe, it, expect, vi } from 'vitest';

function createResolvedMock(value = {}) {
  return vi.fn().mockImplementation(() => {
    return new Promise((resolve) => {
      setTimeout(() => resolve(value), 0);
    });
  });
}

describe('fake timer resolve debug', () => {
  it('should resolve with advanceTimersByTime + pump', async () => {
    vi.useFakeTimers();
    const mock = createResolvedMock();
    
    let resolved = false;
    const promise = mock().then(() => { resolved = true; });
    
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
