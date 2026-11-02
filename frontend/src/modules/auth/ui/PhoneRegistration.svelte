<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { authService } from '../services/auth.service';

  export let showPhoneRegistration = false;

  let phoneNumber = '';
  let verificationCode = '';
  let step: 'phone' | 'verify' = 'phone';
  let loading = false;
  let error = '';

  const dispatch = createEventDispatcher();

  async function handleSendCode() {
    if (!phoneNumber.trim()) {
      error = 'Phone number is required';
      return;
    }

    loading = true;
    error = '';

    try {
      const response = await fetch('/api/auth/send-sms-code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phoneNumber })
      });

      if (response.ok) {
        step = 'verify';
      } else {
        error = 'Failed to send verification code';
      }
    } catch (err) {
      error = (err as Error).message;
    } finally {
      loading = false;
    }
  }

  async function handleVerifyCode() {
    if (!verificationCode.trim()) {
      error = 'Verification code is required';
      return;
    }

    loading = true;
    error = '';

    try {
      const response = await fetch('/api/auth/verify-sms-code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phoneNumber, code: verificationCode })
      });

      if (response.ok) {
        const { user, token } = await response.json();
        authService.setUser(user, token);
        dispatch('login', { user });
        showPhoneRegistration = false;
      } else {
        error = 'Invalid verification code';
      }
    } catch (err) {
      error = (err as Error).message;
    } finally {
      loading = false;
    }
  }

  function handleSwitchToEmail() {
    dispatch('switchToEmail');
  }

  function handleClose() {
    showPhoneRegistration = false;
  }

  function resetForm() {
    step = 'phone';
    phoneNumber = '';
    verificationCode = '';
    error = '';
  }
</script>

{#if showPhoneRegistration}
  <div class="phone-registration-overlay" on:click={handleClose}>
    <div class="phone-registration-modal" on:click|stopPropagation>
      <div class="modal-header">
        <h3>Register with Phone</h3>
        <button class="close-button" on:click={handleClose}>×</button>
      </div>

      {#if error}
        <div class="error-message">
          <span class="error-icon">⚠️</span>
          {error}
        </div>
      {/if}

      {#if step === 'phone'}
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input
            id="phone"
            type="tel"
            bind:value={phoneNumber}
            placeholder="+1 (555) 123-4567"
            disabled={loading}
          />
        </div>

        <button 
          class="submit-button" 
          on:click={handleSendCode}
          disabled={loading}
        >
          {loading ? 'Sending...' : 'Send Code'}
        </button>
      {:else}
        <div class="form-group">
          <label for="code">Verification Code</label>
          <input
            id="code"
            type="text"
            bind:value={verificationCode}
            placeholder="Enter 6-digit code"
            maxlength="6"
            disabled={loading}
          />
        </div>

        <button 
          class="submit-button" 
          on:click={handleVerifyCode}
          disabled={loading}
        >
          {loading ? 'Verifying...' : 'Verify & Register'}
        </button>
      {/if}

      <div class="modal-footer">
        <button 
          type="button" 
          class="link-button"
          on:click={handleSwitchToEmail}
        >
          Use email instead
        </button>
        
        {#if step === 'verify'}
          <button 
            type="button" 
            class="link-button"
            on:click={resetForm}
          >
            Change phone number
          </button>
        {/if}
      </div>
    </div>
  </div>
{/if}

<style>
  .phone-registration-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }

  .phone-registration-modal {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .modal-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 1.25rem;
  }

  .close-button {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
  }

  .close-button:hover {
    background: #f3f4f6;
  }

  .error-message {
    background: #fee2e2;
    border: 1px solid #f87171;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    color: #991b1b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
  }

  .error-icon {
    font-size: 1rem;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-group label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
  }

  .form-group input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
  }

  .form-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .form-group input:disabled {
    background: #f3f4f6;
    cursor: not-allowed;
  }

  .submit-button {
    width: 100%;
    padding: 0.875rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .submit-button:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }

  .submit-button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  .modal-footer {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
  }

  .link-button {
    background: none;
    border: none;
    color: #667eea;
    cursor: pointer;
    font-weight: 500;
    padding: 0;
    text-align: center;
    transition: text-decoration 0.2s;
  }

  .link-button:hover {
    text-decoration: underline;
  }
</style>