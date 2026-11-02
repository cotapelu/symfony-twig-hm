import { describe, it, expect, beforeEach } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import LanguageSwitcher from './LanguageSwitcher.svelte';
import { locale } from '$lib/i18n';

describe('LanguageSwitcher Component', () => {
  beforeEach(() => {
    locale.set('en');
    localStorage.clear();
  });

  it('renders wrapper with language-switcher class', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    expect(container.querySelector('.language-switcher')).not.toBeNull();
  });

  it('renders lang-btn trigger button', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    expect(btn).not.toBeNull();
  });

  it('trigger button has aria-expanded=false initially', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    expect(btn?.getAttribute('aria-expanded')).toBe('false');
  });

  it('trigger button has aria-label for accessibility', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    expect(btn?.getAttribute('aria-label')).toBe('Change language');
  });

  it('renders default language name English', async () => {
    render(LanguageSwitcher);
    await tick();
    expect(screen.getByText('English')).not.toBeNull();
  });

  it('renders default language flag 🇺🇸', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const flag = container.querySelector('.lang-flag');
    expect(flag?.textContent).toBe('🇺🇸');
  });

  it('renders dropdown arrow', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    expect(container.querySelector('.dropdown-arrow')).not.toBeNull();
  });

  it('does not render dropdown initially', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    expect(container.querySelector('.lang-dropdown')).toBeNull();
  });

  it('clicking trigger opens dropdown', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    expect(container.querySelector('.lang-dropdown')).not.toBeNull();
  });

  it('aria-expanded becomes true after trigger click', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    expect(btn?.getAttribute('aria-expanded')).toBe('true');
  });

  it('dropdown applies open class to arrow after click', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    expect(container.querySelector('.dropdown-arrow.open')).not.toBeNull();
  });

  it('dropdown contains all 4 languages', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    const options = container.querySelectorAll('.lang-option');
    expect(options.length).toBe(4);
  });

  it('dropdown contains all language names', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    // English appears in both trigger and dropdown - use getAllByText
    expect(screen.getAllByText('English').length).toBe(2);
    expect(screen.getByText('Tiếng Việt')).not.toBeNull();
    expect(screen.getByText('Español')).not.toBeNull();
    expect(screen.getByText('Français')).not.toBeNull();
  });

  it('dropdown options have role=menuitem', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    const menuitems = container.querySelectorAll('[role="menuitem"]');
    expect(menuitems.length).toBe(4);
  });

  it('dropdown container has role=menu', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    expect(container.querySelector('[role="menu"]')).not.toBeNull();
  });

  it('current language option has active class', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    const activeOptions = container.querySelectorAll('.lang-option.active');
    expect(activeOptions.length).toBe(1);
  });

  it('current language option has aria-current=true', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    const activeOption = container.querySelector('.lang-option.active');
    expect(activeOption?.getAttribute('aria-current')).toBe('true');
  });

  it('current language option shows checkmark', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    expect(container.querySelector('.checkmark')).not.toBeNull();
  });

  it('clicking language option calls selectLanguage and closes dropdown', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    // Click Vietnamese option
    const viOption = Array.from(container.querySelectorAll('.lang-option')).find(
      (el) => el.textContent?.includes('Tiếng Việt')
    );
    if (viOption) {
      await fireEvent.click(viOption);
      await tick();
    }
    expect(container.querySelector('.lang-dropdown')).toBeNull();
  });

  it('clicking language option updates locale store', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    const viOption = Array.from(container.querySelectorAll('.lang-option')).find(
      (el) => el.textContent?.includes('Tiếng Việt')
    );
    if (viOption) {
      await fireEvent.click(viOption);
      await tick();
    }
    // Check that the trigger button now shows Vietnamese
    expect(screen.getByText('Tiếng Việt')).not.toBeNull();
  });

  it('clicking language option saves to localStorage', async () => {
    const { container } = render(LanguageSwitcher);
    await tick();
    const btn = container.querySelector('.lang-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    const esOption = Array.from(container.querySelectorAll('.lang-option')).find(
      (el) => el.textContent?.includes('Español')
    );
    if (esOption) {
      await fireEvent.click(esOption);
      await tick();
    }
    expect(localStorage.getItem('preferred-language')).toBe('es');
  });

  it('reflects initial locale from store', async () => {
    locale.set('fr');
    const { container } = render(LanguageSwitcher);
    await tick();
    const flag = container.querySelector('.lang-flag');
    expect(flag?.textContent).toBe('🇫🇷');
  });

  it('reflects restored locale from localStorage on mount', async () => {
    locale.set('en');
    localStorage.setItem('preferred-language', 'vi');
    render(LanguageSwitcher);
    await tick();
    expect(screen.getByText('Tiếng Việt')).not.toBeNull();
  });
});
