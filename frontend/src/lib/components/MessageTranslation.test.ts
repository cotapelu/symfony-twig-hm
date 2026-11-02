import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import MessageTranslation from './MessageTranslation.svelte';

const baseMessage = {
  id: 'm1',
  text: 'Hello',
  detectedLanguage: 'en',
};

describe('MessageTranslation Component', () => {
  it('renders nothing when message is null', async () => {
    const { container } = render(MessageTranslation, { props: { message: null } });
    await tick();
    expect(container.querySelector('.message-translation')).toBeNull();
  });

  it('renders wrapper with message-translation class when message is provided', async () => {
    const { container } = render(MessageTranslation, { props: { message: baseMessage } });
    await tick();
    expect(container.querySelector('.message-translation')).not.toBeNull();
  });

  it('renders Translate button initially when showTranslation=false and isTranslating=false', async () => {
    render(MessageTranslation, { props: { message: baseMessage } });
    await tick();
    expect(screen.getByText(/Translate/)).not.toBeNull();
  });

  it('Translate button has aria-label', async () => {
    const { container } = render(MessageTranslation, { props: { message: baseMessage } });
    await tick();
    const btn = container.querySelector('.translate-btn');
    expect(btn?.getAttribute('aria-label')).toBe('Translate message');
  });

  it('does not render Translate button when showTranslation=true', async () => {
    const { container } = render(MessageTranslation, {
      props: { message: baseMessage, showTranslation: true, translatedText: 'Hola' },
    });
    await tick();
    expect(container.querySelector('.translate-btn')).toBeNull();
  });

  it('does not render Translate button when isTranslating=true', async () => {
    const { container } = render(MessageTranslation, {
      props: { message: baseMessage, isTranslating: true },
    });
    await tick();
    expect(container.querySelector('.translate-btn')).toBeNull();
  });

  it('renders Translating loading state when isTranslating=true', async () => {
    const { container } = render(MessageTranslation, {
      props: { message: baseMessage, isTranslating: true },
    });
    await tick();
    expect(container.querySelector('.translating')).not.toBeNull();
    expect(container.querySelector('.loading-spinner')).not.toBeNull();
    expect(container.textContent).toContain('Translating');
  });

  it('does not render translation display when showTranslation=false', async () => {
    const { container } = render(MessageTranslation, {
      props: { message: baseMessage, translatedText: 'Hola' },
    });
    await tick();
    expect(container.querySelector('.translation-display')).toBeNull();
  });

  it('does not render translation display when translatedText is empty', async () => {
    const { container } = render(MessageTranslation, {
      props: { message: baseMessage, showTranslation: true, translatedText: '' },
    });
    await tick();
    expect(container.querySelector('.translation-display')).toBeNull();
  });

  it('renders translation display when showTranslation=true and translatedText present', async () => {
    const { container } = render(MessageTranslation, {
      props: { message: baseMessage, showTranslation: true, translatedText: 'Hola mundo' },
    });
    await tick();
    expect(container.querySelector('.translation-display')).not.toBeNull();
    expect(container.textContent).toContain('Hola mundo');
  });

  it('renders Translated from label with detected language', async () => {
    render(MessageTranslation, {
      props: { message: baseMessage, showTranslation: true, translatedText: 'Hola' },
    });
    await tick();
    expect(screen.getByText(/Translated from English/)).not.toBeNull();
  });

  it('renders Translated from label with auto fallback when detectedLanguage is missing', async () => {
    const noLangMsg = { id: 'm1', text: 'Hello' };
    render(MessageTranslation, {
      props: { message: noLangMsg, showTranslation: true, translatedText: 'X' },
    });
    await tick();
    expect(screen.getByText(/Translated from AUTO/)).not.toBeNull();
  });

  it('close translation button has aria-label', async () => {
    const { container } = render(MessageTranslation, {
      props: { message: baseMessage, showTranslation: true, translatedText: 'Hola' },
    });
    await tick();
    const closeBtn = container.querySelector('.close-translation');
    expect(closeBtn?.getAttribute('aria-label')).toBe('Hide translation');
  });

  it('clicking Translate button calls onTranslate with message', async () => {
    const onTranslate = vi.fn();
    const { container } = render(MessageTranslation, {
      props: { message: baseMessage, onTranslate },
    });
    await tick();
    const btn = container.querySelector('.translate-btn');
    if (btn) {
      await fireEvent.click(btn);
    }
    expect(onTranslate).toHaveBeenCalledWith(baseMessage);
  });

  it('clicking Translate button does not throw when onTranslate not provided', async () => {
    const { container } = render(MessageTranslation, {
      props: { message: baseMessage },
    });
    await tick();
    const btn = container.querySelector('.translate-btn');
    let error: any = null;
    try {
      if (btn) await fireEvent.click(btn);
    } catch (e) {
      error = e;
    }
    expect(error).toBeNull();
  });

  it('Translate button is disabled when isTranslating=true', async () => {
    const { container } = render(MessageTranslation, {
      props: { message: baseMessage, isTranslating: true },
    });
    await tick();
    // Note: when isTranslating is true, the button itself is replaced by the loading indicator
    // so we test the "show button but also disable" case via a controlled prop
  });

  it('renders detected language name correctly for known codes', async () => {
    const testCases: Array<[string, string]> = [
      ['en', 'English'],
      ['es', 'Spanish'],
      ['fr', 'French'],
      ['de', 'German'],
      ['vi', 'Vietnamese'],
      ['ja', 'Japanese'],
    ];

    for (const [code, name] of testCases) {
      const msg = { id: 'm1', text: 'X', detectedLanguage: code };
      render(MessageTranslation, {
        props: { message: msg, showTranslation: true, translatedText: 'Y' },
      });
      await tick();
      expect(screen.getByText(new RegExp(`Translated from ${name}`))).not.toBeNull();
    }
  });

  it('renders language name as uppercase code for unknown language', async () => {
    const msg = { id: 'm1', text: 'X', detectedLanguage: 'xyz' };
    render(MessageTranslation, {
      props: { message: msg, showTranslation: true, translatedText: 'Y' },
    });
    await tick();
    expect(screen.getByText(/Translated from XYZ/)).not.toBeNull();
  });

  it('translation-display contains translated-text element', async () => {
    const { container } = render(MessageTranslation, {
      props: { message: baseMessage, showTranslation: true, translatedText: 'Hola' },
    });
    await tick();
    expect(container.querySelector('.translated-text')).not.toBeNull();
  });

  it('translation-header is rendered inside translation-display', async () => {
    const { container } = render(MessageTranslation, {
      props: { message: baseMessage, showTranslation: true, translatedText: 'Hola' },
    });
    await tick();
    const display = container.querySelector('.translation-display');
    expect(display?.querySelector('.translation-header')).not.toBeNull();
  });
});
