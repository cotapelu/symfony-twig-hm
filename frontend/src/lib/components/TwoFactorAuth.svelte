<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import Input from './atoms/Input.svelte';
  import Button from './atoms/Button.svelte';
  import Icon from './atoms/Icon.svelte';
  import Alert from './molecules/Alert.svelte';

  interface Props {
    step?: 'setup' | 'verify' | 'backup';
    loading?: boolean;
    error?: string | null;
    qrCodeUrl?: string | null;
    backupCodes?: string[] | null;
    secret?: string;
    verifyCode?: string;
    // Callback props for Svelte 5 compatibility
    onSetup?: (detail: { secret: string }) => void;
    onVerify?: (detail: { code: string }) => void;
    onBack?: () => void;
    onGenerateBackup?: () => void;
    onDownloadBackup?: () => void;
    onSkipBackup?: () => void;
    onErrorChange?: (error: string | null) => void;
  }

  let {
    step = 'setup',
    loading = false,
    error = null,
    qrCodeUrl = null,
    backupCodes = null,
    secret = '',
    verifyCode = '',
    onSetup,
    onVerify,
    onBack,
    onGenerateBackup,
    onDownloadBackup,
    onSkipBackup,
    onErrorChange
  }: Props = $props();

  const dispatch = createEventDispatcher<{
    setup: { secret: string };
    verify: { code: string };
    back: undefined;
    generateBackup: undefined;
    downloadBackup: undefined;
    skipBackup: undefined;
    errorChange: string | null;
  }>();

  let code = $state('');
  // Local error state - sync from prop initially
  let localError = $state(error);

  // Sync error from prop
  $effect(() => {
    localError = error;
  });

  function handleSetup() {
    console.log('handleSetup called');
    dispatch('setup', { secret });
    onSetup?.({ secret });
    console.log('dispatch result:', true);
  }

  function handleVerify() {
    dispatch('verify', { code: verifyCode });
    onVerify?.({ code: verifyCode });
  }

  function handleGenerateBackup() {
    dispatch('generateBackup');
    onGenerateBackup?.();
  }

  function handleDownloadBackup() {
    dispatch('downloadBackup');
    onDownloadBackup?.();
  }

  function handleSkipBackup() {
    dispatch('skipBackup');
    onSkipBackup?.();
  }

  function formatCode(value: string): string {
    return value.toUpperCase().replace(/[^0-9A-Z]/g, '').slice(0, 6);
  }

  function clearError() {
    localError = null;
    dispatch('errorChange', null);
    onErrorChange?.(null);
  }
</script>

<div class="two-factor-auth">
  <div class="two-factor-content">
    {#if step === 'setup'}
      <div class="setup-step">
        <div class="step-header">
          <h2>Set Up Two-Factor Authentication</h2>
          <p>Scan the QR code below with your authenticator app</p>
        </div>

        {#if qrCodeUrl}
          <div class="qr-code-container">
            <img src={qrCodeUrl} alt="QR Code" class="qr-code" />
          </div>
        {/if}

        {#if secret}
          <div class="secret-container">
            <label for="secret">Secret Key</label>
            <div class="secret-input">
              <Input
                bind:value={secret}
                placeholder="Enter secret key"
                readonly
              />
              <Button
                variant="ghost"
                size="sm"
                onclick={() => navigator.clipboard.writeText(secret)}
                aria-label="Copy secret key"
              >
                <Icon name="copy" />
              </Button>
            </div>
          </div>
        {/if}

        {#if localError}
          <Alert type="error" description={localError} closable on:close={clearError} />
        {/if}

        <div class="step-actions">
          <Button
            variant="primary"
            loading={loading}
            disabled={!secret}
            onclick={handleSetup}
          >
            {loading ? 'Verifying...' : 'Next'}
          </Button>
        </div>
      </div>

    {:else if step === 'verify'}
      <div class="verify-step">
        <div class="step-header">
          <h2>Verify Two-Factor Authentication</h2>
          <p>Enter the 6-digit code from your authenticator app</p>
        </div>

        <div class="code-input-container">
          <Input
            bind:value={verifyCode}
            placeholder="123456"
            type="text"
          />
        </div>

        {#if localError}
          <Alert type="error" description={localError} closable on:close={clearError} />
        {/if}

        <div class="step-actions">
          <Button
            variant="secondary"
            onclick={() => { dispatch('back'); onBack?.(); }}
          >
            Back
          </Button>
          <Button
            variant="primary"
            loading={loading}
            disabled={verifyCode.length !== 6}
            onclick={handleVerify}
          >
            {loading ? 'Verifying...' : 'Verify'}
          </Button>
        </div>
      </div>

    {:else if step === 'backup'}
      <div class="backup-step">
        <div class="step-header">
          <h2>Backup Codes</h2>
          <p>Save these codes in a secure location</p>
        </div>

        {#if backupCodes}
          <div class="backup-codes-container">
            {#each backupCodes as code, i}
              <div class="backup-code">
                <span class="code-index">{i + 1}.</span>
                <span class="code-value">{code}</span>
                <Button
                  variant="ghost"
                  size="sm"
                  onclick={() => navigator.clipboard.writeText(code)}
                  aria-label="Copy backup code"
                >
                  <Icon name="copy" />
                </Button>
              </div>
            {/each}
          </div>
        {/if}

        <div class="backup-warning">
          <Icon name="warning" />
          <p>Keep these codes safe. They will not be shown again.</p>
        </div>

        <div class="step-actions">
          <Button
            variant="secondary"
            onclick={handleSkipBackup}
          >
            Skip
          </Button>
          <Button
            variant="primary"
            onclick={handleDownloadBackup}
          >
            Download
          </Button>
        </div>
      </div>
    {/if}
  </div>
</div>

<style>
  :global(:root) {
    --two-factor-bg: #ffffff;
    --two-factor-border: #e5e7eb;
    --two-factor-text: #1f2937;
    --two-factor-muted: #6b7280;
  }

  .two-factor-auth {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
    background: var(--two-factor-bg);
    border: 1px solid var(--two-factor-border);
    border-radius: 8px;
    padding: 2rem;
  }

  .two-factor-content {
    width: 100%;
    max-width: 400px;
  }

  .step-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .step-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    color: var(--two-factor-text);
  }

  .step-header p {
    margin: 0;
    color: var(--two-factor-muted);
  }

  .qr-code-container {
    display: flex;
    justify-content: center;
    margin-bottom: 1.5rem;
  }

  .qr-code {
    width: 200px;
    height: 200px;
    border: 1px solid var(--two-factor-border);
    border-radius: 8px;
    padding: 1rem;
  }

  .secret-container {
    margin-bottom: 1.5rem;
  }

  .secret-container label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--two-factor-text);
  }

  .secret-input {
    display: flex;
    gap: 0.5rem;
  }

  .code-input-container {
    margin-bottom: 1.5rem;
  }

  .backup-codes-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    max-height: 300px;
    overflow-y: auto;
  }

  .backup-code {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #f9fafb;
    border: 1px solid var(--two-factor-border);
    border-radius: 6px;
    font-family: monospace;
    font-size: 1.125rem;
  }

  .code-index {
    color: var(--two-factor-muted);
    font-weight: 600;
  }

  .code-value {
    flex: 1;
    color: var(--two-factor-text);
    letter-spacing: 0.125rem;
  }

  .backup-warning {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    color: #92400e;
  }

  .backup-warning p {
    margin: 0;
  }

  .step-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
  }
</style>