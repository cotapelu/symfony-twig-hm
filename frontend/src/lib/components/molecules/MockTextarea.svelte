<script lang="ts">
  export let label = '';
  export let required = false;
  export let placeholder = '';
  export let value = '';
  export let rows = 3;
  export let disabled = false;
  export let readonly = false;
  export let name = '';
  export let id = `textarea-${Math.random().toString(36).slice(2)}`;
  export let className = '';
  export let style = '';
  export let oninput;
  export let onchange;
  export let onblur;
  export let onfocus;
</script>

<div class="textarea-wrapper">
  {#if label}
    <label for={id} class="textarea-label">
      {label}
      {#if required}<span class="required" aria-label="required">*</span>{/if}
    </label>
  {/if}
  <textarea
    id={id}
    {value}
    {placeholder}
    {disabled}
    {readonly}
    {required}
    {rows}
    {name}
    class="textarea {className}"
    {style}
    on:input={oninput}
    on:change={onchange}
    on:blur={onblur}
    on:focus={onfocus}
    data-testid="mock-textarea"
  ></textarea>
</div>

<style>
  .textarea-wrapper {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .textarea-label {
    font-weight: 500;
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
  }

  .required {
    color: var(--error-500, #ef4444);
    margin-left: 0.25rem;
  }

  .textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 6px;
    font-size: 1rem;
    line-height: 1.5;
    color: var(--text-primary, #374151);
    background-color: var(--bg-primary, #ffffff);
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    resize: vertical;
    min-height: 100px;
  }

  .textarea:focus {
    outline: none;
    border-color: var(--primary-500, #3b82f6);
    box-shadow: 0 0 0 3px var(--primary-100, #dbeafe);
  }

  .textarea:disabled {
    background-color: var(--gray-100, #f3f4f6);
    cursor: not-allowed;
  }
</style>