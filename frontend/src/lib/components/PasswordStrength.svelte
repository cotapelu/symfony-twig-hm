<script lang="ts">
	import { createEventDispatcher } from 'svelte';
	import Icon from './atoms/Icon.svelte';
	import Progress from './atoms/Progress.svelte';
	import Typography from './atoms/Typography.svelte';

	const dispatch = createEventDispatcher();

	export let password = '';
	export let minLength = 8;
	export let requireUppercase = true;
	export let requireLowercase = true;
	export let requireNumber = true;
	export let requireSpecial = true;
	export let showPassword = false;
	export let className = '';
	export let onChange: ((strength: number, feedback: string) => void) | undefined = undefined;

	let strength = 0;
	let feedback = '';

	$: calculateStrength(password);

	function calculateStrength(pwd: string) {
		let score = 0;
		const checks = [];

		if (pwd.length >= minLength) score += 10;
		else if (pwd.length > 0) score += 5;

		if (requireLowercase && /[a-z]/.test(pwd)) {
			score += 15;
			checks.push('Lowercase letter');
		}

		if (requireUppercase && /[A-Z]/.test(pwd)) {
			score += 15;
			checks.push('Uppercase letter');
		}

		if (requireNumber && /\d/.test(pwd)) {
			score += 15;
			checks.push('Number');
		}

		if (requireSpecial && /[^a-zA-Z0-9]/.test(pwd)) {
			score += 15;
			checks.push('Special character');
		}

		if (pwd.length >= 12) score += 10;
		else if (pwd.length >= 8) score += 5;

		strength = Math.min(100, score);

		if (strength < 30) feedback = 'Weak password';
		else if (strength < 50) feedback = 'Fair password';
		else if (strength < 70) feedback = 'Good password';
		else feedback = 'Strong password';

		if (onChange) onChange(strength, feedback);
		dispatch('change', { strength, feedback });
	}

	function getStrengthColor(): string {
		if (strength < 30) return 'var(--color-danger, #ef4444)';
		if (strength < 50) return 'var(--color-warning, #f59e0b)';
		if (strength < 70) return 'var(--color-info, #3b82f6)';
		return 'var(--color-success, #10b981)';
	}

	function toggleShowPassword() {
		showPassword = !showPassword;
	}
</script>

<div class="password-strength {className}">
	<div class="password-input-wrapper">
		<slot>
			<input
				type={showPassword ? 'text' : 'password'}
				value={password}
				class="password-input"
				aria-label="Password input"
				oninput={(e: Event) => {
					const target = e.target as HTMLInputElement;
					password = target.value;
				}}
			/>
		</slot>
		<button
			class="password-toggle"
			onclick={toggleShowPassword}
			aria-label={showPassword ? 'Hide password' : 'Show password'}
		>
			<Icon name={showPassword ? 'eye-off' : 'eye'} size="sm" />
		</button>
	</div>

	<Progress percent={strength} status="active" showInfo={false} size="sm" strokeWidth={8} />

	<div class="password-feedback">
		<Typography variant="caption" className="password-feedback-text">
			{feedback}
		</Typography>
	</div>

	<div class="password-requirements">
		<Typography variant="caption" className="requirements-title">
			Password requirements:
		</Typography>
		<ul class="requirements-list">
			<li class={password.length >= minLength ? 'met' : 'unmet'}>
					<Icon name="check" size="sm" />
					At least {minLength} characters
			</li>
			{#if requireLowercase}
			<li class={/[a-z]/.test(password) ? 'met' : 'unmet'}>
					<Icon name="check" size="sm" />
					Lowercase letter
			</li>
			{/if}
			{#if requireUppercase}
			<li class={/[A-Z]/.test(password) ? 'met' : 'unmet'}>
					<Icon name="check" size="sm" />
					Uppercase letter
			</li>
			{/if}
			{#if requireNumber}
			<li class={/\d/.test(password) ? 'met' : 'unmet'}>
					<Icon name="check" size="sm" />
					Number
			</li>
			{/if}
			{#if requireSpecial}
			<li class={/[^a-zA-Z0-9]/.test(password) ? 'met' : 'unmet'}>
					<Icon name="check" size="sm" />
					Special character
			</li>
			{/if}
		</ul>
	</div>
</div>

<style>
	.password-strength {
		width: 100%;
	}

	.password-input-wrapper {
		display: flex;
		gap: 0.5rem;
		align-items: center;
	}

	.password-input {
		flex: 1;
		padding: 0.625rem 0.875rem;
		border: 1px solid var(--color-border, #d1d5db);
		border-radius: var(--radius-md, 0.375rem);
		font-size: 0.875rem;
		transition: all 0.15s;
	}

	.password-input:focus {
		outline: none;
		border-color: var(--color-primary, #3b82f6);
		box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
	}

	.password-toggle {
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 0.5rem;
		background: var(--color-gray-100, #f3f4f6);
		border: 1px solid var(--color-border, #d1d5db);
		border-radius: var(--radius-md, 0.375rem);
		cursor: pointer;
		transition: all 0.15s;
	}

	.password-toggle:hover {
		background: var(--color-gray-200, #e5e7eb);
	}

	.password-feedback {
		margin-top: 0.5rem;
	}

	.password-feedback-text {
		font-weight: 500;
	}

	.password-requirements {
		margin-top: 0.75rem;
	}

	.requirements-title {
		font-weight: 600;
		margin-bottom: 0.25rem;
		color: var(--color-text-secondary, #6b7280);
	}

	.requirements-list {
		list-style: none;
		padding: 0;
		display: flex;
		flex-direction: column;
		gap: 0.25rem;
	}

	.requirements-list li {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		color: var(--color-text-secondary, #6b7280);
		font-size: 0.8125rem;
	}

	.requirements-list li.met {
		color: var(--color-success, #10b981);
	}

	.requirements-list li.unmet {
		color: var(--color-text-secondary, #6b7280);
		opacity: 0.7;
	}

	@media (prefers-color-scheme: dark) {
		.password-input {
			background: var(--color-gray-800, #1f2937);
			border-color: var(--color-gray-700, #374151);
		}

		.password-input:focus {
			border-color: var(--color-primary, #3b82f6);
			background: var(--color-gray-800, #1f2937);
		}

		.password-toggle {
			background: var(--color-gray-800, #1f2937);
			border-color: var(--color-gray-700, #374151);
		}

		.password-toggle:hover {
			background: var(--color-gray-700, #374151);
		}

		.requirements-title,
		.requirements-list li.unmet {
			color: var(--color-text-secondary-dark, #9ca3af);
		}
	}
</style>
