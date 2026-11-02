<script lang="ts">

  import { Card, Typography, Button, Space } from '$lib/components';

  export let error: any = null;
  export let retry: (() => void) | null = null;

  let hasError = false;
  let errorMessage = '';

  $: if (error) {
    hasError = true;
    errorMessage = error.message || 'An error occurred';
  }

  function handleRetry() {
    if (retry) {
      hasError = false;
      errorMessage = '';
      retry();
    }
  }
</script>

{#if hasError}
  <div class="error-boundary">
    <Card variant="outlined">
      <Space direction="vertical" align="center">
        <Typography variant="h6" color="error">🚨 Something went wrong</Typography>
        <Typography variant="body" color="error">{errorMessage}</Typography>
        {#if retry}
          <Button variant="primary" onclick={handleRetry}>Try Again</Button>
        {/if}
      </Space>
    </Card>
  </div>
{:else}
  <slot />
{/if}

<style>
  .error-boundary {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
    padding: 2rem;
  }
</style>