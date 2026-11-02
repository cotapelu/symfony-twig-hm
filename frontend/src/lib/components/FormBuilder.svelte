<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import DOMPurify from 'dompurify';

  interface FieldOption {
    value: string;
    label: string;
  }

  interface Field {
    name: string;
    label: string;
    type: string;
    placeholder?: string;
    required?: boolean;
    options?: FieldOption[];
  }

  interface Schema {
    fields?: Field[];
    submitLabel?: string;
  }

  interface FormData {
    [key: string]: string;
  }

  interface ValidationErrors {
    [key: string]: string;
  }

  export let schema: Schema = {};
  export let data: FormData = {};
  export let errors: ValidationErrors = {};
  export let onSubmit: ((values: FormData) => void) | null = null;

  const dispatch = createEventDispatcher();

  function handleSubmit(event: Event) {
    event.preventDefault();
    const formData = new FormData(event.target as HTMLFormElement);
    const values: FormData = {};

    for (const [key, value] of formData.entries()) {
      values[key] = value as string;
    }

    const validationErrors = validateForm(values, schema);
    if (Object.keys(validationErrors).length === 0) {
      dispatch('submit', values);
      if (onSubmit) onSubmit(values);
    } else {
      dispatch('error', validationErrors);
    }
  }

  function validateForm(values: FormData, schema: Schema): ValidationErrors {
    const errors: ValidationErrors = {};

    if (schema.fields) {
      schema.fields.forEach(field => {
        const value = values[field.name];
        const error = validateField(value, field);
        if (error) errors[field.name] = error;
      });
    }

    return errors;
  }

  function validateField(value: string, field: Field): string | null {
    if (field.required && (!value || value === '')) {
      return `${field.label} is required`;
    }

    if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      return 'Invalid email format';
    }

    return null;
  }

  function renderField(field: Field): string {
    const fieldErrors = errors[field.name] || '';

    switch (field.type) {
      case 'text':
      case 'email':
      case 'password':
        return `
          <div class="form-field">
            <label for="${field.name}">${field.label}</label>
            <input
              type="${field.type}"
              id="${field.name}"
              name="${field.name}"
              value="${data[field.name] || ''}"
              placeholder="${field.placeholder || ''}"
              required="${field.required || false}"
              class="form-input ${fieldErrors ? 'error' : ''}"
            />
            ${fieldErrors ? `<span class="error-message">${fieldErrors}</span>` : ''}
          </div>
        `;

      case 'textarea':
        return `
          <div class="form-field">
            <label for="${field.name}">${field.label}</label>
            <textarea
              id="${field.name}"
              name="${field.name}"
              placeholder="${field.placeholder || ''}"
              required="${field.required || false}"
              class="form-textarea ${fieldErrors ? 'error' : ''}"
            >${data[field.name] || ''}</textarea>
            ${fieldErrors ? `<span class="error-message">${fieldErrors}</span>` : ''}
          </div>
        `;

      case 'select':
        const options = field.options?.map(option =>
          `<option value="${option.value}" ${data[field.name] === option.value ? 'selected' : ''}>${option.label}</option>`
        ).join('') || '';
        return `
          <div class="form-field">
            <label for="${field.name}">${field.label}</label>
            <select
              id="${field.name}"
              name="${field.name}"
              required="${field.required || false}"
              class="form-select ${fieldErrors ? 'error' : ''}"
            >
              <option value="">Select ${field.label.toLowerCase()}</option>
              ${options}
            </select>
            ${fieldErrors ? `<span class="error-message">${fieldErrors}</span>` : ''}
          </div>
        `;

      default:
        return '';
    }
  }
</script>

<form onsubmit={handleSubmit} class="dynamic-form">
  {#each schema.fields || [] as field}
    {@html DOMPurify.sanitize(renderField(field))}
  {/each}

  <div class="form-actions">
    <button type="submit" class="submit-btn">
      {schema.submitLabel || 'Submit'}
    </button>
  </div>
</form>

<style>
  .dynamic-form {
    max-width: 600px;
    margin: 0 auto;
  }

  .form-field {
    margin-bottom: 1.5rem;
  }

  .form-field label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
  }

  .form-input,
  .form-textarea,
  .form-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 1rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }

  .form-input:focus,
  .form-textarea:focus,
  .form-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .form-textarea {
    min-height: 100px;
    resize: vertical;
  }

  .form-input.error,
  .form-textarea.error,
  .form-select.error {
    border-color: #ef4444;
  }

  .error-message {
    display: block;
    margin-top: 0.25rem;
    color: #ef4444;
    font-size: 0.875rem;
  }

  .form-actions {
    margin-top: 2rem;
    text-align: center;
  }

  .submit-btn {
    background-color: #3b82f6;
    color: white;
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 0.375rem;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
  }

  .submit-btn:hover {
    background-color: #2563eb;
  }
</style>
