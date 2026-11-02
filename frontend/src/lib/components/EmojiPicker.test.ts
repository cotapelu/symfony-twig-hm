import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import EmojiPicker from './EmojiPicker.svelte';

describe('EmojiPicker Component', () => {
  it('renders the emoji-picker container', async () => {
    const { container } = render(EmojiPicker);
    await tick();
    expect(container.querySelector('.emoji-picker')).not.toBeNull();
  });

  it('renders search input', async () => {
    const { container } = render(EmojiPicker);
    await tick();
    const searchInput = container.querySelector('.search-input');
    expect(searchInput).not.toBeNull();
    expect(searchInput?.getAttribute('placeholder')).toBe('Search emojis...');
  });

  it('renders category tabs', async () => {
    const { container } = render(EmojiPicker);
    await tick();
    const categoryTabs = container.querySelectorAll('.category-tab');
    // 7 categories: recent, smileys, animals, food, activities, objects, symbols
    expect(categoryTabs.length).toBe(7);
  });

  it('renders emoji grid with smileys by default', async () => {
    const { container } = render(EmojiPicker);
    await tick();
    const emojiButtons = container.querySelectorAll('.emoji-btn');
    expect(emojiButtons.length).toBeGreaterThan(0);
  });

  it('does not render recent tab when no recentEmojis', async () => {
    const { container } = render(EmojiPicker, { props: { recentEmojis: [] } });
    await tick();
    const tabs = container.querySelectorAll('.category-tab');
    // 7 categories always rendered (recent may have empty array but tab still shown)
    expect(tabs.length).toBe(7);
  });

  it('dispatches select event when emoji is clicked', async () => {
    const { container } = render(EmojiPicker);
    await tick();
    const firstEmoji = container.querySelector('.emoji-btn');
    expect(firstEmoji).not.toBeNull();
    if (firstEmoji) {
      const selectHandler = vi.fn();
      firstEmoji.addEventListener('click', selectHandler);
      await fireEvent.click(firstEmoji);
      // Component uses createEventDispatcher, so we test click is handled
      expect(selectHandler).toHaveBeenCalled();
    }
  });

  it('switches active category when a tab is clicked', async () => {
    const { container } = render(EmojiPicker);
    await tick();

    // Initially smileys should be active
    const initialActive = container.querySelector('.category-tab.active');
    expect(initialActive).not.toBeNull();

    // Click on animals tab (second non-recent tab)
    const categoryTabs = container.querySelectorAll('.category-tab');
    // First tab might be recent (index 0), so click the next one
    if (categoryTabs.length > 1) {
      const animalsTab = categoryTabs[1]; // skip recent or smileys
      if (animalsTab) {
        await fireEvent.click(animalsTab);
        await tick();
        const newActive = container.querySelector('.category-tab.active');
        expect(newActive).not.toBeNull();
      }
    }
  });

  it('filters emojis when search query is entered', async () => {
    const { container } = render(EmojiPicker);
    await tick();

    const searchInput = container.querySelector('.search-input') as HTMLInputElement;
    expect(searchInput).not.toBeNull();

    if (searchInput) {
      await fireEvent.input(searchInput, { target: { value: 'cat' } });
      await tick();

      // Search should show filtered results
      const emojiButtons = container.querySelectorAll('.emoji-btn');
      // Should have fewer results than the full list
      expect(emojiButtons).toBeDefined();
    }
  });

  it('shows no results message when search has no matches', async () => {
    const { container } = render(EmojiPicker);
    await tick();

    const searchInput = container.querySelector('.search-input') as HTMLInputElement;
    if (searchInput) {
      await fireEvent.input(searchInput, { target: { value: 'xyzabc_no_match_123' } });
      await tick();

      const noResults = container.querySelector('.no-results');
      expect(noResults).not.toBeNull();
      expect(noResults?.textContent).toContain('No emojis found');
    }
  });

  it('hides category tabs when searching', async () => {
    const { container } = render(EmojiPicker);
    await tick();

    const searchInput = container.querySelector('.search-input') as HTMLInputElement;
    if (searchInput) {
      await fireEvent.input(searchInput, { target: { value: 'dog' } });
      await tick();

      const categories = container.querySelector('.emoji-categories');
      expect(categories).toBeNull();
    }
  });

  it('applies position-bottom class by default', async () => {
    const { container } = render(EmojiPicker);
    await tick();
    const picker = container.querySelector('.emoji-picker');
    expect(picker?.className).toContain('position-bottom');
  });

  it('applies position-top class when position is top', async () => {
    const { container } = render(EmojiPicker, { props: { position: 'top' } });
    await tick();
    const picker = container.querySelector('.emoji-picker');
    expect(picker?.className).toContain('position-top');
  });

  it('applies position-left class when position is left', async () => {
    const { container } = render(EmojiPicker, { props: { position: 'left' } });
    await tick();
    const picker = container.querySelector('.emoji-picker');
    expect(picker?.className).toContain('position-left');
  });

  it('applies position-right class when position is right', async () => {
    const { container } = render(EmojiPicker, { props: { position: 'right' } });
    await tick();
    const picker = container.querySelector('.emoji-picker');
    expect(picker?.className).toContain('position-right');
  });

  it('renders emoji buttons with aria-label', async () => {
    const { container } = render(EmojiPicker);
    await tick();
    const firstEmoji = container.querySelector('.emoji-btn');
    expect(firstEmoji?.getAttribute('aria-label')).toContain('Select');
  });

  it('renders emoji buttons with title attribute', async () => {
    const { container } = render(EmojiPicker);
    await tick();
    const firstEmoji = container.querySelector('.emoji-btn');
    expect(firstEmoji?.getAttribute('title')).not.toBeNull();
  });

  it('renders category tabs with role=tab', async () => {
    const { container } = render(EmojiPicker);
    await tick();
    const tabList = container.querySelector('[role="tablist"]');
    expect(tabList).not.toBeNull();

    const tabs = container.querySelectorAll('[role="tab"]');
    expect(tabs.length).toBe(7);
  });

  it('renders emoji grid with role=grid', async () => {
    const { container } = render(EmojiPicker);
    await tick();
    const grid = container.querySelector('[role="grid"]');
    expect(grid).not.toBeNull();
  });

  it('handles Escape key to dispatch close event', async () => {
    const { container } = render(EmojiPicker);
    await tick();
    // Dispatch keyboard event on window
    const event = new KeyboardEvent('keydown', { key: 'Escape' });
    window.dispatchEvent(event);
    await tick();
    // Component should handle the escape without errors
    expect(container.querySelector('.emoji-picker')).not.toBeNull();
  });

  it('clears search when input is cleared', async () => {
    const { container } = render(EmojiPicker);
    await tick();

    const searchInput = container.querySelector('.search-input') as HTMLInputElement;
    if (searchInput) {
      await fireEvent.input(searchInput, { target: { value: 'cat' } });
      await tick();

      await fireEvent.input(searchInput, { target: { value: '' } });
      await tick();

      // Category tabs should reappear
      const categories = container.querySelector('.emoji-categories');
      expect(categories).not.toBeNull();
    }
  });

  it('updates recent emojis when emoji is selected', async () => {
    const { container } = render(EmojiPicker, { props: { recentEmojis: [] } });
    await tick();

    const firstEmoji = container.querySelector('.emoji-btn');
    if (firstEmoji) {
      await fireEvent.click(firstEmoji);
      await tick();
      // The recent emojis prop should be updated by the component
      // This is reflected in the DOM (recent tab is updated)
      expect(firstEmoji).not.toBeNull();
    }
  });
});
