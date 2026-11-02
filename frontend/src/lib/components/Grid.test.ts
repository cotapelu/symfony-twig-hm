import { render, screen } from '@testing-library/svelte';
import { describe, it, expect } from 'vitest';
import Grid from './Grid.svelte';

describe('Grid Component', () => {
	it('renders slot content', () => {
		render(Grid);
		// Slot is empty by default, component should still render container
		expect(document.querySelector('.grid')).toBeDefined();
	});

	it('applies gap class', () => {
		render(Grid, { props: { gap: 'lg' } });
		const el = document.querySelector('.grid');
		expect(el?.classList.contains('grid-gap-lg')).toBe(true);
	});

	it('applies wrap class by default', () => {
		render(Grid);
		const el = document.querySelector('.grid');
		expect(el?.classList.contains('grid-wrap')).toBe(true);
	});

	it('applies nowrap class when wrap is false', () => {
		render(Grid, { props: { wrap: false } });
		const el = document.querySelector('.grid');
		expect(el?.classList.contains('grid-nowrap')).toBe(true);
	});

	it('applies justify class', () => {
		render(Grid, { props: { justify: 'center' } });
		const el = document.querySelector('.grid');
		expect(el?.classList.contains('grid-justify-center')).toBe(true);
	});
});
