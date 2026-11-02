<script lang="ts">
    import { sendMessage } from '../../../stores/messages';
    import { user } from '$lib/stores/global';
    import SmartReply from '$lib/components/SmartReply.svelte';
    import VoiceRecorder from '$lib/components/VoiceRecorder.svelte';
    import PollCreator from '$lib/components/PollCreator.svelte';
    import type { Conversation, FileItem } from '$lib/types';

    export let selectedConversation: Conversation | null = null;

    export let onMessageSent: () => void = () => {};

    export let newMessage: string = '';

    export let selectedFiles: File[] = [];

    let fileInput: HTMLInputElement;

    let showPollCreator = false;

    let showVoiceRecorder = false;

    let voiceMessage: { blob: Blob } | null = null;

    let typingTimeout: ReturnType<typeof setTimeout>;

    async function handleSendMessage() {
      if (!newMessage.trim()) return;

      try {
        await sendMessage(selectedConversation?.id || '', newMessage.trim());

        onMessageSent();
        newMessage = '';
        selectedFiles = [];
      } catch (error) {
        console.error('Failed to send message:', error);
      }
    }

    function handleKeyPress(event: KeyboardEvent) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            handleSendMessage();
        }
    }

    function handleFileSelect(event: Event) {
        const target = event.target as HTMLInputElement;
        const files = Array.from(target.files || []);
        selectedFiles = [...selectedFiles, ...files];
    }

    function removeFile(index: number) {
        selectedFiles = selectedFiles.filter((_, i) => i !== index);
    }

    function handleVoiceRecording(event: CustomEvent) {
        voiceMessage = event.detail;
        showVoiceRecorder = false;
        handleSendVoiceMessage(event.detail);
    }

    async function handleSendVoiceMessage(voiceData: { blob: Blob }) {
        try {
            await sendMessage(selectedConversation?.id || '', '');
            onMessageSent();
            voiceMessage = null;
        } catch (error) {
            console.error('Failed to send voice message:', error);
        }
    }

    function handlePollCreate(event: CustomEvent) {
        const pollData = event.detail;
        console.log('Poll created:', pollData);
        showPollCreator = false;
    }

    function handleSmartReplySelect(event: CustomEvent) {
        const { suggestion } = event.detail;
        newMessage = suggestion.text;
    }
</script>

<div class="message-input" role="region" aria-label="Message input">
    {#if selectedFiles.length > 0}
        <div class="file-preview">
            {#each selectedFiles as file, index}
                <div class="file-item">
                    <span class="file-name">{file.name}</span>
                    <button class="remove-file" onclick={() => removeFile(index)}>×</button>
                </div>
            {/each}
        </div>
    {/if}

    {#if showVoiceRecorder}
        <VoiceRecorder
            on:recordingComplete={handleVoiceRecording}
            on:error={(e) => console.error('Voice recording error:', e.detail)}
        />
    {/if}

    {#if showPollCreator}
        <PollCreator
            on:create={handlePollCreate}
            on:cancel={() => showPollCreator = false}
        />
    {/if}

    <div class="input-container">
        <textarea
            bind:value={newMessage}
            placeholder="Type a message..."
            onkeydown={handleKeyPress}
            disabled={!selectedConversation}
            aria-label="Message input"
            aria-describedby="message-help"
            rows="3"
        ></textarea>
        <div id="message-help" class="sr-only">
            Press Enter to send, Shift+Enter for new line. Use voice button for voice messages.
        </div>

        <div class="input-actions">
            <button
                class="file-button"
                onclick={() => fileInput?.click()}
                disabled={!selectedConversation}
                aria-label="Attach file"
                title="Attach file"
            >
                📎
            </button>

            <button
                class="voice-button"
                onclick={() => showVoiceRecorder = !showVoiceRecorder}
                disabled={!selectedConversation}
                class:active={showVoiceRecorder}
                aria-label={showVoiceRecorder ? 'Hide voice recorder' : 'Show voice recorder'}
                aria-pressed={showVoiceRecorder}
            >
                🎤
            </button>

            <button
                class="poll-button"
                onclick={() => showPollCreator = !showPollCreator}
                disabled={!selectedConversation}
                aria-label="Create poll"
                title="Create poll"
            >
                📊
            </button>

            <button
                class="send-button"
                onclick={handleSendMessage}
                disabled={!selectedConversation || (!newMessage.trim() && selectedFiles.length === 0)}
                aria-label="Send message"
                title="Send message"
            >
                Send
            </button>
        </div>
    </div>

    <input
        bind:this={fileInput}
        type="file"
        multiple
        style="display: none"
        onchange={handleFileSelect}
    />

    <!-- AI Smart Reply Suggestions -->
    <SmartReply
      message={newMessage}
      conversationId={selectedConversation?.id || ''}
      isVisible={newMessage.length > 5}
      on:select={handleSmartReplySelect}
    />
</div>

<style>
    .message-input {
        border-top: 1px solid #e5e7eb;
        padding: 16px;
        background: white;
    }

    .file-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }

    .file-item {
        display: flex;
        align-items: center;
        background: #f3f4f6;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
    }

    .file-name {
        margin-right: 8px;
    }

    .remove-file {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 16px;
        line-height: 1;
    }

    .input-container {
        display: flex;
        align-items: flex-end;
        gap: 8px;
    }

    textarea {
        flex: 1;
        min-height: 40px;
        max-height: 120px;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        resize: vertical;
        font-family: inherit;
        font-size: 14px;
    }

    textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .input-actions {
        display: flex;
        gap: 4px;
    }

    button {
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.2s;
    }

    .file-button, .voice-button, .poll-button {
        background: #f3f4f6;
        color: #6b7280;
    }

    .file-button:hover, .voice-button:hover, .poll-button:hover {
        background: #e5e7eb;
    }

    .voice-button.active {
        background: #dbeafe;
        color: #3b82f6;
    }

    .send-button {
        background: #3b82f6;
        color: white;
    }

    .send-button:hover:not(:disabled) {
        background: #2563eb;
    }

    button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>