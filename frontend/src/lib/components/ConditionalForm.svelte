<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import DOMPurify from 'dompurify';

  interface FieldCondition {
    dependsOn: string;
    value: any;
    operator?: 'equals' | 'notEquals' | 'contains' | 'notContains' | 'exists' | 'notExists';
  }

  interface FormField {
    name: string;
    label: string;
    type: string;
    placeholder?: string;
    required?: boolean;
    condition?: FieldCondition;
    options?: Array<{ value: string; label: string }>;
  }

  interface FormSchema {
    fields?: FormField[];
    submitLabel?: string;
  }

  interface FormData {
    [key: string]: any;
  }

  interface FormErrors {
    [key: string]: string;
  }

  export let schema: FormSchema = {};
  export let data: FormData = {};
  export let errors: FormErrors = {};
  export let onSubmit: ((values: FormData) => void) | null = null;

  const dispatch = createEventDispatcher();

  let visibleFields: FormField[] = [];

  $: visibleFields = getVisibleFields(schema.fields || [], data);

  function getVisibleFields(fields: FormField[], currentData: FormData): FormField[] {
    return fields.filter(field => {
      if (!field.condition) return true;

      const { dependsOn, value, operator = 'equals' } = field.condition;
      const dependentValue = currentData[dependsOn];

      switch (operator) {
        case 'equals':
          return dependentValue === value;
        case 'notEquals':
          return dependentValue !== value;
        case 'contains':
          return Array.isArray(dependentValue) && dependentValue.includes(value);
        case 'notContains':
          return !Array.isArray(dependentValue) || !dependentValue.includes(value);
        case 'exists':
          return dependentValue != null && dependentValue !== '';
        case 'notExists':
          return dependentValue == null || dependentValue === '';
        default:
          return true;
      }
    });
  }

  function handleInput(event: Event) {
    const target = event.target as HTMLInputElement | HTMLSelectElement;
    const { name, value } = target;
    data = { ...data, [name]: value };

    visibleFields = getVisibleFields(schema.fields || [], data);

    dispatch('input', { name, value, data });
  }

  function handleSubmit(event: Event) {
    event.preventDefault();
    const formData = new FormData(event.target as HTMLFormElement);
    const values: FormData = {};

    for (const [key, value] of formData.entries()) {
      values[key] = value;
    }

    const validationErrors = validateForm(values, visibleFields);
    if (Object.keys(validationErrors).length === 0) {
      dispatch('submit', values);
      if (onSubmit) onSubmit(values);
    } else {
      dispatch('error', validationErrors);
    }
  }

  function validateForm(values: FormData, fields: FormField[]): FormErrors {
    const errors: FormErrors = {};

    fields.forEach(field => {
      const value = values[field.name];
      const error = validateField(value, field);
      if (error) errors[field.name] = error;
    });

    return errors;
  }

  function validateField(value: any, field: FormField): string | null {
    if (field.required && (!value || value === '')) {
      return `${field.label} is required`;
    }

    if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      return 'Invalid email format';
    }

    return null;
  }

  function renderField(field: FormField): string {
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
              oninput={handleInput}
            />
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
              onchange={handleInput}
            >
              <option value="">Select ${field.label.toLowerCase()}</option>
              ${options}
            </select>
            ${fieldErrors ? `<span class="error-message">${fieldErrors}</span>` : ''}
          </div>
        `;

      case 'radio':
        const radioOptions = field.options?.map(option => `
          <label class="radio-option">
            <input
              type="radio"
              name="${field.name}"
              value="${option.value}"
              checked="${data[field.name] === option.value}"
              onchange={handleInput}
            />
            ${option.label}
          </label>
        `).join('') || '';
        return `
          <div class="form-field">
            <label>${field.label}</label>
            <div class="radio-group">
              ${radioOptions}
            </div>
            ${fieldErrors ? `<span class="error-message">${fieldErrors}</span>` : ''}
          </div>
        `;

      default:
        return '';
    }
  }
</script>

<form onsubmit={handleSubmit} class="conditional-form">
  {#each visibleFields as field}
    {@html DOMPurify.sanitize(renderField(field))}
  {/each}

  <div class="form-actions">
    <button type="submit" class="submit-btn">
      {schema.submitLabel || 'Submit'}
    </button>
  </div>
</form>

<style>
  .conditional-form {
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
  .form-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 1rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }

  .form-input:focus,
  .form-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .form-input.error,
  .form-select.error {
    border-color: #ef4444;
  }

  .radio-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .radio-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.375rem;
    transition: background-color 0.15s ease-in-out;
  }

  .radio-option:hover {
    background: #f3f4f6;
  }

  .radio-option input[type="radio"] {
    accent-color: #3b82f6;
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
