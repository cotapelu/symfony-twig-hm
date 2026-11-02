<script lang="ts">
    import { conversations, selectedConversation, loadConversations, selectConversation } from '../../../stores/messages';
    import UnreadBadge from '$lib/components/UnreadBadge.svelte';
    import Skeleton from '$lib/components/Skeleton.svelte';
    import type { Conversation } from '$lib/types';

    export let loading = false;

    function handleSelectConversation(conversationId: string): void {
        selectConversation(conversationId);
    }
</script>

<div class="conversation-list">
    {#if loading}
        {#each Array(5) as _}
            <div class="conversation-item skeleton">
                <Skeleton height="40px" />
            </div>
        {/each}
    {:else}
        {#each $conversations as conversation}
            <div
                class="conversation-item"
                class:selected={$selectedConversation?.id === conversation.id}
                onclick={() => handleSelectConversation(conversation.id)}
            >
                <div class="conversation-header">
                    <h4 class="conversation-title">{conversation.title}</h4>
                    {#if conversation.unread && conversation.unread > 0}
                        <UnreadBadge count={conversation.unread} />
                    {/if}
                </div>
                <p class="last-message">{conversation.lastMessage || 'No messages yet'}</p>
                <span class="timestamp">{new Date().toLocaleTimeString()}</span>
            </div>
        {/each}
    {/if}
</div>

<style>
    .conversation-list {
        flex: 1;
        overflow-y: auto;
        border-right: 1px solid #e5e7eb;
    }

    .conversation-item {
        padding: 12px 16px;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .conversation-item:hover {
        background-color: #f9fafb;
    }

    .conversation-item.selected {
        background-color: #dbeafe;
        border-right: 3px solid #3b82f6;
    }

    .conversation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
    }

    .conversation-title {
        font-weight: 600;
        font-size: 14px;
        color: #1f2937;
        margin: 0;
    }

    .last-message {
        font-size: 12px;
        color: #6b7280;
        margin: 4px 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .timestamp {
        font-size: 10px;
        color: #9ca3af;
    }

    .skeleton {
        margin: 8px 16px;
    }
</style>