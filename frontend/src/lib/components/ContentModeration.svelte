<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { fade } from 'svelte/transition';
  import { Card, Typography, Badge, Space, Divider } from '$lib/components';

  export let moderation: any = null;
  export let onDismiss: (() => void) | null = null;

  const dispatch = createEventDispatcher();

  function dismiss() {
    dispatch('dismiss');
    onDismiss?.();
  }

  function getSeverityColor(severity: string): 'success' | 'error' | 'warning' | 'info' | 'default' {
    switch (severity) {
      case 'high': return 'error';
      case 'medium': return 'warning';
      case 'low': return 'success';
      default: return 'default';
    }
  }

  function getSeverityIcon(severity: string): string {
    switch (severity) {
      case 'high': return '🚫';
      case 'medium': return '⚠️';
      case 'low': return 'ℹ️';
      default: return '🛡️';
    }
  }
</script>

{#if moderation && moderation.is_flagged}
  <div
    class="content-moderation"
    class:high={moderation.severity === 'high'}
    class:medium={moderation.severity === 'medium'}
    class:low={moderation.severity === 'low'}
    in:fade
    role="alert"
    aria-live="assertive"
  >
    <Card>
      <div class="moderation-header">
        <Space>
          <Typography variant="body">{getSeverityIcon(moderation.severity)}</Typography>
          <div class="moderation-title">
            <Typography variant="h6">Content Moderation Alert</Typography>
            <span class="severity-badge" style="background: {getSeverityColor(moderation.severity) === 'error' ? '#dc3545' : getSeverityColor(moderation.severity) === 'warning' ? '#ffc107' : getSeverityColor(moderation.severity) === 'success' ? '#28a745' : '#6c757d'}">
              {moderation.severity.toUpperCase()} SEVERITY
            </span>
          </div>
          <button
            class="dismiss-btn"
            onclick={dismiss}
            aria-label="Dismiss moderation alert"
          >
            ×
          </button>
        </Space>
      </div>

      <div class="moderation-content">
        {#if moderation.flags && moderation.flags.length > 0}
          <div class="flags-list">
            <Typography variant="caption">Detected issues:</Typography>
            <Space wrap>
              {#each moderation.flags as flag}
                <Badge variant="outlined">{flag}</Badge>
              {/each}
            </Space>
          </div>
        {/if}

        {#if moderation.recommendation}
          <div class="recommendation">
            <Typography variant="caption">💡 {moderation.recommendation}</Typography>
          </div>
        {/if}

        <Divider />
        <div class="moderation-meta">
          <Typography variant="caption">
            Confidence: {Math.round(moderation.confidence * 100)}%
            {#if moderation.categories && moderation.categories.length > 0}
              • Categories: {moderation.categories.join(', ')}
            {/if}
          </Typography>
        </div>
      </div>
    </Card>
  </div>
{/if}

<style>
  .content-moderation {
    margin: 0.5rem 0;
  }

  .high {
    --card-border-color: #dc3545;
    --card-background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
  }

  .medium {
    --card-border-color: #ffc107;
    --card-background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
  }

  .low {
    --card-border-color: #28a745;
    --card-background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
  }

  .moderation-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.3);
  }

  .moderation-title {
    flex: 1;
  }

  .dismiss-btn {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }

  .dismiss-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
  }

  .moderation-content {
    padding: 0.75rem 1rem;
  }

  .flags-list {
    margin-bottom: 0.5rem;
  }

  .recommendation {
    margin-bottom: 0.5rem;
  }

  .moderation-meta {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .moderation-header {
      padding: 0.5rem 0.75rem;
      gap: 0.5rem;
    }

    .moderation-content {
      padding: 0.5rem 0.75rem;
    }
  }
</style>