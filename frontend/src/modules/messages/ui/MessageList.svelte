<script lang="ts">
    import { activeConversation, loading } from '../../../stores/messages';
    import MessageItem from './MessageItem.svelte';
    import Skeleton from '$lib/components/Skeleton.svelte';
    import TypingIndicators from '$lib/components/TypingIndicators.svelte';
    import type { Message } from '$lib/types';

    /** @type {() => void} */
    export let scrollToBottom = () => {};

    /** @type {import('$lib/types').TypingUser[]} */
    export let typingUsers: import('$lib/types').TypingUser[] = [];

    /** @type {HTMLElement} */
    let messageContainer: HTMLElement;

    /** @type {number} */
    let focusedMessageIndex = -1;

    // Auto scroll to bottom when new messages arrive
    $: if (messageContainer && $activeConversation.length > 0) {
        scrollToBottom();
    }

    /**
     * @param {KeyboardEvent} event
     */
    // Keyboard navigation
    function handleKeydown(event: KeyboardEvent) {
        if (!$activeConversation.length) return;

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                focusedMessageIndex = Math.min(focusedMessageIndex + 1, $activeConversation.length - 1);
                scrollToFocusedMessage();
                break;
            case 'ArrowUp':
                event.preventDefault();
                focusedMessageIndex = Math.max(focusedMessageIndex - 1, 0);
                scrollToFocusedMessage();
                break;
            case 'Home':
                event.preventDefault();
                focusedMessageIndex = 0;
                scrollToFocusedMessage();
                break;
            case 'End':
                event.preventDefault();
                focusedMessageIndex = $activeConversation.length - 1;
                scrollToFocusedMessage();
                break;
        }
    }

    function scrollToFocusedMessage() {
        if (focusedMessageIndex >= 0 && messageContainer) {
            const focusedElement = messageContainer.children[focusedMessageIndex];
            if (focusedElement) {
                focusedElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                (focusedElement as HTMLElement).focus();
            }
        }
    }
</script>

<div class="message-list" bind:this={messageContainer} onkeydown={handleKeydown} tabindex="0" role="log" aria-label="Message list">
    {#if $loading}
        {#each Array(10) as _}
            <div class="message-skeleton">
                <Skeleton height="60px" />
            </div>
        {/each}
    {:else if $activeConversation.length > 0}
        {#each $activeConversation as message, index}
            <MessageItem {message} />
        {/each}

        {#if typingUsers.length > 0}
            <TypingIndicators {typingUsers} />
        {/if}
    {:else}
        <div class="no-conversation">
            <p>Select a conversation to start messaging</p>
        </div>
    {/if}
</div>

<style>
    .message-list {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .message-skeleton {
        margin-bottom: 8px;
    }

    .no-conversation {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #6b7280;
        text-align: center;
    }
</style>