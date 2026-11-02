<script lang="ts">
  import { createEventDispatcher, onMount } from 'svelte';

  interface ValidationRule {
    required?: boolean;
    minLength?: number;
    email?: boolean;
    pattern?: RegExp;
    message?: string;
  }

  interface ValidationSchema {
    [key: string]: ValidationRule;
  }

  interface FormData {
    [key: string]: any;
  }

  interface Errors {
    [key: string]: string;
  }

  interface Touched {
    [key: string]: boolean;
  }

  export let initialValues: FormData = {};
  export let validationSchema: ValidationSchema | null = null;
  export let onSubmit: ((data: FormData) => Promise<any> | any) | null = null;
  export let disabled = false;
  export let loading = false;
  export let submitLabel = 'Submit';
  export let resetLabel = 'Reset';
  export let showReset = true;

  const dispatch = createEventDispatcher();

  let formData: FormData = { ...initialValues };
  let errors: Errors = {};
  let touched: Touched = {};
  let isValid = true;
  let isSubmitting = false;

  $: formData, validateForm();

  onMount(() => {
    validateForm();
  });

  function validateForm() {
    if (!validationSchema) {
      isValid = true;
      errors = {};
      return;
    }

    try {
      errors = {};
      isValid = true;

      Object.keys(validationSchema).forEach(field => {
        const rules = validationSchema[field];
        const value = formData[field];

        if (rules.required && (!value || value.toString().trim() === '')) {
          errors[field] = `${field} is required`;
          isValid = false;
        }

        if (rules.minLength && value && value.length < rules.minLength) {
          errors[field] = `${field} must be at least ${rules.minLength} characters`;
          isValid = false;
        }

        if (rules.email && value && !isValidEmail(value)) {
          errors[field] = `${field} must be a valid email`;
          isValid = false;
        }

        if (rules.pattern && value && !rules.pattern.test(value)) {
          errors[field] = rules.message || `${field} format is invalid`;
          isValid = false;
        }
      });

      dispatch('validate', { isValid, errors });
    } catch (error) {
      console.error('Validation error:', error);
      isValid = false;
    }
  }

  function isValidEmail(email: string) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  function updateField(field: string, value: any) {
    formData[field] = value;
    formData = { ...formData };

    if (!touched[field]) {
      touched[field] = true;
      touched = { ...touched };
    }
  }

  function handleFieldChange(event: Event) {
    const target = event.target as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
    const { name, value, type } = target;
    const checked = target instanceof HTMLInputElement ? target.checked : undefined;
    const fieldValue = type === 'checkbox' ? checked : value;
    updateField(name, fieldValue);
  }

  async function handleSubmit(event: Event) {
    event.preventDefault();

    if (!isValid || disabled || isSubmitting) return;

    isSubmitting = true;

    try {
      const result = onSubmit ? await onSubmit(formData) : formData;
      dispatch('submit', { data: formData, result });
    } catch (error) {
      dispatch('error', { error, data: formData });
    } finally {
      isSubmitting = false;
    }
  }

  function handleReset() {
    formData = { ...initialValues };
    errors = {};
    touched = {};
    isValid = true;
    dispatch('reset');
  }

  function setFieldValue(field: string, value: any) {
    updateField(field, value);
  }

  function setFieldError(field: string, error: string) {
    errors[field] = error;
    errors = { ...errors };
    isValid = false;
  }

  function clearFieldError(field: string) {
    if (errors[field]) {
      delete errors[field];
      errors = { ...errors };
      validateForm();
    }
  }

  function getFieldProps(fieldName: string) {
    return {
      name: fieldName,
      value: formData[fieldName] || '',
      error: errors[fieldName],
      touched: touched[fieldName],
      onChange: handleFieldChange
    };
  }

  export {
    setFieldValue,
    setFieldError,
    clearFieldError,
    getFieldProps,
    validateForm
  };
</script>

<form onsubmit={handleSubmit} class="form" class:disabled class:loading>
  <div class="form-content">
    <slot {formData} {errors} {touched} {isValid} {updateField} {getFieldProps} />
  </div>

  <div class="form-actions">
    {#if showReset}
      <button type="button" onclick={handleReset} disabled={disabled || isSubmitting} class="reset-button">
        {resetLabel}
      </button>
    {/if}
    <button type="submit" disabled={!isValid || disabled || isSubmitting} class="submit-button">
      {#if isSubmitting}
        <span class="spinner">⟳</span>
        Submitting...
      {:else}
        {submitLabel}
      {/if}
    </button>
  </div>
</form>

<style>
  .form {
    width: 100%;
  }

  .form.disabled {
    opacity: 0.6;
    pointer-events: none;
  }

  .form.loading {
    position: relative;
  }

  .form.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
  }

  .form-content {
    margin-bottom: 2rem;
  }

  .form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color, #e5e7eb);
  }

  .submit-button,
  .reset-button {
    padding: 0.5rem 1.5rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
  }

  .submit-button {
    background: var(--primary-color, #3b82f6);
    color: var(--primary-text, #ffffff);
    border-color: var(--primary-color, #3b82f6);
  }

  .submit-button:hover:not(:disabled) {
    background: var(--primary-hover, #2563eb);
    border-color: var(--primary-hover, #2563eb);
  }

  .submit-button:disabled {
    background: var(--disabled-bg, #9ca3af);
    border-color: var(--disabled-bg, #9ca3af);
    cursor: not-allowed;
    opacity: 0.6;
  }

  .reset-button {
    background: transparent;
    color: var(--text-secondary, #6b7280);
    border-color: var(--border-color, #d1d5db);
  }

  .reset-button:hover:not(:disabled) {
    background: var(--bg-hover, #f3f4f6);
    color: var(--text-primary, #111827);
  }

  .reset-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .spinner {
    display: inline-block;
    animation: spin 1s linear infinite;
    margin-right: 0.5rem;
  }

  @keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }

  /* svelte-ignore css_unused_selector */
  /* Field styles (for common form fields) */
  .form-field {
    margin-bottom: 1rem;
  }

  /* svelte-ignore css_unused_selector */
  .form-field label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary, #111827);
  }

  .form-field input,
  .form-field select,
  .form-field textarea {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 0.375rem;
    background: var(--bg-input, #ffffff);
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }

  .form-field input:focus,
  .form-field select:focus,
  .form-field textarea:focus {
    outline: none;
    border-color: var(--focus-color, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .form-field textarea {
    resize: vertical;
    min-height: 80px;
  }

  .form-field.error input,
  .form-field.error select,
  .form-field.error textarea {
    border-color: var(--error-color, #ef4444);
  }

  .form-field .error-message {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--error-color, #ef4444);
  }

  /* Dark mode */
  @media (prefers-color-scheme: dark) {
    .form-actions {
      border-top-color: var(--border-color-dark, #374151);
    }

    .reset-button {
      color: var(--text-secondary-dark, #9ca3af);
      border-color: var(--border-color-dark, #374151);
    }

    .reset-button:hover:not(:disabled) {
      background: var(--bg-hover-dark, #374151);
      color: var(--text-primary-dark, #f9fafb);
    }

    .form-field input,
    .form-field select,
    .form-field textarea {
      background: var(--bg-input-dark, #1f2937);
      border-color: var(--border-color-dark, #374151);
      color: var(--text-primary-dark, #f9fafb);
    }

    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
      border-color: var(--focus-color-dark, #60a5fa);
      box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
    }

    .form-field.error input,
    .form-field.error select,
    .form-field.error textarea {
      border-color: var(--error-color-dark, #f87171);
    }
  }

  /* High contrast */
  @media (prefers-contrast: high) {
    .submit-button,
    .reset-button {
      border: 2px solid;
    }

    .form-field input,
    .form-field select,
    .form-field textarea {
      border: 2px solid;
    }
  }

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce) {
    .submit-button,
    .reset-button {
      transition: none;
    }

    .form-field input,
    .form-field select,
    .form-field textarea {
      transition: none;
    }

    .spinner {
      animation: none;
    }
  }
</style>