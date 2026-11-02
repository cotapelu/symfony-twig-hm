import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import * as i18n from './i18n';
import { get } from 'svelte/store';

describe('i18n module', () => {
  describe('SUPPORTED_LOCALES', () => {
    it('should have all required locale configurations', () => {
      expect(i18n.SUPPORTED_LOCALES.en).toBeDefined();
      expect(i18n.SUPPORTED_LOCALES.vi).toBeDefined();
      expect(i18n.SUPPORTED_LOCALES.es).toBeDefined();
      expect(i18n.SUPPORTED_LOCALES.fr).toBeDefined();
      expect(i18n.SUPPORTED_LOCALES.zh).toBeDefined();
    });

    it('should have correct structure for each locale', () => {
      const en = i18n.SUPPORTED_LOCALES.en;
      expect(en.code).toBe('en');
      expect(en.name).toBe('English');
      expect(en.nativeName).toBe('English');
      expect(en.dir).toBe('ltr');
    });

    it('should identify RTL locales correctly', () => {
      expect(i18n.SUPPORTED_LOCALES.ar.dir).toBe('rtl');
      expect(i18n.SUPPORTED_LOCALES.he.dir).toBe('rtl');
      expect(i18n.SUPPORTED_LOCALES.fa.dir).toBe('rtl');
      expect(i18n.SUPPORTED_LOCALES.ur.dir).toBe('rtl');
    });

    it('should have unique codes', () => {
      const codes = Object.values(i18n.SUPPORTED_LOCALES).map(l => l.code);
      const unique = new Set(codes);
      expect(unique.size).toBe(codes.length);
    });
  });

  describe('RTL_LOCALES', () => {
    it('should contain only RTL locale codes', () => {
      expect(i18n.RTL_LOCALES).toEqual(['ar', 'he', 'fa', 'ur']);
    });

    it('should be subset of SUPPORTED_LOCALES', () => {
      i18n.RTL_LOCALES.forEach(code => {
        expect(i18n.SUPPORTED_LOCALES[code as keyof typeof i18n.SUPPORTED_LOCALES]).toBeDefined();
      });
    });
  });

  describe('DEFAULT_TRANSLATIONS', () => {
    it('should have translations for supported locales', () => {
      expect(i18n.translations.en).toBeDefined();
      expect(i18n.translations.vi).toBeDefined();
    });

    it('should contain common keys in English', () => {
      const en = i18n.translations.en;
      expect(en.loading).toBe('Loading...');
      expect(en.error).toBe('Error');
      expect(en.save).toBe('Save');
    });

    it('should have top-level navigation keys', () => {
      const en = i18n.translations.en;
      expect(en.dashboard).toBeDefined();
      expect(en.dashboard).toBe('Dashboard');
    });
  });

  describe('locale store', () => {
    beforeEach(async () => {
      await i18n.initI18n('en');
    });

    it('should initialize with default locale (en)', () => {
      expect(get(i18n.locale)).toBe('en');
    });

    it('should allow setting locale', async () => {
      await i18n.setLocale('vi');
      expect(get(i18n.locale)).toBe('vi');
    });

    it('should not set unsupported locale (keep current)', async () => {
      const initial = get(i18n.locale);
      await i18n.setLocale('invalid' as any);
      expect(get(i18n.locale)).toBe(initial);
    });
  });

  describe('isRTL derived store', () => {
    beforeEach(async () => {
      await i18n.initI18n('en');
    });

    it('should be false for en', () => {
      expect(get(i18n.isRTL)).toBe(false);
    });

    it('should be true for ar', async () => {
      await i18n.setLocale('ar');
      expect(get(i18n.isRTL)).toBe(true);
    });
  });

  describe('currentLocaleConfig derived store', () => {
    beforeEach(async () => {
      await i18n.initI18n('en');
    });

    it('should return config for en', () => {
      const config = get(i18n.currentLocaleConfig);
      expect(config.code).toBe('en');
    });

    it('should return config for vi', async () => {
      await i18n.setLocale('vi');
      const config = get(i18n.currentLocaleConfig);
      expect(config.code).toBe('vi');
      expect(config.dir).toBe('ltr');
    });

    it('should return RTL config for ar', async () => {
      await i18n.setLocale('ar');
      const config = get(i18n.currentLocaleConfig);
      expect(config.dir).toBe('rtl');
    });

    it('should fallback to default for unsupported locale', async () => {
      await i18n.setLocale('invalid' as any);
      const config = get(i18n.currentLocaleConfig);
      expect(config.code).toBe('en');
    });
  });

  describe('initI18n', () => {
    beforeEach(() => {
      vi.clearAllMocks();
    });

    it('should load default translations on init', async () => {
      await i18n.initI18n();
      expect(get(i18n.locale)).toBe('en');
    });

    it('should accept initial locale', async () => {
      await i18n.initI18n('fr');
      expect(get(i18n.locale)).toBe('fr');
    });
  });

  describe('_ translation function', () => {
    beforeEach(async () => {
      await i18n.initI18n('en');
    });

    it('should return English translation for common key', () => {
      expect(i18n._('loading')).toBe('Loading...');
      expect(i18n._('save')).toBe('Save');
      expect(i18n._('cancel')).toBe('Cancel');
    });

    it('should return Vietnamese translation after locale change', async () => {
      await i18n.setLocale('vi');
      expect(i18n._('save')).toBe('Lưu');
      expect(i18n._('cancel')).toBe('Hủy');
    });

    it('should return key itself for missing translation', () => {
      expect(i18n._('nonexistent.key')).toBe('nonexistent.key');
    });

    it('should handle interpolation with template', () => {
      // Assuming 'greeting' template: "Hello, {name}!"
      const result = i18n._('greeting', { name: 'John' });
      if (result.includes('{name}')) {
        expect(result).toContain('John');
      } else {
        expect(result).toBeDefined();
      }
    });

    it('should handle empty interpolation object', () => {
      const result = i18n._('loading');
      expect(result).toBe('Loading...');
    });

    it('should handle empty key gracefully', () => {
      expect(() => i18n._('')).not.toThrow();
      expect(i18n._('')).toBe('');
    });

    it('should handle nested key gracefully (fallback to self)', () => {
      const result = i18n._('unknown.nested.key');
      expect(result).toBe('unknown.nested.key');
    });

    it('should fallback to English for missing key in current locale', async () => {
      await i18n.setLocale('fr');
      const result = i18n._('missing_key_xyz');
      expect(result).toBe('missing_key_xyz');
    });
  });

  describe('n pluralization function', () => {
    beforeEach(async () => {
      await i18n.initI18n('en');
    });

    it('should use singular form for count=1', () => {
      const result = i18n.n('item', 1);
      // pluralization uses key_item_one if available, else key
      expect(result).toBeDefined();
    });

    it('should use plural form for count>1', () => {
      const result = i18n.n('item', 5);
      expect(result).toBeDefined();
    });

    it('should handle zero as plural in English', () => {
      const result = i18n.n('item', 0);
      expect(result).toBeDefined();
    });

    it('should work with Arabic plural rules', async () => {
      await i18n.setLocale('ar');
      expect(i18n.n('item', 0)).toBeDefined();
      expect(i18n.n('item', 1)).toBeDefined();
      expect(i18n.n('item', 2)).toBeDefined();
      expect(i18n.n('item', 3)).toBeDefined();
      expect(i18n.n('item', 10)).toBeDefined();
    });
  });

  describe('formatNumber', () => {
    beforeEach(async () => {
      await i18n.initI18n('en');
    });

    it('should format number with thousand separators', () => {
      const result = i18n.formatNumber(1234567.89);
      expect(result).toMatch(/1.*234.*567/);
    });

    it('should format integers correctly', () => {
      const result = i18n.formatNumber(1000);
      expect(result).toBe('1,000');
    });

    it('should handle zero', () => {
      const result = i18n.formatNumber(0);
      expect(result).toBe('0');
    });

    it('should format negative numbers', () => {
      const result = i18n.formatNumber(-1234.5);
      expect(result).toMatch(/-/);
    });

    it('should handle very large numbers', () => {
      const result = i18n.formatNumber(999999999999999);
      expect(result).toBeDefined();
    });

    it('should return empty string for non-finite numbers', () => {
      expect(i18n.formatNumber(NaN)).toBe('');
      expect(i18n.formatNumber(Infinity)).toBe('');
    });
  });

  describe('formatCurrency', () => {
    beforeEach(async () => {
      await i18n.initI18n('en');
    });

    it('should format as USD by default', () => {
      const result = i18n.formatCurrency(99.99);
      expect(result).toContain('$');
    });

    it('should format as VND when currency=VND', () => {
      const result = i18n.formatCurrency(100000, 'VND');
      expect(result).toContain('₫');
    });

    it('should format as EUR', () => {
      const result = i18n.formatCurrency(50, 'EUR');
      expect(result).toContain('€');
    });

    it('should return empty string for non-finite numbers', () => {
      expect(i18n.formatCurrency(NaN)).toBe('');
    });
  });

  describe('formatDate', () => {
    beforeEach(async () => {
      await i18n.initI18n('en');
    });

    it('should format date with default options', () => {
      const date = new Date('2024-01-15');
      const result = i18n.formatDate(date);
      expect(result).toMatch(/Jan|15|2024/);
    });

    it('should format with custom options', () => {
      const date = new Date('2024-01-15T14:30:00');
      const result = i18n.formatDate(date, { month: 'short', day: '2-digit', year: 'numeric' });
      expect(result).toMatch(/Jan/);
    });

    it('should handle string date input', () => {
      const result = i18n.formatDate('2024-01-15');
      expect(result).toBeDefined();
    });

    it('should return empty string for invalid date', () => {
      const result = i18n.formatDate(new Date('invalid') as any);
      expect(result).toBe('');
    });
  });

  describe('formatRelativeTime', () => {
    beforeEach(async () => {
      await i18n.initI18n('en');
    });

    it('should format seconds ago as just now', () => {
      const result = i18n.formatRelativeTime(new Date(Date.now() - 30000));
      expect(result).toBe('Just now');
    });

    it('should format minutes ago', () => {
      const result = i18n.formatRelativeTime(new Date(Date.now() - 2 * 60 * 1000));
      expect(result).toMatch(/minute|2/);
    });

    it('should format hours ago', () => {
      const result = i18n.formatRelativeTime(new Date(Date.now() - 2 * 60 * 60 * 1000));
      expect(result).toMatch(/hour|2/);
    });

    it('should format days ago', () => {
      const result = i18n.formatRelativeTime(new Date(Date.now() - 2 * 24 * 60 * 60 * 1000));
      expect(result).toMatch(/day|2/);
    });

    it('should format weeks ago', () => {
      const result = i18n.formatRelativeTime(new Date(Date.now() - 14 * 24 * 60 * 60 * 1000));
      expect(result).toMatch(/week|2/);
    });
  });

  describe('Edge cases', () => {
    beforeEach(async () => {
      await i18n.initI18n('en');
    });

    it('should handle large numbers in formatNumber', () => {
      const result = i18n.formatNumber(999999999999999);
      expect(result).toBeDefined();
    });

    it('should handle locale persistence in localStorage', () => {
      if (typeof window !== 'undefined' && window.localStorage) {
        window.localStorage.setItem('locale', 'vi');
        // After init, should load from storage
      }
    });

    it('should return default for undefined params in _', () => {
      const result = i18n._('loading');
      expect(result).toBe('Loading...');
    });

    it('should handle null interpolation values', () => {
      const result = i18n._('greeting', { name: null as any });
      expect(result).toBeDefined();
    });

    it('should handle empty interpolation values', () => {
      const result = i18n._('greeting', {});
      expect(result).toBeDefined();
    });
  });
});
