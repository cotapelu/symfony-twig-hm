<script lang="ts">
    import { onMount } from 'svelte';
    import { page } from '$app/stores';
    import { showToast } from '$lib/stores/global';
    import { fetchGroupDetails, leaveGroup, addParticipant, sendMessageAPI } from '$lib/api';
    import Button from '$lib/components/atoms/Button.svelte';
    import Input from '$lib/components/atoms/Input.svelte';
    import Typography from '$lib/components/atoms/Typography.svelte';
    import Card from '$lib/components/molecules/Card.svelte';

    interface Message {
      sender_name: string;
      content: string;
      created_at: string;
    }

    interface Group {
      title: string;
      participants?: { length: number }[];
    }

    let group: Group | null = null;
    let messages: Message[] = [];
    let newMessage = '';
    let newMemberId = '';
    let loading = true;
    let sending = false;

    $: groupId = $page.params.id as string;

    onMount(async () => {
        await loadGroup();
    });

    async function loadGroup() {
        loading = true;
        try {
            const data = await fetchGroupDetails(groupId);
            group = data.group;
            messages = data.messages || [];
        } catch (error) {
            showToast('Failed to load group', 'error');
        } finally {
            loading = false;
        }
    }

    async function sendGroupMessage() {
        if (!newMessage.trim()) return;

        sending = true;
        try {
            await sendMessageAPI(groupId, newMessage.trim());
            newMessage = '';
            await loadGroup(); // Refresh messages
            showToast('Message sent!', 'success');
        } catch (error) {
            showToast('Failed to send message', 'error');
        } finally {
            sending = false;
        }
    }

    async function handleLeaveGroup() {
        if (!confirm('Are you sure you want to leave this group?')) return;

        try {
            await leaveGroup(groupId);
            showToast('Left group successfully', 'success');
            window.history.back();
        } catch (error) {
            showToast('Failed to leave group', 'error');
        }
    }

    async function handleAddParticipant(userId: string) {
        try {
            await addParticipant(groupId, userId);
            showToast('Participant added successfully', 'success');
            await loadGroup();
        } catch (error) {
            showToast('Failed to add participant', 'error');
        }
    }

    function handleKeyDown(e: CustomEvent<KeyboardEvent>) {
        if (e.detail.key === 'Enter') {
            sendGroupMessage();
        }
    }
</script>

<main class="group-detail">
    {#if loading}
        <div class="loading">Loading group...</div>
    {:else if group}
        <header class="group-header">
            <Typography variant="h1">{group.title}</Typography>
            <Typography variant="body">{group.participants?.length || 0} members</Typography>
            <div class="group-actions">
                <Button onclick={handleLeaveGroup} variant="danger">Leave Group</Button>
                <Input
                    type="text"
                    placeholder="Enter user ID to add"
                    bind:value={newMemberId}
                />
                <Button onclick={() => handleAddParticipant(newMemberId)} disabled={!newMemberId?.trim()}>
                    Add Member
                </Button>
            </div>
        </header>

        <div class="group-content">
            <div class="messages-section">
                <Typography variant="h2">Messages</Typography>
                <div class="messages-list">
                    {#each messages as message}
                        <div class="message-item">
                            <strong>{message.sender_name}:</strong>
                            <span>{message.content}</span>
                            <small>{new Date(message.created_at).toLocaleString()}</small>
                        </div>
                    {/each}
                </div>

                <div class="message-input">
                    <Input
                        type="text"
                        bind:value={newMessage}
                        placeholder="Type a message..."
                        disabled={sending}
                    />
                    <Button onclick={sendGroupMessage} disabled={sending || !newMessage.trim()}>
                        {#if sending}Sending...{:else}Send{/if}
                    </Button>
                </div>
            </div>
        </div>
    {:else}
        <div class="error">Group not found</div>
    {/if}
</main>

<style>
    .group-detail {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem;
        background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
        color: #ffffff;
        min-height: 100vh;
    }

    .group-header {
        text-align: center;
        margin-bottom: 2rem;
        padding: 2rem;
        background: rgba(20, 20, 20, 0.8);
        border-radius: 12px;
        border: 1px solid rgba(0, 255, 255, 0.3);
    }

    .group-header h1 {
        color: #00ffff;
        margin-bottom: 0.5rem;
        text-shadow: 0 0 15px #00ffff;
    }

    .group-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1rem;
    }

    .group-actions button {
        padding: 0.5rem 1rem;
        border: 1px solid #00ffff;
        border-radius: 5px;
        background: rgba(0, 255, 255, 0.1);
        color: #00ffff;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .group-actions button:hover {
        background: rgba(0, 255, 255, 0.2);
        box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
    }

    .leave-btn {
        border-color: #ff6b6b;
        color: #ff6b6b;
    }

    .leave-btn:hover {
        background: rgba(255, 107, 107, 0.2);
        box-shadow: 0 0 10px rgba(255, 107, 107, 0.5);
    }

    .messages-section h2 {
        color: #ff00ff;
        margin-bottom: 1rem;
        text-shadow: 0 0 10px #ff00ff;
    }

    .messages-list {
        max-height: 400px;
        overflow-y: auto;
        margin-bottom: 2rem;
        padding: 1rem;
        background: rgba(20, 20, 20, 0.8);
        border-radius: 8px;
        border: 1px solid rgba(255, 0, 255, 0.3);
    }

    .message-item {
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        background: rgba(40, 40, 40, 0.5);
        border-radius: 6px;
        border-left: 3px solid #00ffff;
    }

    .message-item strong {
        color: #00ffff;
    }

    .message-item small {
        color: #cccccc;
        font-size: 0.8rem;
        display: block;
        margin-top: 0.25rem;
    }

    .message-input {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .message-input input {
        flex: 1;
        padding: 0.75rem;
        border: 1px solid rgba(0, 255, 255, 0.3);
        border-radius: 8px;
        background: rgba(20, 20, 20, 0.8);
        color: #ffffff;
        font-family: 'Courier New', monospace;
    }

    .message-input input:focus {
        outline: none;
        border-color: #00ffff;
        box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
    }

    .message-input button {
        padding: 0.75rem 1.5rem;
        background: linear-gradient(45deg, #00ffff, #ff00ff);
        border: none;
        border-radius: 8px;
        color: #000000;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .message-input button:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
    }

    .message-input button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .loading, .error {
        text-align: center;
        padding: 3rem;
        color: #00ffff;
    }

    .error {
        color: #ff6b6b;
    }
</style>