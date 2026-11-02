import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import Message from './Message.svelte';

describe('Message Component', () => {
  const defaultProps = {
    id: 'm1',
    content: 'Hello world',
    sender: 'Alice',
    senderAvatar: '/alice.png',
    timestamp: new Date('2024-01-01T10:00:00Z'),
    read: true,
    sent: true,
    type: 'text' as const,
    isOwn: false,
    reactions: [],
  };

  it('renders message content and sender name', () => {
    render(Message, { props: defaultProps });
    expect(screen.getByText('Hello world')).toBeInTheDocument();
    expect(screen.getByText('Alice')).toBeInTheDocument();
  });

  it('renders without error for basic props', () => {
    const { container } = render(Message, { props: defaultProps });
    expect(container.querySelector('.message')).toBeInTheDocument();
  });

  it('applies own class when isOwn=true', () => {
    const { container } = render(Message, { props: { ...defaultProps, isOwn: true } });
    expect(container.querySelector('.message')).toHaveClass('own');
  });

  it('applies other class when isOwn=false', () => {
    const { container } = render(Message, { props: { ...defaultProps, isOwn: false } });
    expect(container.querySelector('.message')).toHaveClass('other');
  });

  // Rendering specific message types (image/file/etc) covered by component integration; skipped for now.
});
