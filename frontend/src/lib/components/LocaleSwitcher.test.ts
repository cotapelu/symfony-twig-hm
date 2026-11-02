import { render, screen } from '@testing-library/svelte';
import { describe, it, expect, vi } from 'vitest';
import LocaleSwitcher from './LocaleSwitcher.svelte';

describe('LocaleSwitcher Component', () => {
	it('renders with default locales', () => {
		render(LocaleSwitcher);
		expect(screen.getByText('Language:')).toBeDefined();
		expect(screen.getByText('en')).toBeDefined();
		expect(screen.getByText('vi')).toBeDefined();
		expect(screen.getByText('es')).toBeDefined();
	});

	it('marks active locale', () => {
		render(LocaleSwitcher, { props: { currentLocale: 'vi' } });
		const viButton = screen.getByLabelText('Switch to vi');
		expect(viButton.classList.contains('active')).toBe(true);
	});

	it('calls changeCallback on locale click', () => {
		const cb = vi.fn();
		render(LocaleSwitcher, { props: { changeCallback: cb } });
		screen.getByText('es').click();
		expect(cb).toHaveBeenCalledWith('es');
	});
});
