<script lang="ts">
    import { onMount } from 'svelte';
    import { get } from 'svelte/store';
    import type { Message } from '$lib/types';
    import { loadMessages } from '../../../stores/messages';
    import { user, subscribeToRealtime } from '$lib/stores/global';
    import MessageInput from './MessageInput.svelte';
    import MessageBubble from './MessageBubble.svelte';
    import PollSystem from '$lib/components/PollSystem.svelte';

    export let conversationId = '';

    let messages: Message[] = [];
    let loading = true;
    let error = '';
    let typingUsers = new Set<string>();

    onMount(async () => {
      if (conversationId) {
        await fetchMessages();
      }

      subscribeToRealtime('conversation/' + conversationId, (data: unknown) => {
        const messageData = data as { type: string; message: Message };
        if (messageData.type === 'new_message') {
          messages = [...messages, messageData.message];
        }
      });

      subscribeToRealtime('conversation/' + conversationId + '/typing', (data: unknown) => {
        const typingData = data as { type: string; userId: string };
        if (typingData.type === 'typing_start') {
          typingUsers.add(typingData.userId);
        } else if (typingData.type === 'typing_stop') {
          typingUsers.delete(typingData.userId);
        }
      });
    });

    async function fetchMessages() {
      loading = true;
      error = '';
      try {
        messages = await loadMessages(conversationId);
      } catch (err) {
        error = 'Failed to load messages';
      } finally {
        loading = false;
      }
    }

    interface MessageSentEvent {
        detail: {
            content: string;
        };
    }

    function handleMessageSent(event: CustomEvent<{ content: string }>) {
     const currentUser = get(user);
     const newMessage: Message = {
       id: Date.now().toString(),
       conversationId: conversationId,
       senderId: currentUser?.id || 'anonymous',
       content: event.detail.content,
       type: 'text',
       createdAt: new Date().toISOString(),
     };
     messages = [...messages, newMessage];
    }
</script>

<div class="message-thread">
  <div class="messages-container">
    {#if loading}
      <p>Loading messages...</p>
    {:else if error}
      <p class="error">Error: {error}</p>
    {:else}
      {#each messages as message (message.id)}
        <MessageBubble {message} />
      {/each}

      {#if typingUsers.size > 0}
        <div class="typing-indicator">
          <em>{Array.from(typingUsers).join(', ')} is typing...</em>
        </div>
      {/if}
    {/if}
  </div>

  <MessageInput on:send={handleMessageSent} />

  <PollSystem />
</div>

<style>
  .message-thread {
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
  }

  .error {
    color: #ff6b6b;
    text-align: center;
  }

  .typing-indicator {
    padding: 0.5rem 1rem;
    color: #666;
    font-style: italic;
  }
</style>