import { render, screen } from '@testing-library/svelte';
import { describe, it, expect } from 'vitest';
import Spin from './Spin.svelte';

describe('Spin Component', () => {
	it('renders with default props', () => {
		render(Spin);
		const el = screen.getByRole('status');
		expect(el).toBeDefined();
		expect(el.getAttribute('aria-label')).toBe('Loading');
		expect(el.getAttribute('aria-busy')).toBe('true');
	});

	it('applies size class', () => {
		render(Spin, { props: { size: 'lg' } });
		const el = screen.getByRole('status');
		expect(el.classList.contains('lg')).toBe(true);
	});

	it('applies custom className', () => {
		render(Spin, { props: { className: 'my-spin' } });
		const el = screen.getByRole('status');
		expect(el.classList.contains('my-spin')).toBe(true);
	});

	it('applies color to icon', () => {
		render(Spin, { props: { color: 'red' } });
		const el = screen.getByRole('status');
		expect(el.innerHTML).toContain('red');
	});
});
