import { render, screen } from '@testing-library/svelte';
import { describe, it, expect } from 'vitest';
import StatCard from './StatCard.svelte';

describe('StatCard Component', () => {
	it('renders title and value', () => {
		render(StatCard, { props: { title: 'Users', value: 1234 } });
		expect(screen.getByText('Users')).toBeDefined();
		expect(screen.getByText('1234')).toBeDefined();
	});

	it('renders icon', () => {
		render(StatCard, { props: { title: 'Test', value: 1, icon: 'user' } });
		expect(document.querySelector('.stat-icon')).toBeDefined();
	});

	it('applies custom color', () => {
		render(StatCard, { props: { title: 'Test', value: 1, color: '#ff0000' } });
		const wrapper = document.querySelector('.stat-card-wrapper');
		expect(wrapper?.getAttribute('style')).toContain('--card-color: #ff0000');
	});
});
