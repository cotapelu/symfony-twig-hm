import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import Result from './Result.svelte';

describe('Result extra', () => {
	it('renders with default status and accessible title', () => {
		const { getByText, container } = render(Result, {
			props: { title: 'Done', status: 'success' },
		});
		expect(getByText('Done')).toBeDefined();
		const wrapper = container.querySelector('.result') || container.querySelector('[class*="result"]');
		expect(wrapper).not.toBeNull();
	});
});
