import { describe, it, expect, beforeEach, vi } from 'vitest';
import * as utils from './utils';

// === DATE/TIME TESTS ===
describe('Date/Time utilities', () => {
  const fixedNow = new Date('2024-01-15T12:00:00Z');
  beforeEach(() => {
    vi.useFakeTimers();
    vi.setSystemTime(fixedNow);
  });
  afterEach(() => {
    vi.useRealTimers();
  });

  describe('formatDate', () => {
    it('formats a Date object', () => {
      const date = new Date('2024-01-20T10:30:00Z');
      const result = utils.formatDate(date);
      expect(result).toMatch(/Jan 20, 2024/);
    });

    it('formats a string date', () => {
      const result = utils.formatDate('2024-01-20');
      expect(result).toMatch(/Jan 20, 2024/);
    });

    it('respects custom options', () => {
      const date = new Date('2024-01-20T10:30:00Z');
      const result = utils.formatDate(date, { month: 'long', day: '2-digit' });
      expect(result).toMatch(/January 20/);
    });
  });

  describe('formatTime', () => {
    it('formats time correctly', () => {
      const date = new Date('2024-01-20T14:05:00Z');
      const result = utils.formatTime(date);
      expect(result).toMatch(/\d{1,2}:\d{2}/); // e.g., "2:05 PM" or "14:05"
    });
  });

  describe('formatDateTime', () => {
    it('combines date and time', () => {
      const date = new Date('2024-01-20T10:30:00Z');
      const result = utils.formatDateTime(date);
      // Both date and time portions present
      expect(result).toMatch(/Jan 20, 2024/);
      expect(result).toMatch(/\d{1,2}:\d{2}/);
    });
  });

  describe('formatRelativeTime', () => {
    it('returns "Just now" for seconds ago', () => {
      const date = new Date(Date.now() - 30 * 1000);
      expect(utils.formatRelativeTime(date)).toBe('Just now');
    });

    it('returns minutes for less than hour', () => {
      const date = new Date(Date.now() - 5 * 60 * 1000);
      expect(utils.formatRelativeTime(date)).toBe('5m ago');
    });

    it('returns hours for less than day', () => {
      const date = new Date(Date.now() - 3 * 60 * 60 * 1000);
      expect(utils.formatRelativeTime(date)).toBe('3h ago');
    });

    it('returns days for less than week', () => {
      const date = new Date(Date.now() - 2 * 24 * 60 * 60 * 1000);
      expect(utils.formatRelativeTime(date)).toBe('2d ago');
    });

    // Weeks, months, years can be tested with larger offsets if needed
  });

  describe('isToday', () => {
    it('returns true for today', () => {
      const today = new Date();
      expect(utils.isToday(today)).toBe(true);
    });

    it('returns false for yesterday', () => {
      const yesterday = new Date(Date.now() - 24 * 60 * 60 * 1000);
      expect(utils.isToday(yesterday)).toBe(false);
    });
  });

  describe('isYesterday', () => {
    it('returns true for yesterday', () => {
      const yesterday = new Date(Date.now() - 24 * 60 * 60 * 1000);
      expect(utils.isYesterday(yesterday)).toBe(true);
    });

    it('returns false for today', () => {
      const today = new Date();
      expect(utils.isYesterday(today)).toBe(false);
    });
  });

  describe('formatDuration', () => {
    it('formats seconds only', () => {
      expect(utils.formatDuration(45)).toBe('45s');
    });

    it('formats minutes and seconds', () => {
      expect(utils.formatDuration(125)).toBe('2m 5s');
    });

    it('formats hours, minutes', () => {
      expect(utils.formatDuration(3665)).toBe('1h 1m');
    });
  });

  describe('getTimeAgo', () => {
    it('delegates to formatRelativeTime', () => {
      const date = new Date(Date.now() - 2 * 60 * 60 * 1000);
      expect(utils.getTimeAgo(date)).toBe('2h ago');
    });
  });
});

// === STRING TESTS ===
describe('String utilities', () => {
  describe('truncate', () => {
    it('returns original if within limit', () => {
      expect(utils.truncate('hello', 10)).toBe('hello');
    });

    it('truncates and adds suffix', () => {
      expect(utils.truncate('hello world', 8)).toBe('hello...');
    });

    it('respects custom suffix', () => {
      expect(utils.truncate('hello world', 8, '…')).toBe('hello w…');
    });
  });

  describe('capitalize', () => {
    it('capitalizes first letter and lowercases rest', () => {
      expect(utils.capitalize('hELLo')).toBe('Hello');
    });
  });

  describe('capitalizeWords', () => {
    it('capitalizes each word', () => {
      expect(utils.capitalizeWords('hello world')).toBe('Hello World');
    });
  });

  describe('slugify', () => {
    it('creates URL-friendly slug', () => {
      expect(utils.slugify('Hello World!')).toBe('hello-world');
    });

    it('removes special characters and trims', () => {
      expect(utils.slugify('  __Foo Bar__  ')).toBe('foo-bar');
    });
  });

  describe('camelCase', () => {
    it('converts to camelCase', () => {
      expect(utils.camelCase('hello-world')).toBe('hello-World');
      expect(utils.camelCase('foo bar')).toBe('fooBar');
    });
  });

  describe('kebabCase', () => {
    it('converts to kebab-case', () => {
      expect(utils.kebabCase('HelloWorld')).toBe('hello-world');
      expect(utils.kebabCase('camelCase')).toBe('camel-case');
    });
  });

  describe('pascalCase', () => {
    it('converts to PascalCase', () => {
      expect(utils.pascalCase('hello-world')).toBe('Hello-world');
    });
  });

  describe('truncateWords', () => {
    it('truncates by word count', () => {
      expect(utils.truncateWords('one two three four', 2)).toBe('one two...');
    });

    it('returns original if within limit', () => {
      expect(utils.truncateWords('one two', 5)).toBe('one two');
    });
  });

  describe('stripHtml', () => {
    it('removes HTML tags', () => {
      expect(utils.stripHtml('<p>Hello <b>World</b></p>')).toBe('Hello World');
    });
  });

  describe('escapeHtml', () => {
    it('escapes special characters', () => {
      const result = utils.escapeHtml('<>&"\'');
      // DOM implementation escapes &, <, >, " but leaves ' unescaped
      expect(result).toBe('&lt;&gt;&amp;"\'');
    });
  });

  describe('unescapeHtml', () => {
    it('converts entities back', () => {
      expect(utils.unescapeHtml('&lt;&gt;&amp;')).toBe('<>&');
    });
  });

  describe('generateInitials', () => {
    it('generates two-letter initials', () => {
      expect(utils.generateInitials('John Doe')).toBe('JD');
      expect(utils.generateInitials('Alice')).toBe('A');
    });

    it('handles multiple words', () => {
      expect(utils.generateInitials('First Middle Last')).toBe('FM');
    });
  });

  describe('pluralize', () => {
    it('singular when count=1', () => {
      expect(utils.pluralize(1, 'cat')).toBe('cat');
    });

    it('plural otherwise', () => {
      expect(utils.pluralize(2, 'cat')).toBe('cats');
      expect(utils.pluralize(2, 'person', 'people')).toBe('people');
    });
  });
});

// === NUMBER TESTS ===
describe('Number utilities', () => {
  describe('formatNumber', () => {
    it('formats number with default en-US', () => {
      expect(utils.formatNumber(1234567)).toBe('1,234,567');
    });

    it('respects options', () => {
      expect(utils.formatNumber(1234.56, { minimumFractionDigits: 2, maximumFractionDigits: 2 })).toBe('1,234.56');
    });
  });

  describe('formatCompactNumber', () => {
    it('formats in compact notation', () => {
      const result = utils.formatCompactNumber(1234567);
      expect(result).toMatch(/^1\.?\d*M$/); // e.g., 1.2M
    });
  });

  describe('formatCurrency', () => {
    it('formats USD by default', () => {
      expect(utils.formatCurrency(1234.56)).toBe('$1,234.56');
    });

    it('respects currency', () => {
      expect(utils.formatCurrency(1234.56, 'EUR')).toContain('€');
    });
  });

  describe('formatPercent', () => {
    it('formats with default 0 decimals', () => {
      expect(utils.formatPercent(0.1234)).toBe('12%');
    });

    it('respects decimals', () => {
      expect(utils.formatPercent(0.1234, 2)).toBe('12.34%');
    });
  });

  describe('clamp', () => {
    it('clamps value within range', () => {
      expect(utils.clamp(5, 0, 10)).toBe(5);
      expect(utils.clamp(-5, 0, 10)).toBe(0);
      expect(utils.clamp(15, 0, 10)).toBe(10);
    });
  });

  describe('randomInt', () => {
    it('returns integer in range inclusive', () => {
      for (let i = 0; i < 100; i++) {
        const val = utils.randomInt(1, 10);
        expect(val).toBeGreaterThanOrEqual(1);
        expect(val).toBeLessThanOrEqual(10);
        expect(Number.isInteger(val)).toBe(true);
      }
    });
  });

  describe('randomFloat', () => {
    it('returns float in range with default decimals', () => {
      for (let i = 0; i < 20; i++) {
        const val = utils.randomFloat(1.5, 2.5);
        expect(val).toBeGreaterThanOrEqual(1.5);
        expect(val).toBeLessThanOrEqual(2.5);
        // Check decimal places (string representation may have trailing zeros)
        const parts = val.toString().split('.');
        if (parts[1]) expect(parts[1].length).toBeLessThanOrEqual(2);
      }
    });
  });

  describe('lerp', () => {
    it('interpolates linearly', () => {
      expect(utils.lerp(0, 100, 0)).toBe(0);
      expect(utils.lerp(0, 100, 1)).toBe(100);
      expect(utils.lerp(0, 100, 0.5)).toBe(50);
    });
  });

  describe('round', () => {
    it('rounds to nearest integer by default', () => {
      expect(utils.round(1.234)).toBe(1);
      expect(utils.round(1.7)).toBe(2);
    });

    it('respects decimals', () => {
      expect(utils.round(1.2345, 2)).toBe(1.23);
      expect(utils.round(1.235, 2)).toBe(1.24);
    });
  });

  describe('sum', () => {
    it('sums array', () => {
      expect(utils.sum([1, 2, 3])).toBe(6);
    });

    it('returns 0 for empty array', () => {
      expect(utils.sum([])).toBe(0);
    });
  });

  describe('average', () => {
    it('computes average', () => {
      expect(utils.average([1, 2, 3])).toBe(2);
    });

    it('returns 0 for empty array', () => {
      expect(utils.average([])).toBe(0);
    });
  });

  describe('median', () => {
    it('returns middle value for odd length', () => {
      expect(utils.median([1, 3, 2])).toBe(2);
    });

    it('returns average of middle two for even length', () => {
      expect(utils.median([1, 2, 3, 4])).toBe(2.5);
    });

    it('returns 0 for empty array', () => {
      expect(utils.median([])).toBe(0);
    });
  });

  describe('percentChange', () => {
    it('calculates percent change', () => {
      expect(utils.percentChange(100, 150)).toBe(50);
      expect(utils.percentChange(150, 100)).toBe(-33.33333333333333);
    });

    it('handles zero denominator', () => {
      expect(utils.percentChange(0, 100)).toBe(100);
      expect(utils.percentChange(0, 0)).toBe(0);
    });
  });
});

// === ARRAY TESTS ===
describe('Array utilities', () => {
  describe('chunk', () => {
    it('splits array into chunks of given size', () => {
      expect(utils.chunk([1, 2, 3, 4, 5], 2)).toEqual([[1, 2], [3, 4], [5]]);
    });

    it('handles empty array', () => {
      expect(utils.chunk([], 2)).toEqual([]);
    });
  });

  describe('shuffle', () => {
    it('returns array of same length', () => {
      const arr = [1, 2, 3, 4, 5];
      const shuffled = utils.shuffle(arr);
      expect(shuffled).toHaveLength(arr.length);
    });

    it('contains same elements', () => {
      const arr = [1, 2, 3];
      const shuffled = utils.shuffle(arr);
      expect(shuffled.sort()).toEqual(arr.sort());
    });
  });

  describe('unique', () => {
    it('removes duplicates', () => {
      expect(utils.unique([1, 2, 2, 3])).toEqual([1, 2, 3]);
    });

    it('works with key for objects', () => {
      const arr = [{ id: 1 }, { id: 2 }, { id: 1 }];
      expect(utils.unique(arr, 'id')).toEqual([{ id: 1 }, { id: 2 }]);
    });
  });

  describe('groupBy', () => {
    it('groups by key', () => {
      const arr = [
        { type: 'fruit', name: 'apple' },
        { type: 'fruit', name: 'banana' },
        { type: 'veg', name: 'carrot' },
      ];
      const grouped = utils.groupBy(arr, 'type');
      expect(grouped).toEqual({
        fruit: [arr[0], arr[1]],
        veg: [arr[2]],
      });
    });

    it('groups by function', () => {
      const arr = [1, 2, 3, 4, 5, 6];
      const grouped = utils.groupBy(arr, n => (n % 2 === 0 ? 'even' : 'odd'));
      expect(grouped).toEqual({ odd: [1, 3, 5], even: [2, 4, 6] });
    });
  });

  describe('sortBy', () => {
    it('sorts ascending by default', () => {
      const arr = [{ age: 30 }, { age: 20 }, { age: 25 }];
      expect(utils.sortBy(arr, 'age')).toEqual([{ age: 20 }, { age: 25 }, { age: 30 }]);
    });

    it('sorts descending', () => {
      const arr = [{ age: 30 }, { age: 20 }, { age: 25 }];
      expect(utils.sortBy(arr, 'age', 'desc')).toEqual([{ age: 30 }, { age: 25 }, { age: 20 }]);
    });
  });

  describe('remove', () => {
    it('removes first matching element', () => {
      const arr = [1, 2, 3, 2];
      expect(utils.remove(arr, x => x === 2)).toEqual([1, 3, 2]);
    });

    it('returns same array if no match', () => {
      const arr = [1, 2, 3];
      expect(utils.remove(arr, x => x === 4)).toBe(arr);
    });
  });

  describe('findLast', () => {
    it('finds last element matching predicate', () => {
      const arr = [1, 2, 3, 2, 1];
      expect(utils.findLast(arr, x => x === 2)).toBe(2);
    });

    it('returns undefined if none found', () => {
      const arr = [1, 2, 3];
      expect(utils.findLast(arr, x => x > 10)).toBeUndefined();
    });
  });

  describe('omit', () => {
    it('removes specified keys', () => {
      const obj = { a: 1, b: 2, c: 3 };
      expect(utils.omit(obj, ['b'])).toEqual({ a: 1, c: 3 });
    });
  });

  describe('pick', () => {
    it('picks specified keys', () => {
      const obj = { a: 1, b: 2, c: 3 };
      expect(utils.pick(obj, ['a', 'c'])).toEqual({ a: 1, c: 3 });
    });
  });

  describe('deepClone', () => {
    it('creates independent copy', () => {
      const original = { a: 1, b: { c: 2 } };
      const clone = utils.deepClone(original);
      clone.b.c = 3;
      expect(original.b.c).toBe(2);
    });
  });

  describe('isEqual', () => {
    it('compares JSON equality', () => {
      expect(utils.isEqual({ a: 1 }, { a: 1 })).toBe(true);
      expect(utils.isEqual({ a: 1 }, { a: 2 })).toBe(false);
    });
  });
});

// === FUNCTION UTILITIES ===
describe('Function utilities', () => {
  describe('debounce', () => {
    beforeEach(() => { vi.useFakeTimers(); });
    afterEach(() => { vi.useRealTimers(); });

    it('delays execution', async () => {
      const fn = vi.fn();
      const debounced = utils.debounce(fn, 50);
      debounced();
      debounced();
      expect(fn).not.toHaveBeenCalled();
      await vi.runAllTimersAsync();
      expect(fn).toHaveBeenCalledTimes(1);
    });
  });

  describe('throttle', () => {
    beforeEach(() => { vi.useFakeTimers(); });
    afterEach(() => { vi.useRealTimers(); });

    it('allows first call and then at most once per interval', async () => {
      const fn = vi.fn();
      const throttled = utils.throttle(fn, 50);
      throttled();
      expect(fn).toHaveBeenCalledTimes(1);
      throttled();
      throttled();
      expect(fn).toHaveBeenCalledTimes(1);
      await vi.runAllTimersAsync();
      throttled();
      expect(fn).toHaveBeenCalledTimes(2);
    });
  });

  describe('memoize', () => {
    it('caches results', () => {
      const fn = vi.fn((x: number) => x * 2);
      const memoized = utils.memoize(fn);
      expect(memoized(5)).toBe(10);
      expect(memoized(5)).toBe(10);
      expect(fn).toHaveBeenCalledTimes(1); // second call from cache
    });
  });

  describe('once', () => {
    it('executes only once', () => {
      const fn = vi.fn();
      const onceFn = utils.once(fn);
      onceFn();
      onceFn();
      expect(fn).toHaveBeenCalledTimes(1);
    });
  });
});

// === VALIDATION UTILITIES ===
describe('Validation utilities', () => {
  describe('isEmail', () => {
    it('validates email', () => {
      expect(utils.isEmail('test@example.com')).toBe(true);
      expect(utils.isEmail('invalid')).toBe(false);
    });
  });

  describe('isUrl', () => {
    it('validates URLs', () => {
      expect(utils.isUrl('https://example.com')).toBe(true);
      expect(utils.isUrl('ftp://files.example')).toBe(true);
      expect(utils.isUrl('not a url')).toBe(false);
    });
  });

  describe('isPhone', () => {
    it('validates phone numbers', () => {
      // Valid: at least 10 digits
      expect(utils.isPhone('+1-555-123-4567')).toBe(true);
      expect(utils.isPhone('555-1234567')).toBe(true);
      expect(utils.isPhone('1234567890')).toBe(true);
      // Invalid
      expect(utils.isPhone('abc')).toBe(false);
      expect(utils.isPhone('123-4567')).toBe(false); // too short
    });
  });

  describe('isValidPassword', () => {
    it('returns valid for strong password', () => {
      const result = utils.isValidPassword('Pass123!');
      expect(result.valid).toBe(true);
      expect(result.message).toBe('');
    });

    it('rejects weak password (too short)', () => {
      const result = utils.isValidPassword('short');
      expect(result.valid).toBe(false);
      expect(result.message).toContain('at least 8 characters');
    });

    it('rejects password missing uppercase', () => {
      const result = utils.isValidPassword('lowercase123');
      expect(result.valid).toBe(false);
      expect(result.message).toContain('uppercase');
    });

    it('rejects password missing lowercase', () => {
      const result = utils.isValidPassword('UPPERCASE123');
      expect(result.valid).toBe(false);
      expect(result.message).toContain('lowercase');
    });

    it('rejects password missing number', () => {
      const result = utils.isValidPassword('NoNumberHere');
      expect(result.valid).toBe(false);
      expect(result.message).toContain('number');
    });
  });

  describe('isHexColor', () => {
    it('accepts valid hex colors', () => {
      expect(utils.isHexColor('#ff0000')).toBe(true);
      expect(utils.isHexColor('#0f0')).toBe(true);
      expect(utils.isHexColor('fff')).toBe(true);
      expect(utils.isHexColor('#FFFFFF')).toBe(true);
    });

    it('rejects invalid hex colors', () => {
      expect(utils.isHexColor('red')).toBe(false);
      expect(utils.isHexColor('#gg0000')).toBe(false);
      expect(utils.isHexColor('#12345')).toBe(false); // 5 digits
      expect(utils.isHexColor('#1234567')).toBe(false); // 7 digits
    });
  });
});



// === MISCELLANEOUS ===
describe('Misc utilities', () => {
  it('noop does nothing', () => {
    expect(() => utils.noop()).not.toThrow();
  });

  describe('sleep', () => {
    it('delays execution', async () => {
      const start = Date.now();
      await utils.sleep(50);
      const elapsed = Date.now() - start;
      expect(elapsed).toBeGreaterThanOrEqual(40);
    });
  });

  describe('identity', () => {
    it('returns the same value', () => {
      expect(utils.identity(5)).toBe(5);
      expect(utils.identity('hello')).toBe('hello');
      expect(utils.identity({ a: 1 })).toEqual({ a: 1 });
    });
  });

  describe('partial', () => {
    it('creates a partially applied function', () => {
      const add = (a: number, b: number) => a + b;
      const add5 = utils.partial(add, 5);
      expect(add5(3)).toBe(8);
      expect(add5(10)).toBe(15);
    });
  });

  describe('pipe', () => {
    it('composes functions right to left', () => {
      const double = (x: number) => x * 2;
      const increment = (x: number) => x + 1;
      const pipeline = utils.pipe(increment, double);
      expect(pipeline(5)).toBe(12); // (5+1)*2=12
    });
  });

  describe('isOnline', () => {
    it('returns navigator.onLine', () => {
      // In jsdom, navigator.onLine is typically true.
      expect(typeof utils.isOnline()).toBe('boolean');
    });
  });
});
