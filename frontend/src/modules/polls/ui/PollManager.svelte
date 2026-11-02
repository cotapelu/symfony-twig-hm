<script lang="ts">
  import { onMount } from 'svelte';
  import { pollsStore, loadPolls, vote, hasVoted, getUserVote, loading, error } from '../state/pollsStore';
  import type { Poll } from '../contracts/pollsTypes';

  let polls: Poll[] = [];
  let isLoading = false;
  let errorMessage: string | null = null;

  pollsStore.subscribe(state => {
    polls = state.polls;
    isLoading = state.loading;
    errorMessage = state.error;
  });

  onMount(() => {
    loadPolls();
  });

  function handleVote(pollId: string, optionId: string, allowMultiple: boolean) {
    if (hasVoted(pollId)) {
      return;
    }

    const poll = polls.find(p => p.id === pollId);
    if (!poll || poll.status === 'closed') {
      return;
    }

    const optionIds = allowMultiple 
      ? poll.options.filter(o => {
          const currentVote = getUserVote(pollId);
          return currentVote?.includes(o.id) || o.id === optionId;
        }).map(o => o.id)
      : [optionId];

    vote({ pollId, optionIds }).catch(err => {
      alert(err.message);
    });
  }

  function isOptionSelected(pollId: string, optionId: string): boolean {
    const userVote = getUserVote(pollId);
    return userVote?.includes(optionId) ?? false;
  }

  function calculatePercentage(votes: number, total: number): number {
    if (total === 0) return 0;
    return Math.round((votes / total) * 100);
  }
</script>

<div class="polls">
  <h1>📊 Khảo sát & Bình chọn</h1>
  
  {#if isLoading}
    <div class="loading">Đang tải...</div>
  {/if}

  {#if errorMessage}
    <div class="error">{errorMessage}</div>
  {/if}

  {#each polls as poll}
    <div class="poll-card">
      <h3>{poll.question}</h3>
      <div class="options">
        {#each poll.options as option, i}
          <div 
            class="option" 
            class:selected={isOptionSelected(poll.id, option.id)}
            class:disabled={hasVoted(poll.id) || poll.status === 'closed'}
            on:click={() => handleVote(poll.id, option.id, poll.allowMultiple)}
            on:keypress={(e) => e.key === 'Enter' && handleVote(poll.id, option.id, poll.allowMultiple)}
            role="button"
            tabindex="0"
          >
            <div class="option-bar" style="width: {calculatePercentage(option.votes, poll.totalVotes)}%"></div>
            <div class="option-content">
              <span>{option.text}</span>
              <span>{option.votes} phiếu ({calculatePercentage(option.votes, poll.totalVotes)}%)</span>
            </div>
          </div>
        {/each}
      </div>
      <p class="total">Tổng: {poll.totalVotes} phiếu bầu</p>
      {#if poll.status === 'closed'}
        <span class="closed-badge">Đã đóng</span>
      {/if}
      {#if hasVoted(poll.id)}
        <span class="voted-badge">Đã bình chọn</span>
      {/if}
    </div>
  {/each}

  {#if polls.length === 0 && !isLoading}
    <div class="empty">Chưa có khảo sát nào</div>
  {/if}
</div>

<style>
  .polls { padding: 20px; max-width: 600px; margin: 0 auto; }
  .poll-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
  .option { position: relative; background: #f3f4f6; margin: 8px 0; padding: 12px; border-radius: 8px; cursor: pointer; overflow: hidden; transition: all 0.2s; }
  .option:hover:not(.disabled) { background: #e5e7eb; }
  .option.selected { border: 2px solid #3b82f6; }
  .option.disabled { cursor: not-allowed; opacity: 0.7; }
  .option-bar { position: absolute; left: 0; top: 0; height: 100%; background: #3b82f6; opacity: 0.2; transition: width 0.3s; }
  .option-content { position: relative; display: flex; justify-content: space-between; z-index: 1; }
  .total { text-align: center; color: #6b7280; margin-top: 16px; }
  .closed-badge { display: block; text-align: center; color: #ef4444; margin-top: 8px; }
  .voted-badge { display: block; text-align: center; color: #10b981; margin-top: 8px; }
  .loading, .error, .empty { text-align: center; padding: 20px; }
  .error { color: #ef4444; }
</style>