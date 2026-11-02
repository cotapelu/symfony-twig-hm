import { render, screen } from '@testing-library/svelte';
import { describe, it, expect } from 'vitest';
import UnreadBadge from './UnreadBadge.svelte';

describe('UnreadBadge Component', () => {
	it('does not render when show is false', () => {
		render(UnreadBadge, { props: { show: false, count: 5 } });
		expect(screen.queryByRole('status')).toBeNull();
	});

	it('does not render when count is 0', () => {
		render(UnreadBadge, { props: { show: true, count: 0 } });
		expect(screen.queryByRole('status')).toBeNull();
	});

	it('renders count text', () => {
		render(UnreadBadge, { props: { show: true, count: 5 } });
		expect(screen.getByText('5')).toBeDefined();
	});

	it('renders 99+ when count exceeds 99', () => {
		render(UnreadBadge, { props: { show: true, count: 150 } });
		expect(screen.getByText('99+')).toBeDefined();
	});

	it('applies size class', () => {
		render(UnreadBadge, { props: { show: true, count: 1, size: 'large' } });
		const el = screen.getByText('1');
		expect(el.parentElement?.classList.contains('large')).toBe(true);
	});
});
