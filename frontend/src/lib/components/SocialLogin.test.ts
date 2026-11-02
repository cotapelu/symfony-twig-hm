import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import SocialLogin from './SocialLogin.svelte';

describe('SocialLogin', () => {
  // Mock window.open
  const originalOpen = window.open;
  beforeEach(() => {
    window.open = vi.fn().mockReturnValue({
      closed: false,
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
    });
    vi.useFakeTimers();
  });

  afterEach(() => {
    window.open = originalOpen;
    vi.useRealTimers();
  });

  it('renders with default props', () => {
    render(SocialLogin);
    expect(screen.getByText('or continue with')).toBeInTheDocument();
  });

  it('renders Google button', () => {
    render(SocialLogin);
    const googleBtn = screen.getByRole('button', { name: /Google/i });
    expect(googleBtn).toBeInTheDocument();
  });

  it('renders Facebook button', () => {
    render(SocialLogin);
    const facebookBtn = screen.getByRole('button', { name: /Facebook/i });
    expect(facebookBtn).toBeInTheDocument();
  });

  it('renders GitHub button', () => {
    render(SocialLogin);
    const githubBtn = screen.getByRole('button', { name: /GitHub/i });
    expect(githubBtn).toBeInTheDocument();
  });

  it('disables buttons when disabled prop is true', () => {
    render(SocialLogin, { props: { disabled: true } });
    expect(screen.getByRole('button', { name: /Google/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /Facebook/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /GitHub/i })).toBeDisabled();
  });

  it('shows loading spinner for Google when loadingProvider is google', () => {
    render(SocialLogin, { props: { loadingProvider: 'google' } });
    expect(document.querySelector('.google .loading-spinner')).toBeInTheDocument();
    expect(screen.getByText('Google')).toBeInTheDocument();
  });

  it('shows loading spinner for Facebook when loadingProvider is facebook', () => {
    render(SocialLogin, { props: { loadingProvider: 'facebook' } });
    expect(document.querySelector('.facebook .loading-spinner')).toBeInTheDocument();
    expect(screen.getByText('Facebook')).toBeInTheDocument();
  });

  it('shows loading spinner for GitHub when loadingProvider is github', () => {
    render(SocialLogin, { props: { loadingProvider: 'github' } });
    expect(document.querySelector('.github .loading-spinner')).toBeInTheDocument();
    expect(screen.getByText('GitHub')).toBeInTheDocument();
  });

  it('disables buttons when loadingProvider is set', () => {
    render(SocialLogin, { props: { loadingProvider: 'google' } });
    expect(screen.getByRole('button', { name: /Google/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /Facebook/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /GitHub/i })).toBeDisabled();
  });

  it('renders social divider', () => {
    render(SocialLogin);
    expect(screen.getByText('or continue with')).toBeInTheDocument();
  });

  it('applies correct classes to Google button', () => {
    render(SocialLogin);
    const googleBtn = screen.getByRole('button', { name: /Google/i });
    expect(googleBtn).toHaveClass('google');
  });

  it('applies correct classes to Facebook button', () => {
    render(SocialLogin);
    const facebookBtn = screen.getByRole('button', { name: /Facebook/i });
    expect(facebookBtn).toHaveClass('facebook');
  });

  it('applies correct classes to GitHub button', () => {
    render(SocialLogin);
    const githubBtn = screen.getByRole('button', { name: /GitHub/i });
    expect(githubBtn).toHaveClass('github');
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(SocialLogin);
    const { container: container2 } = render(SocialLogin, { props: { disabled: true } });
    expect(container1).toBeInTheDocument();
    expect(container2).toBeInTheDocument();
  });

  it('has social-login container', () => {
    render(SocialLogin);
    const container = document.querySelector('.social-login');
    expect(container).toBeInTheDocument();
  });

  it('shows oauth status when loadingProvider is set', () => {
    render(SocialLogin, { props: { loadingProvider: 'google' } });
    expect(screen.getByText('Connecting to google...')).toBeInTheDocument();
  });
});