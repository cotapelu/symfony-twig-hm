import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import VoiceSearch from './VoiceSearch.svelte';

describe('VoiceSearch Component', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it('renders voice-search-btn button', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    expect(container.querySelector('.voice-search-btn')).not.toBeNull();
  });

  it('button has default placeholder text when not listening', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    expect(container.querySelector('.text')?.textContent).toContain('Click to speak');
  });

  it('button has custom placeholder text', async () => {
    const { container } = render(VoiceSearch, { props: { placeholder: 'Say something...' } });
    await tick();
    expect(container.querySelector('.text')?.textContent).toContain('Say something');
  });

  it('button has aria-label "Start voice search" initially', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    expect(container.querySelector('.voice-search-btn')?.getAttribute('aria-label')).toBe('Start voice search');
  });

  it('does not have listening class initially', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    expect(container.querySelector('.listening')).toBeNull();
  });

  it('renders 🔊 icon initially', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    expect(container.querySelector('.icon')?.textContent).toContain('🔊');
  });

  it('does not render transcript initially', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    expect(container.querySelector('.transcript')).toBeNull();
  });

  it('clicking button starts listening (no-throw with fake timers)', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    const btn = container.querySelector('.voice-search-btn');
    let error: any = null;
    try {
      if (btn) await fireEvent.click(btn);
      await tick();
    } catch (e) {
      error = e;
    }
    expect(error).toBeNull();
  });

  it('applies listening class after click', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    const btn = container.querySelector('.voice-search-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    expect(container.querySelector('.listening')).not.toBeNull();
  });

  it('renders 🎤 icon when listening', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    const btn = container.querySelector('.voice-search-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    expect(container.querySelector('.icon')?.textContent).toContain('🎤');
  });

  it('aria-label changes to "Stop voice search" when listening', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    const btn = container.querySelector('.voice-search-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    expect(container.querySelector('.voice-search-btn')?.getAttribute('aria-label')).toBe('Stop voice search');
  });

  it('renders transcript after simulated recognition completes', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    const btn = container.querySelector('.voice-search-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    // Fast-forward the 2-second simulation timer
    vi.advanceTimersByTime(2000);
    await tick();
    expect(container.querySelector('.transcript')?.textContent).toBe('Hello world');
  });

  it('removes listening class after recognition completes', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    const btn = container.querySelector('.voice-search-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    vi.advanceTimersByTime(2000);
    await tick();
    expect(container.querySelector('.listening')).toBeNull();
  });

  it('clicking twice toggles listening on then off immediately', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    const btn = container.querySelector('.voice-search-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
      expect(container.querySelector('.listening')).not.toBeNull();
      // Second click before timer fires — should stop immediately
      await fireEvent.click(btn);
      await tick();
      expect(container.querySelector('.listening')).toBeNull();
    }
  });

  it('renders text "Listening..." when isListening', async () => {
    const { container } = render(VoiceSearch);
    await tick();
    const btn = container.querySelector('.voice-search-btn');
    if (btn) {
      await fireEvent.click(btn);
      await tick();
    }
    expect(container.querySelector('.text')?.textContent).toContain('Listening');
  });
});
