import { render, screen } from '@testing-library/svelte';
import { describe, it, expect } from 'vitest';
import MockButton from './MockButton.svelte';

describe('MockButton Component', () => {
	it('renders with default props', () => {
		render(MockButton, { props: { children: () => 'Click me' } });
		const btn = screen.getByRole('button');
		expect(btn).toBeDefined();
		expect(btn.classList.contains('btn-primary')).toBe(true);
		expect(btn.classList.contains('btn-md')).toBe(true);
	});

	it('applies variant class', () => {
		render(MockButton, { props: { variant: 'secondary', children: () => 'Test' } });
		const btn = screen.getByRole('button');
		expect(btn.classList.contains('btn-secondary')).toBe(true);
	});

	it('applies size class', () => {
		render(MockButton, { props: { size: 'sm', children: () => 'Test' } });
		const btn = screen.getByRole('button');
		expect(btn.classList.contains('btn-sm')).toBe(true);
	});

	it('disables button when disabled is true', () => {
		render(MockButton, { props: { disabled: true, children: () => 'Test' } });
		const btn = screen.getByRole('button');
		expect(btn.hasAttribute('disabled')).toBe(true);
	});

	it('shows loading state', () => {
		render(MockButton, { props: { loading: true, children: () => 'Test' } });
		expect(screen.getByText('Loading...')).toBeDefined();
	});
});
