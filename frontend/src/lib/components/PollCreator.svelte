<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let onPollCreate = null;

  const dispatch = createEventDispatcher();

  let question = '';
  let options = ['', ''];
  let allowMultiple = false;
  let expiresIn = 24; // hours
  let isAnonymous = false;
  let showAdvanced = false;

  function addOption() {
    if (options.length < 10) {
      options = [...options, ''];
    }
  }

  function removeOption(index) {
    if (options.length > 2) {
      options = options.filter((_, i) => i !== index);
    }
  }

  function updateOption(index, value) {
    options[index] = value;
  }

  function createPoll() {
    if (!question.trim()) {
      alert('Please enter a question');
      return;
    }

    const validOptions = options.filter(opt => opt.trim());
    if (validOptions.length < 2) {
      alert('Please add at least 2 options');
      return;
    }

    const pollData = {
      question: question.trim(),
      options: validOptions,
      allowMultiple,
      expiresAt: expiresIn > 0 ? new Date(Date.now() + expiresIn * 60 * 60 * 1000) : null,
      isAnonymous,
      createdAt: new Date()
    };

    dispatch('create', pollData);

    if (onPollCreate) {
      onPollCreate(pollData);
    }

    // Reset form
    question = '';
    options = ['', ''];
    allowMultiple = false;
    expiresIn = 24;
    isAnonymous = false;
    showAdvanced = false;
  }

  function cancel() {
    dispatch('cancel');
  }
</script>

<div class="poll-creator">
  <div class="creator-header">
    <h3>Create Poll</h3>
    <button class="close-btn" onclick={cancel} aria-label="Close poll creator">✕</button>
  </div>

  <div class="creator-content">
    <!-- Question -->
    <div class="form-group">
      <label for="poll-question" class="required">Question</label>
      <input
        id="poll-question"
        type="text"
        bind:value={question}
        placeholder="What do you want to ask?"
        maxlength="200"
        required
        class="question-input"
      />
      <div class="char-count">{question.length}/200</div>
    </div>

    <!-- Options -->
    <div class="form-group">
      <label>Options</label>
      <div class="options-list">
        {#each options as option, index}
          <div class="option-item">
            <input
              type="text"
              bind:value={option}
              placeholder="Option {index + 1}"
              maxlength="100"
              class="option-input"
            />
            {#if options.length > 2}
              <button
                class="remove-option-btn"
                onclick={() => removeOption(index)}
                aria-label="Remove option"
                type="button"
              >
                🗑️
              </button>
            {/if}
          </div>
        {/each}
      </div>

      {#if options.length < 10}
        <button
          class="add-option-btn"
          onclick={addOption}
          type="button"
        >
          + Add Option
        </button>
      {/if}
    </div>

    <!-- Advanced Options -->
    <div class="advanced-toggle">
      <button
        class="toggle-btn"
        onclick={() => showAdvanced = !showAdvanced}
        aria-expanded={showAdvanced}
        type="button"
      >
        {showAdvanced ? '▼' : '▶'} Advanced Options
      </button>
    </div>

    {#if showAdvanced}
      <div class="advanced-options">
        <!-- Allow multiple votes -->
        <div class="option-row">
          <label class="checkbox-label">
            <input
              type="checkbox"
              bind:checked={allowMultiple}
            />
            Allow multiple selections
          </label>
        </div>

        <!-- Anonymous voting -->
        <div class="option-row">
          <label class="checkbox-label">
            <input
              type="checkbox"
              bind:checked={isAnonymous}
            />
            Anonymous voting
          </label>
        </div>

        <!-- Expiration -->
        <div class="option-row">
          <label for="expires-in">Expires in</label>
          <select id="expires-in" bind:value={expiresIn} class="expiry-select">
            <option value={1}>1 hour</option>
            <option value={6}>6 hours</option>
            <option value={24}>1 day</option>
            <option value={168}>1 week</option>
            <option value={0}>Never</option>
          </select>
        </div>
      </div>
    {/if}
  </div>

  <div class="creator-actions">
    <button class="cancel-btn" onclick={cancel}>Cancel</button>
    <button
      class="create-btn"
      onclick={createPoll}
      disabled={!question.trim() || options.filter(o => o.trim()).length < 2}
    >
      Create Poll
    </button>
  </div>
</div>

<style>
  .poll-creator {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    width: 100%;
  }

  .creator-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
  }

  .creator-header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
  }

  .close-btn {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    font-size: 1.2rem;
    transition: all 0.15s ease;
  }

  .close-btn:hover {
    background: #f3f4f6;
    color: #374151;
  }

  .creator-content {
    padding: 1.5rem;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
  }

  .required::after {
    content: ' *';
    color: #ef4444;
  }

  .question-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: border-color 0.15s ease;
  }

  .question-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .char-count {
    text-align: right;
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
  }

  .options-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .option-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .option-input {
    flex: 1;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
  }

  .option-input:focus {
    outline: none;
    border-color: #3b82f6;
  }

  .remove-option-btn {
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.15s ease;
  }

  .remove-option-btn:hover {
    background: #fef2f2;
  }

  .add-option-btn {
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    padding: 0.5rem 1rem;
    color: #374151;
    cursor: pointer;
    font-size: 0.875rem;
    margin-top: 0.5rem;
    transition: all 0.15s ease;
  }

  .add-option-btn:hover {
    background: #e5e7eb;
  }

  .advanced-toggle {
    margin: 1rem 0;
  }

  .toggle-btn {
    background: none;
    border: none;
    color: #3b82f6;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .advanced-options {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
  }

  .option-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
  }

  .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .checkbox-label input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    accent-color: #3b82f6;
  }

  .expiry-select {
    padding: 0.375rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: white;
    font-size: 0.875rem;
  }

  .creator-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #f3f4f6;
  }

  .cancel-btn {
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    padding: 0.5rem 1rem;
    color: #374151;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.15s ease;
  }

  .cancel-btn:hover {
    background: #e5e7eb;
  }

  .create-btn {
    background: #3b82f6;
    border: 1px solid #3b82f6;
    border-radius: 0.375rem;
    padding: 0.5rem 1rem;
    color: white;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.15s ease;
  }

  .create-btn:hover:not(:disabled) {
    background: #2563eb;
    border-color: #2563eb;
  }

  .create-btn:disabled {
    background: #9ca3af;
    border-color: #9ca3af;
    cursor: not-allowed;
  }

  /* Dark theme support */
  @media (prefers-color-scheme: dark) {
    .poll-creator {
      background: #1f2937;
      border-color: #374151;
    }

    .creator-header {
      border-color: #374151;
    }

    .creator-header h3 {
      color: #f9fafb;
    }

    .question-input,
    .option-input,
    .expiry-select {
      background: #1f2937;
      border-color: #374151;
      color: #f9fafb;
    }

    .question-input:focus,
    .option-input:focus {
      border-color: #3b82f6;
    }

    .add-option-btn {
      background: #374151;
      border-color: #4b5563;
      color: #f9fafb;
    }

    .add-option-btn:hover {
      background: #4b5563;
    }

    .advanced-options {
      background: #374151;
    }

    .cancel-btn {
      background: #374151;
      border-color: #4b5563;
      color: #f9fafb;
    }

    .cancel-btn:hover {
      background: #4b5563;
    }
  }

  /* Mobile responsive */
  @media (max-width: 640px) {
    .poll-creator {
      margin: 1rem;
    }

    .creator-content {
      padding: 1rem;
    }

    .option-item {
      flex-direction: column;
      align-items: stretch;
      gap: 0.25rem;
    }

    .creator-actions {
      flex-direction: column;
    }

    .cancel-btn,
    .create-btn {
      width: 100%;
    }
  }
</style>
