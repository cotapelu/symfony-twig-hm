// Accessibility utilities and helpers - TypeScript Version

// ============================================================================
// TYPES
// ============================================================================

export type A11yPriority = 'polite' | 'assertive';

export interface FocusTrapOptions {
  container: HTMLElement;
  onEscape?: () => void;
}

export interface FocusTrapResult {
  cleanup: () => void;
  focusFirst: () => void;
  focusLast: () => void;
}

export interface KeyboardShortcut {
  callback: (event: KeyboardEvent) => void;
  description: string;
}

export interface SkipLinkOptions {
  targetId: string;
  text?: string;
  className?: string;
}

// ============================================================================
// ACCESSIBILITY UTILITIES
// ============================================================================

export const a11y = {
  // Generate unique IDs for ARIA relationships
  generateId: (): string => `a11y-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,

  // Announce content to screen readers
  announce: (message: string, priority: A11yPriority = 'polite'): void => {
    if (typeof document === 'undefined') return;

    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', priority);
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.style.cssText = `
      position: absolute;
      left: -10000px;
      width: 1px;
      height: 1px;
      overflow: hidden;
    `;

    document.body.appendChild(announcement);
    announcement.textContent = message;

    // Remove after announcement
    setTimeout(() => {
      if (document.body.contains(announcement)) {
        document.body.removeChild(announcement);
      }
    }, 1000);
  },

  // Focus trap for modals and dialogs
  trapFocus: (container: HTMLElement, onEscape?: () => void): FocusTrapResult => {
    const focusableSelectors = [
      'button:not([disabled])',
      '[href]',
      'input:not([disabled])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      '[tabindex]:not([tabindex="-1"])',
      'audio[controls]',
      'video[controls]',
      '[contenteditable="true"]',
    ].join(', ');

    const focusableElements = container.querySelectorAll<HTMLElement>(focusableSelectors);
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    const handleKeyDown = (e: KeyboardEvent): void => {
      if (e.key === 'Tab') {
        if (e.shiftKey) {
          if (document.activeElement === firstElement) {
            e.preventDefault();
            lastElement?.focus();
          }
        } else {
          if (document.activeElement === lastElement) {
            e.preventDefault();
            firstElement?.focus();
          }
        }
      }

      if (e.key === 'Escape' && onEscape) {
        e.preventDefault();
        onEscape();
      }
    };

    container.addEventListener('keydown', handleKeyDown);

    const cleanup = (): void => {
      container.removeEventListener('keydown', handleKeyDown);
    };

    const focusFirst = (): void => {
      firstElement?.focus();
    };

    const focusLast = (): void => {
      lastElement?.focus();
    };

    // Focus first element by default
    focusFirst();

    return { cleanup, focusFirst, focusLast };
  },

  // Remove focus trap
  removeTrapFocus: (container: HTMLElement, handler: (e: KeyboardEvent) => void): void => {
    container.removeEventListener('keydown', handler);
  },

  // Check if user prefers reduced motion
  prefersReducedMotion: (): boolean => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  },

  // Check if user prefers high contrast
  prefersHighContrast: (): boolean => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(prefers-contrast: high)').matches;
  },

  // Check if user prefers dark color scheme
  prefersDarkMode: (): boolean => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
  },

  // Check if user prefers reduced data
  prefersReducedData: (): boolean => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(prefers-reduced-data: reduce)').matches;
  },

  // Create skip link
  createSkipLink: (options: SkipLinkOptions): HTMLAnchorElement => {
    const { targetId, text = 'Skip to main content', className = 'skip-link' } = options;

    const skipLink = document.createElement('a');
    skipLink.href = `#${targetId}`;
    skipLink.textContent = text;
    skipLink.className = className;
    skipLink.style.cssText = `
      position: absolute;
      top: -40px;
      left: 6px;
      background: #000;
      color: #fff;
      padding: 8px 16px;
      text-decoration: none;
      border-radius: 4px;
      z-index: 9999;
      transition: top 0.2s ease-in-out;
      font-family: system-ui, sans-serif;
      font-size: 14px;
      font-weight: 500;
    `;

    skipLink.addEventListener('focus', () => {
      skipLink.style.top = '6px';
    });

    skipLink.addEventListener('blur', () => {
      skipLink.style.top = '-40px';
    });

    return skipLink;
  },

  // Get focusable elements within a container
  getFocusableElements: (container: HTMLElement): HTMLElement[] => {
    const focusableSelectors = [
      'button:not([disabled])',
      '[href]',
      'input:not([disabled])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      '[tabindex]:not([tabindex="-1"])',
    ].join(', ');

    return Array.from(container.querySelectorAll<HTMLElement>(focusableSelectors));
  },

  // Check if element is focusable
  isFocusable: (element: HTMLElement): boolean => {
    const focusableSelectors = [
      'button:not([disabled])',
      '[href]',
      'input:not([disabled])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      '[tabindex]:not([tabindex="-1"])',
    ];

    return focusableSelectors.some(selector => element.matches(selector));
  },

  // Set focus to element with error handling
  setFocus: (element: HTMLElement | string): boolean => {
    const target = typeof element === 'string' 
      ? document.getElementById(element) 
      : element;

    if (!target) {
      console.warn(`Element not found for focus: ${element}`);
      return false;
    }

    target.focus();
    return true;
  },

  // Live region management
  liveRegions: new Map<string, HTMLElement>(),

  // Create a live region for announcements
  createLiveRegion(id: string, priority: A11yPriority = 'polite'): HTMLElement {
    if (this.liveRegions.has(id)) {
      return this.liveRegions.get(id)!;
    }

    const region = document.createElement('div');
    region.id = `live-region-${id}`;
    region.setAttribute('role', 'status');
    region.setAttribute('aria-live', priority);
    region.setAttribute('aria-atomic', 'true');
    region.className = 'sr-only';
    region.style.cssText = `
      position: absolute;
      left: -10000px;
      width: 1px;
      height: 1px;
      overflow: hidden;
    `;

    document.body.appendChild(region);
    this.liveRegions.set(id, region);

    return region;
  },

  // Announce to a specific live region
  announceToRegion(id: string, message: string): void {
    const region = this.liveRegions.get(id) || this.createLiveRegion(id);
    region.textContent = '';
    
    // Use setTimeout to trigger aria-live
    setTimeout(() => {
      region.textContent = message;
    }, 100);
  },

  // Remove a live region
  removeLiveRegion(id: string): void {
    const region = this.liveRegions.get(id);
    if (region && document.body.contains(region)) {
      document.body.removeChild(region);
    }
    this.liveRegions.delete(id);
  },

  // Generate unique ARIA IDs for form elements
  generateAriaIds: (prefix: string = 'a11y'): { labelId: string; descId: string } => {
    const uniqueId = Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
    return {
      labelId: `${prefix}-label-${uniqueId}`,
      descId: `${prefix}-desc-${uniqueId}`,
    };
  },

  // Set aria-describedby for element
  setAriaDescribedBy: (element: HTMLElement, ...descriptions: string[]): void => {
    const existing = element.getAttribute('aria-describedby')?.split(' ') || [];
    const unique = [...new Set([...existing, ...descriptions])];
    element.setAttribute('aria-describedby', unique.join(' '));
  },

  // Remove aria-describedby reference
  removeAriaDescribedBy: (element: HTMLElement, ...descriptions: string[]): void => {
    const existing = element.getAttribute('aria-describedby')?.split(' ') || [];
    const filtered = existing.filter(d => !descriptions.includes(d));
    
    if (filtered.length > 0) {
      element.setAttribute('aria-describedby', filtered.join(' '));
    } else {
      element.removeAttribute('aria-describedby');
    }
  },
};

// ============================================================================
// KEYBOARD NAVIGATION HELPERS
// ============================================================================

export const keyboard = {
  // Handle arrow key navigation for lists
  handleArrowNavigation<T>(
    event: KeyboardEvent,
    items: T[],
    currentIndex: number,
    onSelect: (item: T | null, index: number) => void
  ): void {
    let newIndex = currentIndex;

    switch (event.key) {
      case 'ArrowDown':
      case 'PageDown':
        event.preventDefault();
        newIndex = Math.min(currentIndex + 1, items.length - 1);
        break;
      case 'ArrowUp':
      case 'PageUp':
        event.preventDefault();
        newIndex = Math.max(currentIndex - 1, 0);
        break;
      case 'Home':
        event.preventDefault();
        newIndex = 0;
        break;
      case 'End':
        event.preventDefault();
        newIndex = items.length - 1;
        break;
      case 'Enter':
      case ' ':
        event.preventDefault();
        onSelect(items[currentIndex], currentIndex);
        return;
      case 'Escape':
        event.preventDefault();
        onSelect(null, -1);
        return;
      default:
        return;
    }

    if (newIndex !== currentIndex) {
      onSelect(items[newIndex], newIndex);
    }
  },

  // Handle grid navigation
  handleGridNavigation(
    event: KeyboardEvent,
    row: number,
    col: number,
    rowCount: number,
    colCount: number,
    onSelect: (row: number, col: number) => void
  ): void {
    let newRow = row;
    let newCol = col;

    switch (event.key) {
      case 'ArrowRight':
        event.preventDefault();
        newCol = Math.min(col + 1, colCount - 1);
        break;
      case 'ArrowLeft':
        event.preventDefault();
        newCol = Math.max(col - 1, 0);
        break;
      case 'ArrowDown':
        event.preventDefault();
        newRow = Math.min(row + 1, rowCount - 1);
        break;
      case 'ArrowUp':
        event.preventDefault();
        newRow = Math.max(row - 1, 0);
        break;
      case 'Home':
        event.preventDefault();
        newCol = 0;
        break;
      case 'End':
        event.preventDefault();
        newCol = colCount - 1;
        break;
      case 'Enter':
      case ' ':
        event.preventDefault();
        onSelect(row, col);
        return;
      default:
        return;
    }

    if (newRow !== row || newCol !== col) {
      onSelect(newRow, newCol);
    }
  },

  // Keyboard shortcuts management
  shortcuts: new Map<string, KeyboardShortcut>(),

  // Register keyboard shortcut
  registerShortcut(
    keys: string | string[],
    callback: (event: KeyboardEvent) => void,
    description: string = ''
  ): void {
    const keyCombo = Array.isArray(keys) ? keys.join('+') : keys;
    this.shortcuts.set(keyCombo, { callback, description });
  },

  // Unregister keyboard shortcut
  unregisterShortcut(keys: string | string[]): void {
    const keyCombo = Array.isArray(keys) ? keys.join('+') : keys;
    this.shortcuts.delete(keyCombo);
  },

  // Handle keyboard shortcut
  handleShortcut(event: KeyboardEvent): boolean {
    const keys: string[] = [];
    
    if (event.ctrlKey || event.metaKey) keys.push('ctrl');
    if (event.altKey) keys.push('alt');
    if (event.shiftKey) keys.push('shift');
    keys.push(event.key.toLowerCase());

    const keyCombo = keys.join('+');
    const shortcut = this.shortcuts.get(keyCombo);

    if (shortcut) {
      event.preventDefault();
      shortcut.callback(event);
      return true;
    }

    return false;
  },

  // Common shortcuts
  common: {
    save: (callback: (e: KeyboardEvent) => void) => 
      keyboard.registerShortcut(['ctrl', 's'], callback, 'Save'),
    
    search: (callback: (e: KeyboardEvent) => void) => 
      keyboard.registerShortcut(['ctrl', 'k'], callback, 'Search'),
    
    escape: (callback: (e: KeyboardEvent) => void) => 
      keyboard.registerShortcut('Escape', callback, 'Escape'),
    
    tab: (callback: (e: KeyboardEvent) => void) => 
      keyboard.registerShortcut('Tab', callback, 'Tab'),
  },
};

// ============================================================================
// INITIALIZE GLOBAL KEYBOARD LISTENER
// ============================================================================

if (typeof window !== 'undefined') {
  document.addEventListener('keydown', (event) => {
    keyboard.handleShortcut(event);
  });
}

// ============================================================================
// ROAMDING HELPERS
// ============================================================================

export const roaming = {
  // Handle roving tabindex for menu/grid patterns
  handleRovingTabindex(
    event: KeyboardEvent,
    items: HTMLElement[],
    currentIndex: number
  ): number {
    let newIndex = currentIndex;

    switch (event.key) {
      case 'ArrowRight':
      case 'ArrowDown':
        event.preventDefault();
        newIndex = (currentIndex + 1) % items.length;
        break;
      case 'ArrowLeft':
      case 'ArrowUp':
        event.preventDefault();
        newIndex = (currentIndex - 1 + items.length) % items.length;
        break;
      case 'Home':
        event.preventDefault();
        newIndex = 0;
        break;
      case 'End':
        event.preventDefault();
        newIndex = items.length - 1;
        break;
      default:
        return currentIndex;
    }

    // Update tabindex
    items.forEach((item, i) => {
      item.setAttribute('tabindex', i === newIndex ? '0' : '-1');
    });

    // Focus new item
    items[newIndex]?.focus();

    return newIndex;
  },

  // Initialize roving tabindex
  initRovingTabindex(container: HTMLElement): void {
    const items = a11y.getFocusableElements(container);
    
    items.forEach((item, i) => {
      item.setAttribute('tabindex', i === 0 ? '0' : '-1');
      item.setAttribute('role', 'tab');
    });

    if (items.length > 0) {
      items[0].setAttribute('tabindex', '0');
    }
  },
};

// ============================================================================
// COLOR CONTRAST CHECKER
// ============================================================================

export const contrast = {
  // Calculate relative luminance
  getLuminance(hexColor: string): number {
    const rgb = this.hexToRgb(hexColor);
    if (!rgb) return 0;

    const [r, g, b] = [rgb.r, rgb.g, rgb.b].map(v => {
      v /= 255;
      return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    });

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
  },

  // Convert hex to RGB
  hexToRgb(hex: string): { r: number; g: number; b: number } | null {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
      r: parseInt(result[1], 16),
      g: parseInt(result[2], 16),
      b: parseInt(result[3], 16),
    } : null;
  },

  // Calculate contrast ratio
  getRatio(hex1: string, hex2: string): number {
    const lum1 = this.getLuminance(hex1);
    const lum2 = this.getLuminance(hex2);
    const lighter = Math.max(lum1, lum2);
    const darker = Math.min(lum1, lum2);
    return (lighter + 0.05) / (darker + 0.05);
  },

  // Check if contrast meets WCAG AA
  meetsAA(hex1: string, hex2: string, isLargeText: boolean = false): boolean {
    const ratio = this.getRatio(hex1, hex2);
    return isLargeText ? ratio >= 3 : ratio >= 4.5;
  },

  // Check if contrast meets WCAG AAA
  meetsAAA(hex1: string, hex2: string, isLargeText: boolean = false): boolean {
    const ratio = this.getRatio(hex1, hex2);
    return isLargeText ? ratio >= 4.5 : ratio >= 7;
  },
};

// Export default
export default {
  a11y,
  keyboard,
  roaming,
  contrast,
};
