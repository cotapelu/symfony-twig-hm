<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import Input from './atoms/Input.svelte';
  import Button from './atoms/Button.svelte';
  import Icon from './atoms/Icon.svelte';
  import Select from './atoms/Select.svelte';
  import Typography from './atoms/Typography.svelte';

  export let value: string = '';
  export let maskType: 'email' | 'phone' | 'creditCard' | 'ssn' | 'custom' = 'email';
  export let customMaskPattern: string = '';
  export let showToggle: boolean = true;
  export let showByDefault: boolean = false;
  export let copyable: boolean = true;

  const dispatch = createEventDispatcher();

  let visible: boolean = showByDefault;
  let maskedValue: string = '';
  let unmaskedValue: string = '';
  let showTooltip: boolean = false;

  const maskPatterns = {
    email: (email: string) => {
      const [username, domain] = email.split('@');
      if (!domain) return email;
      const maskedUsername = username.slice(0, 2) + '*'.repeat(username.length - 2);
      return `${maskedUsername}@${domain}`;
    },
    phone: (phone: string) => {
      const cleaned = phone.replace(/\D/g, '');
      if (cleaned.length < 7) return phone;
      const last4 = cleaned.slice(-4);
      return `***-***-${last4}`;
    },
    creditCard: (card: string) => {
      const cleaned = card.replace(/\D/g, '');
      if (cleaned.length < 13) return card;
      const last4 = cleaned.slice(-4);
      return `****-****-****-${last4}`;
    },
    ssn: (ssn: string) => {
      const cleaned = ssn.replace(/\D/g, '');
      if (cleaned.length < 9) return ssn;
      const last4 = cleaned.slice(-4);
      return `***-**-${last4}`;
    },
    custom: (value: string, pattern: string) => {
      if (!pattern) return value;
      try {
        const regex = new RegExp(pattern);
        return value.replace(regex, (match) => '*'.repeat(match.length));
      } catch {
        return value;
      }
    }
  };

  $: {
    unmaskedValue = value;
    if (maskType === 'custom' && customMaskPattern) {
      maskedValue = maskPatterns.custom(value, customMaskPattern);
    } else {
      const maskFunc = maskPatterns[maskType as keyof typeof maskPatterns];
      if (maskFunc === maskPatterns.custom) {
        maskedValue = maskPatterns.custom(value, customMaskPattern);
      } else {
        maskedValue = maskPatterns[maskType as 'email' | 'phone' | 'creditCard' | 'ssn'](value);
      }
    }
  }

  function toggleVisibility() {
    visible = !visible;
    dispatch('toggle', { visible });
  }

  function handleCopy() {
    if (!copyable) return;

    navigator.clipboard.writeText(unmaskedValue);
    showTooltip = true;
    setTimeout(() => {
      showTooltip = false;
    }, 2000);
    dispatch('copy', { value: unmaskedValue });
  }

  function handleInput(e: Event) {
    const target = e.target as HTMLInputElement;
    dispatch('input', { value: target.value });
  }

  function getPlaceholder(): string {
    switch (maskType) {
      case 'email':
        return 'user@example.com';
      case 'phone':
        return '123-456-7890';
      case 'creditCard':
        return '1234-5678-1234-5678';
      case 'ssn':
        return '123-45-6789';
      default:
        return 'Enter value';
    }
  }

  function getIcon(): string {
    return visible ? 'eye' : 'eye-off';
  }

  function getTooltipText(): string {
    return showTooltip ? 'Copied!' : 'Copy';
  }
</script>

<div class="data-masking">
  <div class="data-masking-input">
    <Input
      type={visible ? 'text' : 'password'}
      value={visible ? unmaskedValue : maskedValue}
      placeholder={getPlaceholder()}
      readonly={!visible}
    />

    <div class="data-masking-actions">
      {#if showToggle}
        <Button
          variant="ghost"
          size="sm"
          onclick={toggleVisibility}
          aria-label={visible ? 'Hide value' : 'Show value'}
        >
          <Icon name={getIcon()} size="sm" />
        </Button>
      {/if}

      {#if copyable}
        <Button
          variant="ghost"
          size="sm"
          onclick={handleCopy}
          aria-label={getTooltipText()}
        >
          <Icon name="copy" size="sm" />
        </Button>
      {/if}
    </div>
  </div>

  {#if maskType === 'custom'}
    <div class="data-masking-pattern">
      <label for="customMask">Custom Mask Pattern (regex)</label>
      <Input
        bind:value={customMaskPattern}
        placeholder="e.g., \\d{4}-\\d{4}"
      />
      <Typography variant="caption">
        Use regex pattern to specify what to mask
      </Typography>
    </div>
  {/if}

  {#if showTooltip}
    <div class="data-masking-tooltip">
      Copied!
    </div>
  {/if}
</div>

<style>
  :global(:root) {
    --data-masking-bg: #ffffff;
    --data-masking-border: #e5e7eb;
    --data-masking-hover: #f3f4f6;
    --data-masking-text: #1f2937;
    --data-masking-muted: #6b7280;
    --data-masking-tooltip-bg: #1f2937;
    --data-masking-tooltip-text: #ffffff;
  }

  .data-masking {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .data-masking-input {
    display: flex;
    gap: 0.5rem;
    align-items: center;
  }

  .data-masking-input :global(input) {
    flex: 1;
  }

  .data-masking-actions {
    display: flex;
    gap: 0.25rem;
  }

  .data-masking-actions button {
    color: var(--data-masking-muted);
    transition: color 0.15s ease;
  }

  .data-masking-actions button:hover {
    color: var(--data-masking-text);
  }

  .data-masking-pattern {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .data-masking-pattern label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--data-masking-text);
  }

  .data-masking-tooltip {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--data-masking-tooltip-bg);
    color: var(--data-masking-tooltip-text);
    border-radius: 6px;
    font-size: 0.875rem;
    animation: fadeIn 0.2s ease;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(-4px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
</style>
