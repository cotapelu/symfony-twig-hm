import { render, screen } from '@testing-library/svelte';
import { describe, it, expect } from 'vitest';
import Segmented from './Segmented.svelte';

describe('Segmented Component', () => {
	it('renders items', () => {
		render(Segmented, {
			props: {
				items: [
					{ label: 'Name', value: 'John' },
					{ label: 'Age', value: '30' }
				]
			}
		});
		expect(screen.getByText('Name')).toBeDefined();
		expect(screen.getByText('John')).toBeDefined();
		expect(screen.getByText('Age')).toBeDefined();
		expect(screen.getByText('30')).toBeDefined();
	});

	it('applies bordered class', () => {
		render(Segmented, { props: { bordered: true, items: [] } });
		const el = document.querySelector('.descriptions');
		expect(el?.classList.contains('bordered')).toBe(true);
	});

	it('renders nothing when items is empty', () => {
		render(Segmented, { props: { items: [] } });
		expect(document.querySelector('.descriptions-item')).toBeNull();
	});
});
