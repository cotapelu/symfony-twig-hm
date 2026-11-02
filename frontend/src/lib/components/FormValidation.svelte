<script lang="ts">
  import { createEventDispatcher, setContext, getContext } from 'svelte';
  import { writable } from 'svelte/store';

  interface ValidationRule {
    type: 'required' | 'email' | 'minLength' | 'maxLength' | 'pattern' | 'custom';
    value?: any;
    message: string;
    async?: boolean;
    validator?: (value: any) => boolean | Promise<boolean>;
  }

  interface FieldValidation {
    field: string;
    rules: ValidationRule[];
  }

  interface Props {
    validations?: FieldValidation[];
    data?: Record<string, any>;
  }

  let {
    validations = [],
    data = {}
  }: Props = $props();

  const dispatch = createEventDispatcher<{ valid: boolean; errors: Record<string, string[]> }>();

  // Validation context - must be at top level
  const validationStore = writable<Record<string, string[]>>({});

  setContext('form-validation', {
    validateField: (field: string, value: any) => validateField(field, value),
    errors: validationStore
  });

  // Make errors a reactive state that can be read by tests
  let errors = $state({} as Record<string, string[]>);

  // Export getter for errors state
  export function getErrors() {
    return errors;
  }

  async function validateField(fieldName: string, value: any): Promise<string[]> {
    const fieldValidation = validations.find(v => v.field === fieldName);
    if (!fieldValidation) return [];

    const fieldErrors: string[] = [];

    for (const rule of fieldValidation.rules) {
      let isValid = false;

      switch (rule.type) {
        case 'required':
          isValid = value !== undefined && value !== null && value !== '';
          break;
        case 'email':
          isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
          break;
        case 'minLength':
          isValid = value && value.length >= rule.value;
          break;
        case 'maxLength':
          isValid = value && value.length <= rule.value;
          break;
        case 'pattern':
          isValid = rule.value.test(value);
          break;
        case 'custom':
          if (rule.validator) {
            if (rule.async) {
              isValid = await rule.validator(value);
            } else {
              isValid = !!rule.validator(value);
            }
          }
          break;
        default:
          isValid = true;
      }

      if (!isValid) {
        fieldErrors.push(rule.message);
      }
    }

    errors = { ...errors, [fieldName]: fieldErrors };
    validationStore.set(errors);

    return fieldErrors;
  }

  async function validateAll(): Promise<boolean> {
    const allErrors: Record<string, string[]> = {};

    // Ensure data is an object
    const validationData = data || {};

    for (const validation of validations) {
      const fieldErrors = await validateField(validation.field, validationData[validation.field]);
      if (fieldErrors.length > 0) {
        allErrors[validation.field] = fieldErrors;
      }
    }

    errors = allErrors;
    validationStore.set(errors);

    const isValid = Object.keys(allErrors).length === 0;
    dispatch('valid', isValid);
    dispatch('errors', allErrors);

    return isValid;
  }

  // Expose validateAll function - synchronous for tests, async for async validators
  export async function validate() {
    return validateAll();
  }
</script>

<slot {errors} {validateAll} />