import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import SearchBar from './SearchBar.svelte';

describe('SearchBar Component', () => {
  it('renders search bar with default placeholder', () => {
    const { container } = render(SearchBar);
    const input = container.querySelector('input');
    expect(input).toBeTruthy();
    expect(input?.getAttribute('placeholder')).toBe('Search...');
  });

  it('renders with custom placeholder', () => {
    const { container } = render(SearchBar, { props: { placeholder: 'Find...' } });
    const input = container.querySelector('input');
    expect(input?.getAttribute('placeholder')).toBe('Find...');
  });

  it('applies size classes correctly', () => {
    const { container: sm } = render(SearchBar, { props: { size: 'sm' } });
    expect(sm.querySelector('.search-bar')).toHaveClass('search-bar-sm');

    const { container: lg } = render(SearchBar, { props: { size: 'lg' } });
    expect(lg.querySelector('.search-bar')).toHaveClass('search-bar-lg');
  });

  it('applies variant classes correctly', () => {
    const { container: filled } = render(SearchBar, { props: { variant: 'filled' } });
    expect(filled.querySelector('.search-bar')).toHaveClass('search-bar-filled');

    const { container: outlined } = render(SearchBar, { props: { variant: 'outlined' } });
    expect(outlined.querySelector('.search-bar')).toHaveClass('search-bar-outlined');
  });

  it('applies disabled state', () => {
    const { container } = render(SearchBar, { props: { disabled: true } });
    const wrapper = container.querySelector('.search-bar');
    expect(wrapper).toHaveClass('search-bar-disabled');
    const input = container.querySelector('input');
    expect(input?.disabled).toBe(true);
  });

  it('applies loading state', () => {
    const { container } = render(SearchBar, { props: { loading: true } });
    const wrapper = container.querySelector('.search-bar');
    expect(wrapper).toHaveClass('search-bar-loading');
  });

  it('applies custom className', () => {
    const { container } = render(SearchBar, { props: { className: 'my-search' } });
    const wrapper = container.querySelector('.search-bar');
    expect(wrapper).toHaveClass('my-search');
  });

});
