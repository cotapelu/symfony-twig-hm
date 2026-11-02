<script lang="ts">
    import { user } from '$lib/stores/global';
    import MessageReactions from '$lib/components/organisms/MessageReactions.svelte';
    import MessageReadReceipt from '$lib/components/organisms/MessageReadReceipt.svelte';
    import type { Message } from '$lib/types';

    export let message: Message;

    $: isOwn = message.senderId === $user?.id;
</script>

<div class="message-item" class:own={isOwn}>
    <div class="message-content">
        {#if message.files && message.files.length > 0}
            <div class="message-files">
                {#each message.files as file}
                    {#if file.type.startsWith('image/')}
                        <img src={file.url} alt={file.name} class="message-image" />
                    {:else}
                        <a href={file.url} download={file.name} class="message-file">
                            📎 {file.name}
                        </a>
                    {/if}
                {/each}
            </div>
        {/if}

        {#if message.content}
            <p class="message-text">{message.content}</p>
        {/if}

        <div class="message-meta">
            <span class="timestamp">{new Date(message.timestamp).toLocaleTimeString()}</span>
            {#if isOwn}
                <MessageReadReceipt messageId={message.id} isOwnMessage={true} readCount={0} readers={[]} showDetails={false} />
            {/if}
        </div>

        <MessageReactions messageId={message.id} currentReactions={{}} currentUserId={$user?.id || ''} showPicker={false} />
    </div>
</div>

<style>
    .message-item {
        display: flex;
        margin-bottom: 8px;
    }

    .message-item.own {
        justify-content: flex-end;
    }

    .message-content {
        max-width: 70%;
        padding: 8px 12px;
        border-radius: 12px;
        background: #f3f4f6;
        position: relative;
    }

    .message-item.own .message-content {
        background: #3b82f6;
        color: white;
    }

    .message-files {
        margin-bottom: 8px;
    }

    .message-image {
        max-width: 200px;
        max-height: 200px;
        border-radius: 8px;
        margin-bottom: 4px;
    }

    .message-file {
        display: inline-block;
        padding: 4px 8px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
        text-decoration: none;
        color: inherit;
        font-size: 12px;
        margin-right: 4px;
        margin-bottom: 4px;
    }

    .message-item.own .message-file {
        background: rgba(255, 255, 255, 0.3);
    }

    .message-text {
        margin: 0;
        word-wrap: break-word;
    }

    .message-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 4px;
        font-size: 10px;
        opacity: 0.7;
    }
</style>