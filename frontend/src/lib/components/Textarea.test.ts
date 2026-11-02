import { render, screen } from '@testing-library/svelte';
import { describe, it, expect } from 'vitest';
import Textarea from './Textarea.svelte';

describe('Textarea Component', () => {
	it('renders textarea element', () => {
		render(Textarea);
		expect(document.querySelector('textarea')).toBeDefined();
	});

	it('applies className', () => {
		render(Textarea, { props: { className: 'custom-class' } });
		const el = document.querySelector('textarea');
		expect(el?.classList.contains('custom-class')).toBe(true);
	});

	it('renders label when provided', () => {
		render(Textarea, { props: { label: 'Test Label' } });
		expect(screen.getByText('Test Label')).toBeDefined();
	});

	it('shows required indicator', () => {
		render(Textarea, { props: { label: 'Required Field', required: true } });
		expect(screen.getByLabelText('required')).toBeDefined();
	});

	it('applies disabled attribute', () => {
		render(Textarea, { props: { disabled: true } });
		const el = document.querySelector('textarea');
		expect(el?.hasAttribute('disabled')).toBe(true);
	});
});
