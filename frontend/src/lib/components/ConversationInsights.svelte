<script lang="ts">
  import { onMount } from 'svelte';

  interface Topic {
    name: string;
    color?: string;
    relevance?: number;
  }

  interface Participant {
    name: string;
    role: string;
    messageCount: number;
    sentimentScore: number;
  }

  interface Recommendation {
    icon?: string;
    text: string;
  }

  interface Insights {
    overallSentiment?: {
      label: string;
      score: number;
    };
    keyTopics?: Topic[];
    summary?: string;
    participants?: Participant[];
    recommendations?: Recommendation[];
  }

  export let conversationId = '';
  export let isVisible = false;

  let insights: Insights | null = null;
  let isLoading = false;
  let error: string | null = null;

  onMount(async () => {
    if (conversationId && isVisible) {
      fetchInsights();
    }
  });

  $: if (conversationId && isVisible) {
    fetchInsights();
  }

  async function fetchInsights() {
    if (!conversationId) return;

    isLoading = true;
    error = null;

    try {
      const response = await fetch(`/api/ai/conversation-insights/${conversationId}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
      });

      if (!response.ok) {
        throw new Error('Failed to fetch insights');
      }

      insights = await response.json();
    } catch (err: any) {
      error = err.message;
      console.error('Insights error:', err);
    } finally {
      isLoading = false;
    }
  }

  function getSentimentColor(sentiment: string) {
    switch (sentiment) {
      case 'positive': return '#10b981';
      case 'negative': return '#ef4444';
      case 'neutral': return '#6b7280';
      default: return '#6b7280';
    }
  }

  function getSentimentIcon(sentiment: string) {
    switch (sentiment) {
      case 'positive': return '😊';
      case 'negative': return '😔';
      case 'neutral': return '😐';
      default: return '🤔';
    }
  }
</script>

{#if isVisible}
  <div class="conversation-insights">
    <div class="insights-header">
      <h3 class="insights-title">🤖 Conversation Insights</h3>
      <button class="refresh-btn" onclick={fetchInsights} disabled={isLoading}>
        {isLoading ? '⏳' : '🔄'}
      </button>
    </div>

    {#if isLoading}
      <div class="loading-state">
        <div class="loading-spinner"></div>
        <span>Analyzing conversation...</span>
      </div>
    {:else if error}
      <div class="error-state">
        ⚠️ {error}
      </div>
    {:else if insights}
      <div class="insights-content">
        <!-- Overall Sentiment -->
        {#if insights.overallSentiment}
          <div class="insight-card">
            <h4 class="card-title">Overall Sentiment</h4>
            <div class="sentiment-display">
              <span class="sentiment-icon">{getSentimentIcon(insights.overallSentiment.label)}</span>
              <span 
                class="sentiment-label" 
                style="color: {getSentimentColor(insights.overallSentiment.label)}"
              >
                {insights.overallSentiment.label}
              </span>
              <span class="sentiment-score">
                {Math.round(insights.overallSentiment.score * 100)}% confidence
              </span>
            </div>
          </div>
        {/if}

        <!-- Key Topics -->
        {#if insights.keyTopics && insights.keyTopics.length > 0}
          <div class="insight-card">
            <h4 class="card-title">Key Topics</h4>
            <div class="topics-list">
              {#each insights.keyTopics as topic}
                <span class="topic-tag" style="background: {topic.color || '#e5e7eb'}">
                  {topic.name}
                  {#if topic.relevance}
                    <span class="topic-relevance">({Math.round(topic.relevance * 100)}%)</span>
                  {/if}
                </span>
              {/each}
            </div>
          </div>
        {/if}

        <!-- Conversation Summary -->
        {#if insights.summary}
          <div class="insight-card">
            <h4 class="card-title">Summary</h4>
            <p class="summary-text">{insights.summary}</p>
          </div>
        {/if}

        <!-- Participant Analysis -->
        {#if insights.participants && insights.participants.length > 0}
          <div class="insight-card">
            <h4 class="card-title">Participant Activity</h4>
            <div class="participants-analysis">
              {#each insights.participants as participant}
                <div class="participant-item">
                  <div class="participant-info">
                    <span class="participant-name">{participant.name}</span>
                    <span class="participant-role">{participant.role}</span>
                  </div>
                  <div class="participant-stats">
                    <span class="stat-item">💬 {participant.messageCount} messages</span>
                    <span class="stat-item">😊 {Math.round(participant.sentimentScore * 100)}% positive</span>
                  </div>
                </div>
              {/each}
            </div>
          </div>
        {/if}

        <!-- Actionable Insights -->
        {#if insights.recommendations && insights.recommendations.length > 0}
          <div class="insight-card">
            <h4 class="card-title">💡 Recommendations</h4>
            <ul class="recommendations-list">
              {#each insights.recommendations as recommendation}
                <li class="recommendation-item">
                  <span class="recommendation-icon">{recommendation.icon || '💡'}</span>
                  <span class="recommendation-text">{recommendation.text}</span>
                </li>
              {/each}
            </ul>
          </div>
        {/if}
      </div>
    {:else}
      <div class="empty-state">
        No insights available yet. Try having more conversation!
      </div>
    {/if}
  </div>
{/if}

<style>
  .conversation-insights {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    animation: slideIn 0.3s ease-out;
  }

  .insights-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
  }

  .insights-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
  }

  .refresh-btn {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.375rem;
    transition: all 0.15s ease;
    font-size: 1rem;
  }

  .refresh-btn:hover:not(:disabled) {
    background: #f3f4f6;
    color: #374151;
  }

  .refresh-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .loading-state,
  .error-state,
  .empty-state {
    padding: 2rem 1.5rem;
    text-align: center;
    color: #6b7280;
  }

  .loading-spinner {
    width: 24px;
    height: 24px;
    border: 2px solid #e5e7eb;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    display: inline-block;
    margin-right: 0.75rem;
  }

  .error-state {
    color: #ef4444;
  }

  .insights-content {
    padding: 1.5rem;
    max-height: 600px;
    overflow-y: auto;
  }

  .insight-card {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
  }

  .card-title {
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 0.75rem 0;
  }

  .sentiment-display {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .sentiment-icon {
    font-size: 1.5rem;
  }

  .sentiment-label {
    font-size: 1.125rem;
    font-weight: 600;
    text-transform: capitalize;
  }

  .sentiment-score {
    font-size: 0.875rem;
    color: #6b7280;
  }

  .topics-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .topic-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
  }

  .topic-relevance {
    font-size: 0.75rem;
    opacity: 0.8;
  }

  .summary-text {
    color: #4b5563;
    line-height: 1.6;
    margin: 0;
  }

  .participants-analysis {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .participant-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: white;
    border-radius: 0.375rem;
    border: 1px solid #e5e7eb;
  }

  .participant-info {
    display: flex;
    flex-direction: column;
  }

  .participant-name {
    font-weight: 500;
    color: #111827;
  }

  .participant-role {
    font-size: 0.875rem;
    color: #6b7280;
  }

  .participant-stats {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
  }

  .stat-item {
    font-size: 0.75rem;
    color: #6b7280;
  }

  .recommendations-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .recommendation-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem;
    background: white;
    border-radius: 0.375rem;
    border: 1px solid #e5e7eb;
  }

  .recommendation-icon {
    font-size: 1.125rem;
    flex-shrink: 0;
  }

  .recommendation-text {
    color: #374151;
    line-height: 1.5;
    margin: 0;
  }

  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateX(-20px);
    }
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* Dark theme support */
  @media (prefers-color-scheme: dark) {
    .conversation-insights {
      background: #1f2937;
      border-color: #374151;
    }

    .insights-header {
      border-color: #374151;
    }

    .insights-title {
      color: #f9fafb;
    }

    .refresh-btn:hover:not(:disabled) {
      background: #374151;
      color: #f9fafb;
    }

    .insight-card {
      background: #374151;
      border-color: #4b5563;
    }

    .card-title {
      color: #f9fafb;
    }

    .summary-text {
      color: #d1d5db;
    }

    .participant-item {
      background: #1f2937;
      border-color: #4b5563;
    }

    .participant-name {
      color: #f9fafb;
    }

    .recommendation-item {
      background: #1f2937;
      border-color: #4b5563;
    }

    .recommendation-text {
      color: #f9fafb;
    }
  }

  /* Mobile responsive */
  @media (max-width: 640px) {
    .conversation-insights {
      max-width: 100%;
      margin: 0 1rem;
    }

    .insights-header {
      padding: 1rem;
    }

    .insights-content {
      padding: 1rem;
    }

    .participant-item {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }

    .participant-stats {
      align-items: flex-start;
    }
  }
</style>
