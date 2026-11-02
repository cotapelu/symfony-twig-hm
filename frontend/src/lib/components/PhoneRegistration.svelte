<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { fade, slide } from 'svelte/transition';
  import { setUser } from '$lib/stores/global';
  import { sendSMSVerification, verifyPhoneCode } from '$lib/api';
  import Button from './atoms/Button.svelte';
  import Input from './atoms/Input.svelte';
  import Typography from './atoms/Typography.svelte';

  let { showPhoneRegistration = $bindable(false), onSwitchToEmail = null, onlogin = undefined } = $props();

  let verificationCode = $state('');
  let displayName = $state('');
  let step = $state('phone'); // 'phone', 'verify', 'complete'
  let loading = $state(false);
  let error = $state('');
  let resendTimer = $state(0);
  let resendAttempts = $state(0);
  let timerId: ReturnType<typeof setInterval> | null = null;

  const dispatch = createEventDispatcher();

   // Phone number formatting
   function formatPhoneNumber(value: string) {
    // Remove all non-digit characters
    let digits = value.replace(/\D/g, '');

    // Format based on length
    if (digits.length <= 3) {
      return digits;
    } else if (digits.length <= 6) {
      return `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
    } else {
      return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6, 10)}`;
    }
  }

  // Store raw digits only for API calls
  let rawPhoneNumber = $state('');

  // Formatted version for display in input
  let formattedPhoneNumber = $derived.by(() => {
    if (!rawPhoneNumber) return '';
    return formatPhoneNumber(rawPhoneNumber);
  });

  // Handle phone input - user types formatted, we store raw
  function handlePhoneInput(value: string) {
    const digits = value.replace(/\D/g, '');
    rawPhoneNumber = digits;
  }

  function validatePhoneNumber() {
    return rawPhoneNumber.length >= 10 && rawPhoneNumber.length <= 15;
  }

  async function sendVerificationCode() {
    if (!validatePhoneNumber()) {
      error = 'Please enter a valid phone number';
      return;
    }

    if (!displayName.trim()) {
      error = 'Display name is required';
      return;
    }

    loading = true;
    error = '';

    try {
      await sendSMSVerification(rawPhoneNumber || rawPhoneNumber, displayName);
      step = 'verify';
      startResendTimer();
     } catch (err) {
       error = (err as Error).message || 'Failed to send verification code';
    } finally {
      loading = false;
    }
  }

  async function verifyCode() {
    if (verificationCode.length !== 6) {
      error = 'Please enter a 6-digit verification code';
      return;
    }

    loading = true;
    error = '';

    try {
      const data = await verifyPhoneCode(rawPhoneNumber || rawPhoneNumber, verificationCode, displayName);
      setUser(data.user);

      if (data.token) {
        localStorage.setItem('auth_token', data.token);
      }

      step = 'complete';

      // Auto redirect after success
      setTimeout(() => {
        onlogin?.({
          detail: { user: data.user, method: 'phone' }
        } as CustomEvent<{ user: { id: string; name: string }; method: string }>);
      }, 2000);
     } catch (err) {
       error = (err as Error).message || 'Invalid verification code';
    } finally {
      loading = false;
    }
  }

  function startResendTimer() {
    resendTimer = 60; // 60 seconds
    if (timerId) clearInterval(timerId);
    timerId = setInterval(() => {
      resendTimer--;
      if (resendTimer <= 0) {
        clearInterval(timerId!);
        timerId = null;
      }
    }, 1000);
  }

  async function resendCode() {
    if (resendAttempts >= 3) {
      error = 'Too many resend attempts. Please try again later.';
      return;
    }

    resendAttempts++;
    await sendVerificationCode();
  }

  function resetForm() {
    rawPhoneNumber = '';
    verificationCode = '';
    displayName = '';
    step = 'phone';
    error = '';
    resendTimer = 0;
    resendAttempts = 0;
    if (timerId) {
      clearInterval(timerId);
      timerId = null;
    }
  }

  function switchToEmail() {
    resetForm();
    onSwitchToEmail?.();
  }

  // Cleanup on destroy
  $effect(() => {
    return () => {
      if (timerId) clearInterval(timerId);
    };
  });

  // Handle escape key
  $effect(() => {
    if (!showPhoneRegistration) return;
    
    function handleKeydown(e: KeyboardEvent) {
      if (e.key === 'Escape') {
        showPhoneRegistration = false;
      }
    }
    
    document.addEventListener('keydown', handleKeydown);
    return () => document.removeEventListener('keydown', handleKeydown);
  });
</script>

{#if showPhoneRegistration}
  <div
    class="phone-registration-overlay"
    role="presentation"
    data-testid="phone-registration-overlay"
    onmousedown={(e) => { if (e.target === e.currentTarget) showPhoneRegistration = false; }}
  >
    <div
      class="phone-registration-modal"
      role="dialog"
      aria-modal="true"
      onmousedown={(e) => e.stopPropagation()}
      transition:slide={{ duration: 300 }}
    >
       <!-- Close button -->
       <Button
         variant="ghost"
         className="close-btn"
         onclick={() => showPhoneRegistration = false}
       >
         ×
       </Button>

       <div class="modal-header">
         <Typography variant="h2">📱 Phone Registration</Typography>
         <Typography variant="body">Verify your phone number to create an account</Typography>
       </div>

      <div class="modal-body">
         {#if error}
           <div class="error-message" in:slide={{ duration: 300 }}>
             <Typography variant="body" className="error-icon">⚠️</Typography>
             <Typography variant="body">{error}</Typography>
           </div>
         {/if}

        {#if step === 'phone'}
          <!-- Phone Number Input -->
          <div class="form-step" in:fade>
             <div class="step-indicator">
               <Typography variant="body" className="step-number active">1</Typography>
               <Typography variant="body">Enter Details</Typography>
             </div>

             <form onsubmit={(e) => { e.preventDefault(); sendVerificationCode(); }}>
               <div class="form-group">
                 <Input
                   label="Display Name"
                   type="text"
                   bind:value={displayName}
                   placeholder="Your name"
                   required
                   name="display-name"
                 />
               </div>

               <div class="form-group">
                 <Typography variant="body">Phone Number</Typography>
                 <div class="phone-input-container">
                   <Typography variant="body" className="country-code">+1</Typography>
                   <Input
                     type="tel"
                     value={formattedPhoneNumber}
                     oninput={(e) => handlePhoneInput((e.target as HTMLInputElement).value)}
                     placeholder="(555) 123-4567"
                     required
                     name="phone-number"
                     data-testid="phone-input"
                   />
                 </div>
                 <Typography variant="caption" className="input-hint">Enter your mobile number for SMS verification</Typography>
               </div>

               <Button
                 variant="primary"
                 type="submit"
                 disabled={loading || !validatePhoneNumber() || !displayName.trim()}
               >
                 {#if loading}
                   Sending Code...
                 {:else}
                   Send Verification Code
                 {/if}
               </Button>
             </form>
          </div>

        {:else if step === 'verify'}
          <!-- Verification Code Input -->
          <div class="form-step" in:fade>
             <div class="step-indicator">
               <Typography variant="body" className="step-number active">2</Typography>
               <Typography variant="body">Verify Code</Typography>
             </div>

             <div class="verification-info">
               <Typography variant="body">We've sent a 6-digit code to</Typography>
                <Typography variant="body" weight={600}>{formattedPhoneNumber || rawPhoneNumber}</Typography>
             </div>

             <form onsubmit={(e) => { e.preventDefault(); verifyCode(); }}>
               <div class="form-group">
                 <Input
                   label="Verification Code"
                   type="text"
                   bind:value={verificationCode}
                   placeholder="000000"
                   required
                   name="verification-code"
                 />
                 <Typography variant="caption" className="input-hint">Enter the 6-digit code sent to your phone</Typography>
               </div>

               <Button
                 variant="primary"
                 type="submit"
                 disabled={loading || verificationCode.length !== 6}
               >
                 {#if loading}
                   Verifying...
                 {:else}
                   Verify Code
                 {/if}
               </Button>
             </form>

             <div class="resend-section">
               {#if resendTimer > 0}
                 <Typography variant="body" className="resend-timer">
                   Resend code in {resendTimer}s
                 </Typography>
               {:else}
                 <Button
                   variant="ghost"
                   className="link-btn"
                   onclick={resendCode}
                   disabled={resendAttempts >= 3}
                 >
                   Resend Code
                 </Button>
                 {#if resendAttempts >= 3}
                   <Typography variant="body" className="resend-limit">Too many attempts. Try again later.</Typography>
                 {/if}
               {/if}
             </div>
          </div>

         {:else if step === 'complete'}
           <!-- Success -->
           <div class="form-step success-step" in:fade={{ duration: 400 }}>
             <Typography variant="body" className="success-icon">✅</Typography>
             <Typography variant="h3">Phone Verified!</Typography>
             <Typography variant="body">Your account has been created successfully.</Typography>
             <Typography variant="body" className="redirect-notice">Redirecting you to the dashboard...</Typography>
           </div>
        {/if}

         <!-- Back to Email Link -->
         {#if step === 'phone'}
           <div class="auth-switch">
             <Button variant="ghost" className="link-btn" onclick={switchToEmail}>
               Use email instead
             </Button>
           </div>
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
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
  }

  .phone-registration-modal {
    background: var(--bg-card, #1e293b);
    border-radius: 16px;
    border: 2px solid var(--border-accent, rgba(0, 255, 255, 0.3));
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    width: 100%;
    max-width: 400px;
    position: relative;
    overflow: hidden;
  }

  .close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 1.5rem;
    cursor: pointer;
    z-index: 10;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
  }

  .close-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
  }

  .modal-header {
    padding: 2rem 1.5rem 1rem 1.5rem;
    text-align: center;
    border-bottom: 1px solid var(--border-primary);
  }

  .modal-header h2 {
    /* svelte-ignore css_unused_selector */
    color: var(--text-primary);
    /* svelte-ignore css_unused_selector */
    margin: 0 0 0.5rem 0;
    /* svelte-ignore css_unused_selector */
    font-size: 1.5rem;
  }

  .modal-header p {
    /* svelte-ignore css_unused_selector */
    color: var(--text-secondary);
    /* svelte-ignore css_unused_selector */
    margin: 0;
    /* svelte-ignore css_unused_selector */
    font-size: 0.9rem;
  }

  .modal-body {
    padding: 1.5rem;
  }

  .error-message {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid rgba(239, 68, 68, 0.2);
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .error-icon {
    font-size: 1.1rem;
    flex-shrink: 0;
  }

  .step-indicator {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    padding: 0.75rem;
    background: rgba(0, 255, 255, 0.1);
    border-radius: 8px;
  }

  .step-number {
    width: 2rem;
    height: 2rem;
    background: var(--text-muted);
    color: var(--bg-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
  }

  .step-number.active {
    background: var(--accent-primary);
    color: white;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-group label {
    /* svelte-ignore css_unused_selector */
    display: block;
    /* svelte-ignore css_unused_selector */
    margin-bottom: 0.5rem;
    /* svelte-ignore css_unused_selector */
    color: var(--text-primary);
    /* svelte-ignore css_unused_selector */
    font-weight: 500;
    /* svelte-ignore css_unused_selector */
    font-size: 0.9rem;
  }

  input {
    width: 100%;
    padding: 0.875rem;
    border: 2px solid var(--border-primary);
    border-radius: 8px;
    background: var(--bg-input);
    color: var(--text-primary);
    font-size: 1rem;
    transition: all 0.3s ease;
  }

  input:focus {
    /* svelte-ignore css_unused_selector */
    outline: none;
    /* svelte-ignore css_unused_selector */
    border-color: var(--accent-primary);
    /* svelte-ignore css_unused_selector */
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  input:invalid:not(:placeholder-shown) {
    /* svelte-ignore css_unused_selector */
    border-color: #ef4444;
  }

  .phone-input-container {
    display: flex;
    align-items: center;
    border: 2px solid var(--border-primary);
    border-radius: 8px;
    background: var(--bg-input);
    transition: all 0.3s ease;
  }

  .phone-input-container:focus-within {
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .country-code {
    padding: 0.875rem 1rem;
    color: var(--text-secondary);
    font-weight: 500;
    border-right: 1px solid var(--border-primary);
    background: rgba(0, 255, 255, 0.05);
  }

  .phone-input-container input {
    /* svelte-ignore css_unused_selector */
    border: none;
    /* svelte-ignore css_unused_selector */
    border-radius: 0 6px 6px 0;
    /* svelte-ignore css_unused_selector */
    margin: 0;
  }

  .phone-input-container input:focus {
    /* svelte-ignore css_unused_selector */
    box-shadow: none;
  }

  .input-hint {
    display: block;
    margin-top: 0.25rem;
    color: var(--text-muted);
    font-size: 0.8rem;
  }

  .primary-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, var(--accent-primary), var(--hover-primary));
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }

  .primary-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
  }

  .primary-btn:hover:not(:disabled)::before {
    left: 100%;
  }

  .primary-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
  }

  .primary-btn:active:not(:disabled) {
    transform: translateY(0) scale(0.98);
  }

  .primary-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  .verification-info {
    text-align: center;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: rgba(0, 255, 255, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(0, 255, 255, 0.2);
  }

  .verification-info p {
    /* svelte-ignore css_unused_selector */
    margin: 0 0 0.5rem 0;
    /* svelte-ignore css_unused_selector */
    color: var(--text-secondary);
  }

  .verification-info strong {
    /* svelte-ignore css_unused_selector */
    color: var(--text-primary);
    /* svelte-ignore css_unused_selector */
    font-size: 1.1rem;
  }

  #verification-code {
    text-align: center;
    font-size: 1.5rem;
    font-weight: bold;
    letter-spacing: 0.5rem;
    font-family: 'Courier New', monospace;
  }

  .resend-section {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-primary);
  }

  .resend-timer {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin: 0;
  }

  .link-btn {
    background: none;
    border: none;
    color: var(--accent-primary);
    cursor: pointer;
    font-size: 0.9rem;
    text-decoration: underline;
    transition: color 0.2s ease;
  }

  .link-btn:hover:not(:disabled) {
    color: var(--hover-primary);
  }

  .link-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    text-decoration: none;
  }

  .resend-limit {
    color: #ef4444;
    font-size: 0.8rem;
    margin: 0.5rem 0 0 0;
  }

  .success-step {
    text-align: center;
    padding: 2rem 1rem;
  }

  .success-icon {
    font-size: 3rem;
    color: #10b981;
    margin-bottom: 1rem;
  }

  .success-step h3 {
    /* svelte-ignore css_unused_selector */
    color: var(--text-primary);
    /* svelte-ignore css_unused_selector */
    margin: 0 0 1rem 0;
  }

  .success-step p {
    /* svelte-ignore css_unused_selector */
    color: var(--text-secondary);
    /* svelte-ignore css_unused_selector */
    margin: 0 0 1rem 0;
  }

  .redirect-notice {
    color: var(--text-muted);
    font-size: 0.9rem;
  }

  .auth-switch {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-primary);
  }

  /* Mobile responsive */
  @media (max-width: 480px) {
    .phone-registration-modal {
      margin: 0.5rem;
      max-width: none;
    }

    .modal-header {
      padding: 1.5rem 1rem 1rem 1rem;
    }

    .modal-body {
      padding: 1rem;
    }

    .phone-input-container {
      flex-direction: column;
      align-items: stretch;
    }

    .country-code {
      border-right: none;
      border-bottom: 1px solid var(--border-primary);
      border-radius: 6px 6px 0 0;
    }

    .phone-input-container input {
      border-radius: 0 0 6px 6px;
    }
  }

  /* Cyberpunk theme special effects */
  :global([data-theme="cyberpunk"]) .phone-registration-modal {
    border-color: var(--border-accent);
    box-shadow: 0 0 40px rgba(0, 255, 255, 0.3);
  }

  :global([data-theme="cyberpunk"]) .step-indicator {
    background: rgba(0, 255, 255, 0.05);
    border: 1px solid rgba(0, 255, 255, 0.2);
  }

  :global([data-theme="cyberpunk"]) .verification-info {
    background: rgba(0, 255, 255, 0.05);
    border-color: rgba(0, 255, 255, 0.3);
  }

  :global([data-theme="cyberpunk"]) .success-icon {
    text-shadow: 0 0 20px #10b981;
  }
</style>