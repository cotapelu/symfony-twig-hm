import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import ThemeSwitcher from './ThemeSwitcher.svelte';

// Mock localStorage and matchMedia
beforeEach(() => {
  Object.defineProperty(window, 'localStorage', {
    value: {
      getItem: vi.fn(() => null),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn()
    },
    writable: true
  });

  window.matchMedia = vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn()
  }));
});

describe('ThemeSwitcher Component', () => {
  it('renders all theme options', () => {
    render(ThemeSwitcher);
    expect(screen.getByText('Light')).toBeTruthy();
    expect(screen.getByText('Dark')).toBeTruthy();
    expect(screen.getByText('Auto')).toBeTruthy();
    expect(screen.getByText('Cyberpunk')).toBeTruthy();
  });

  it('marks current theme as active by default (auto)', () => {
    render(ThemeSwitcher);
    const autoBtn = screen.getByText('Auto').closest('button');
    expect(autoBtn).toHaveClass('active');
  });

  it('changes active theme on button click', async () => {
    render(ThemeSwitcher);
    const darkBtn = screen.getByText('Dark').closest('button');
    await fireEvent.click(darkBtn);

    expect(darkBtn).toHaveClass('active');
    const autoBtn = screen.getByText('Auto').closest('button');
    expect(autoBtn).not.toHaveClass('active');
  });

  it('calls localStorage.setItem when theme changed', async () => {
    const setItemSpy = vi.fn();
    window.localStorage.setItem = setItemSpy;
    render(ThemeSwitcher);
    const cyberBtn = screen.getByText('Cyberpunk').closest('button');
    await fireEvent.click(cyberBtn);
    expect(setItemSpy).toHaveBeenCalledWith('theme', 'cyberpunk');
  });
});
