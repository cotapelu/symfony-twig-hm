<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import DOMPurify from 'dompurify';

  interface StepField {
    name: string;
    label: string;
    type: string;
    placeholder?: string;
    required?: boolean;
    options?: Array<{ value: string; label: string }>;
  }

  interface FormStep {
    title: string;
    description?: string;
    fields?: StepField[];
  }

  interface StepData {
    [key: string]: string;
  }

  interface StepErrors {
    [key: string]: string;
  }

  interface AllErrors {
    [stepIndex: number]: StepErrors;
  }

  export let steps: FormStep[] = [];
  export let currentStep = 0;
  export let data: StepData = {};
  export let errors: AllErrors = {};
  export let onComplete: ((allData: StepData) => void) | null = null;

  const dispatch = createEventDispatcher();
  let stepData: StepData[] = [...(Object.keys(data).length > 0 ? [data] : [])];

  $: totalSteps = steps.length;
  $: isFirstStep = currentStep === 0;
  $: isLastStep = currentStep === totalSteps - 1;

  function nextStep() {
    if (currentStep < totalSteps - 1) {
      currentStep++;
      dispatch('stepChange', { step: currentStep, data: stepData });
    }
  }

  function prevStep() {
    if (currentStep > 0) {
      currentStep--;
      dispatch('stepChange', { step: currentStep, data: stepData });
    }
  }

  function handleStepSubmit(event: Event) {
    event.preventDefault();
    const target = event.target as HTMLFormElement;
    const formData = new FormData(target);
    const values: StepData = {};

    for (const [key, value] of formData.entries()) {
      values[key] = value as string;
    }

    stepData[currentStep] = values;

    const stepErrors = validateStep(values, steps[currentStep]);
    if (Object.keys(stepErrors).length === 0) {
      if (isLastStep) {
        const allData = Object.assign({}, ...stepData);
        dispatch('complete', allData);
        if (onComplete) onComplete(allData);
      } else {
        nextStep();
      }
    } else {
      errors = { ...errors, [currentStep]: stepErrors };
      dispatch('error', { step: currentStep, errors: stepErrors });
    }
  }

  function validateStep(values: StepData, step: FormStep): StepErrors {
    const errors: StepErrors = {};

    if (step.fields) {
      step.fields.forEach(field => {
        const value = values[field.name];
        const error = validateField(value, field);
        if (error) errors[field.name] = error;
      });
    }

    return errors;
  }

  function validateField(value: any, field: StepField): string | null {
    if (field.required && (!value || value === '')) {
      return `${field.label} is required`;
    }

    if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      return 'Invalid email format';
    }

    return null;
  }

  function renderField(field: StepField): string {
    const stepErrors = errors[currentStep] || {};
    const fieldErrors = stepErrors[field.name] || '';

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
              value="${stepData[currentStep]?.[field.name] || ''}"
              placeholder="${field.placeholder || ''}"
              required="${field.required || false}"
              class="form-input ${fieldErrors ? 'error' : ''}"
            />
            ${fieldErrors ? `<span class="error-message">${fieldErrors}</span>` : ''}
          </div>
        `;

      case 'select': {
        const options = field.options?.map(option =>
          `<option value="${option.value}" ${stepData[currentStep]?.[field.name] === option.value ? 'selected' : ''}>${option.label}</option>`
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
      }

      default:
        return '';
    }
  }
</script>

<div class="multi-step-form">
  <!-- Progress indicator -->
  <div class="progress-bar">
    <div class="progress-steps">
      {#each steps as _, index}
        <div class="step {index <= currentStep ? 'completed' : ''} {index === currentStep ? 'active' : ''}">
          <span class="step-number">{index + 1}</span>
          <span class="step-title">{steps[index].title}</span>
        </div>
        {#if index < steps.length - 1}
          <div class="step-connector {index < currentStep ? 'completed' : ''}"></div>
        {/if}
      {/each}
    </div>
  </div>

  <!-- Current step content -->
  <div class="step-content">
    <h2 class="step-title">{steps[currentStep]?.title}</h2>
    <p class="step-description">{steps[currentStep]?.description}</p>

    <form onsubmit={handleStepSubmit} class="step-form">
      {#each steps[currentStep]?.fields || [] as field}
        {@html DOMPurify.sanitize(renderField(field))}
      {/each}

      <div class="form-actions">
        {#if !isFirstStep}
          <button type="button" onclick={prevStep} class="prev-btn">
            Previous
          </button>
        {/if}

        <button type="submit" class="next-btn">
          {isLastStep ? 'Complete' : 'Next'}
        </button>
      </div>
    </form>
  </div>
</div>

<style>
  .multi-step-form {
    max-width: 600px;
    margin: 0 auto;
  }

  .progress-bar {
    margin-bottom: 2rem;
  }

  .progress-steps {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
  }

  .step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 0.5rem;
    transition: all 0.2s ease;
  }

  .step.active .step-number {
    background: #3b82f6;
    color: white;
  }

  .step.completed .step-number {
    background: #10b981;
    color: white;
  }

  .step-title {
    font-size: 0.875rem;
    color: #6b7280;
    text-align: center;
  }

  .step.active .step-title {
    color: #3b82f6;
    font-weight: 500;
  }

  .step.completed .step-title {
    color: #10b981;
  }

  .step-connector {
    flex: 1;
    height: 2px;
    background: #e5e7eb;
    margin: 0 1rem;
  }

  .step-connector.completed {
    background: #10b981;
  }

  .step-content {
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .step-content .step-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.5rem;
  }

  .step-description {
    color: #6b7280;
    margin-bottom: 2rem;
  }

  .form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
  }

  .prev-btn,
  .next-btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 0.375rem;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
  }

  .prev-btn {
    background: #f3f4f6;
    color: #374151;
  }

  .prev-btn:hover {
    background: #e5e7eb;
  }

  .next-btn {
    background: #3b82f6;
    color: white;
  }

  .next-btn:hover {
    background: #2563eb;
  }
</style>
