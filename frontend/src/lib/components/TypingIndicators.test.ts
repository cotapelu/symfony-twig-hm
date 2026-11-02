import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import TypingIndicators from './TypingIndicators.svelte';

describe('TypingIndicators Component', () => {
  it('renders nothing when typingUsers is empty', () => {
    const { container } = render(TypingIndicators, { props: { typingUsers: [] } });
    expect(container.querySelector('.typing-indicators')).toBeNull();
  });

  it('displays single user typing', () => {
    render(TypingIndicators, { props: { typingUsers: [{ userId: 'u1', name: 'Alice', timestamp: Date.now() }] } });
    expect(screen.getByText(/Alice is typing/)).toBeInTheDocument();
  });

  it('displays two users typing with "and"', () => {
    render(TypingIndicators, { props: { typingUsers: [
      { userId: 'u1', name: 'Alice', timestamp: Date.now() },
      { userId: 'u2', name: 'Bob', timestamp: Date.now() }
    ] } });
    expect(screen.getByText(/Alice and Bob are typing/)).toBeInTheDocument();
  });

  it('displays three users typing with commas and "and"', () => {
    render(TypingIndicators, { props: { typingUsers: [
      { userId: 'u1', name: 'Alice', timestamp: Date.now() },
      { userId: 'u2', name: 'Bob', timestamp: Date.now() },
      { userId: 'u3', name: 'Carol', timestamp: Date.now() }
    ] } });
    expect(screen.getByText(/Alice, Bob and Carol are typing/)).toBeInTheDocument();
  });

  it('displays up to maxDisplay names', () => {
    render(TypingIndicators, { props: { typingUsers: [
      { userId: 'u1', name: 'Alice', timestamp: Date.now() },
      { userId: 'u2', name: 'Bob', timestamp: Date.now() },
      { userId: 'u3', name: 'Carol', timestamp: Date.now() },
      { userId: 'u4', name: 'Dave', timestamp: Date.now() }
    ], maxDisplay: 3 } });
    // Shows first three names only
    expect(screen.getByText(/Alice, Bob and Carol are typing/)).toBeInTheDocument();
  });

  it('respects maxDisplay default (3)', () => {
    render(TypingIndicators, { props: { typingUsers: [
      { userId: 'u1', name: 'Alice', timestamp: Date.now() },
      { userId: 'u2', name: 'Bob', timestamp: Date.now() },
      { userId: 'u3', name: 'Carol', timestamp: Date.now() },
      { userId: 'u4', name: 'Dave', timestamp: Date.now() }
    ] } });
    // Shows first three names only
    expect(screen.getByText(/Alice, Bob and Carol are typing/)).toBeInTheDocument();
  });

 // Rerender update test omitted for simplicity.
});
