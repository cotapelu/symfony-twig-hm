import { render, screen } from '@testing-library/svelte';
import { describe, it, expect, vi } from 'vitest';
import Anchor from './Anchor.svelte';

describe('Anchor Component', () => {
	it('renders with default props', () => {
		render(Anchor, { props: { href: '/test' } });
		const el = screen.getByRole('link');
		expect(el).toBeDefined();
		expect(el.getAttribute('href')).toBe('/test');
		expect(el.getAttribute('target')).toBe('_blank');
		expect(el.getAttribute('rel')).toBe('noopener noreferrer');
	});

	it('renders with custom target and className', () => {
		render(Anchor, { props: { href: '/test', target: '_self', className: 'my-anchor' } });
		const el = screen.getByRole('link');
		expect(el.getAttribute('target')).toBe('_self');
		expect(el.classList.contains('my-anchor')).toBe(true);
	});

	it('dispatches click event with href', () => {
		const on_click = vi.fn();
		render(Anchor, { props: { href: '/test' }, events: { click: on_click } });
		const el = screen.getByRole('link');
		el.click();
		expect(on_click).toHaveBeenCalled();
	});

	it('renders children text', () => {
		render(Anchor, { props: { href: '/test', children: 'Click me' } });
		expect(screen.getByText('Click me')).toBeDefined();
	});
});
