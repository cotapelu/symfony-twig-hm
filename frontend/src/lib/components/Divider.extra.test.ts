import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import Divider from './Divider.svelte';

describe('Divider extra', () => {
	it('renders a horizontal divider by default', () => {
		const { container, unmount } = render(Divider);
		const hr = container.querySelector('hr');
		expect(hr).not.toBeNull();
		expect(hr?.className).toContain('divider');
		expect(hr?.className).toContain('divider-horizontal');
		unmount();
	});

	it('applies vertical orientation and aria attribute', () => {
		const { container, unmount } = render(Divider, {
			props: { orientation: 'vertical' },
		});
		const div = container.querySelector('div[aria-orientation="vertical"]');
		expect(div).not.toBeNull();
		expect(div?.className).toContain('divider-vertical');
		unmount();
	});
});
