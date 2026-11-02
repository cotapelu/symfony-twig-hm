import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import SmartReplySuggestions from './SmartReplySuggestions.svelte';

describe('SmartReplySuggestions Component', () => {
  it('does not render anything when no suggestions and not loading', async () => {
    const { container } = render(SmartReplySuggestions, {
      props: { suggestions: [], isLoading: false },
    });
    await tick();
    expect(container.querySelector('.smart-replies')).toBeNull();
    expect(container.querySelector('.smart-replies-loading')).toBeNull();
  });

  it('renders loading state when isLoading=true', async () => {
    render(SmartReplySuggestions, {
      props: { suggestions: [], isLoading: true },
    });
    await tick();
    expect(screen.getByText('Generating smart replies...')).not.toBeNull();
  });

  it('renders suggestions list when suggestions provided', async () => {
    render(SmartReplySuggestions, {
      props: { suggestions: ['Yes', 'No', 'Maybe'], isLoading: false },
    });
    await tick();
    expect(screen.getByText('Yes')).not.toBeNull();
    expect(screen.getByText('No')).not.toBeNull();
    expect(screen.getByText('Maybe')).not.toBeNull();
  });

  it('formats suggestion text by removing quotes', async () => {
    render(SmartReplySuggestions, {
      props: { suggestions: ['"Hello"', "'World'"], isLoading: false },
    });
    await tick();
    expect(screen.getByText('Hello')).not.toBeNull();
    expect(screen.getByText('World')).not.toBeNull();
  });

  it('trims whitespace from suggestion text', async () => {
    render(SmartReplySuggestions, {
      props: { suggestions: ['  spaced  '], isLoading: false },
    });
    await tick();
    expect(screen.getByText('spaced')).not.toBeNull();
  });

  it('handles click on suggestion button without error', async () => {
    render(SmartReplySuggestions, {
      props: { suggestions: ['Reply A'], isLoading: false },
    });
    await tick();

    const btn = screen.getByText('Reply A').closest('button');
    expect(btn).not.toBeNull();
    if (btn) {
      await fireEvent.click(btn);
    }
  });

  it('renders only loading spinner when loading with empty suggestions', async () => {
    const { container } = render(SmartReplySuggestions, {
      props: { suggestions: [], isLoading: true },
    });
    await tick();
    expect(container.querySelector('.smart-replies')).toBeNull();
    expect(container.querySelector('.smart-replies-loading')).not.toBeNull();
  });

  it('renders suggestion buttons for all provided items', async () => {
    render(SmartReplySuggestions, {
      props: {
        suggestions: ['A', 'B', 'C', 'D', 'E', 'F'],
        isLoading: false,
      },
    });
    await tick();
    expect(screen.getByText('A')).not.toBeNull();
    expect(screen.getByText('B')).not.toBeNull();
    expect(screen.getByText('C')).not.toBeNull();
    expect(screen.getByText('D')).not.toBeNull();
    expect(screen.getByText('E')).not.toBeNull();
    expect(screen.getByText('F')).not.toBeNull();
  });

  it('shows smart replies header', async () => {
    render(SmartReplySuggestions, {
      props: { suggestions: ['One'], isLoading: false },
    });
    await tick();
    expect(screen.getByText(/Smart replies/i)).not.toBeNull();
  });

  it('does not render suggestions when isLoading=true even with suggestions', async () => {
    render(SmartReplySuggestions, {
      props: { suggestions: ['Yes'], isLoading: true },
    });
    await tick();
    expect(screen.queryByText('Yes')).toBeNull();
    expect(screen.getByText('Generating smart replies...')).not.toBeNull();
  });

  it('handles empty suggestions array with loading false', async () => {
    const { container } = render(SmartReplySuggestions, {
      props: { suggestions: [], isLoading: false },
    });
    await tick();
    expect(container.querySelector('.smart-replies')).toBeNull();
  });

  it('renders multiple suggestions', async () => {
    render(SmartReplySuggestions, {
      props: {
        suggestions: ['One', 'Two', 'Three', 'Four', 'Five'],
        isLoading: false,
      },
    });
    await tick();
    const items = screen.getAllByRole('button');
    expect(items.length).toBeGreaterThanOrEqual(5);
  });

  it('handles suggestions with special characters', async () => {
    render(SmartReplySuggestions, {
      props: { suggestions: ['Yes!', 'No...'], isLoading: false },
    });
    await tick();
    expect(screen.getByText('Yes!')).not.toBeNull();
    expect(screen.getByText('No...')).not.toBeNull();
  });
});
