<script lang="ts">
  import { aiStore, loadAIConfig, processAIRequest } from '../state/aiStore';
  import { onMount } from 'svelte';
  import type { AIConversation, AIMessage } from '../contracts/aiTypes';

  let input = '';
  let activeTab = 'chat';
  let selectedModel = 'gpt-4';
  let isRecording = false;
  let conversations: AIConversation[] = [];
  let currentConversation: AIConversation | null = null;

  onMount(() => {
    loadAIConfig();
  });

  $: loading = $aiStore.loading;
  $: suggestions = $aiStore.suggestions;
  $: config = $aiStore.config;

  async function handleSend() {
    if (!input.trim()) return;

    const userMessage = input;
    input = '';

    try {
      const response = await processAIRequest({
        message: userMessage,
        model: selectedModel,
        conversationId: currentConversation?.id
      });

      // Update conversation
      if (currentConversation) {
        currentConversation.messages = [
          ...currentConversation.messages,
          { role: 'user', content: userMessage, timestamp: new Date().toISOString() },
          { role: 'assistant', content: response.content, timestamp: new Date().toISOString() }
        ];
      } else {
        currentConversation = {
          id: crypto.randomUUID(),
          title: userMessage.slice(0, 50) + (userMessage.length > 50 ? '...' : ''),
          messages: [
            { role: 'user', content: userMessage, timestamp: new Date().toISOString() },
            { role: 'assistant', content: response.content, timestamp: new Date().toISOString() }
          ],
          createdAt: new Date().toISOString()
        };
        conversations = [currentConversation, ...conversations];
      }
    } catch (error) {
      console.error('AI request failed:', error);
    }
  }

  function handleKeyDown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  }

  function toggleRecording() {
    isRecording = !isRecording;
    // Voice input implementation would go here
  }

  function startNewChat() {
    currentConversation = null;
    input = '';
  }

  function loadConversation(conv: AIConversation) {
    currentConversation = conv;
  }

  function formatTime(timestamp: string) {
    return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  const quickActions = [
    { icon: '✍️', label: 'Write an email', prompt: 'Help me write a professional email about...' },
    { icon: '💡', label: 'Brainstorm ideas', prompt: 'Help me brainstorm ideas for...' },
    { icon: '📊', label: 'Analyze data', prompt: 'Analyze this data and provide insights...' },
    { icon: '🔧', label: 'Debug code', prompt: 'Help me debug this code...' },
    { icon: '📝', label: 'Summarize text', prompt: 'Summarize this text for me...' },
    { icon: '🌍', label: 'Translate', prompt: 'Translate this to...' },
  ];
</script>

<div class="ai-page">
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-5xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">AI Assistant</h1>
        <div class="flex items-center gap-4">
          <select 
            bind:value={selectedModel}
            class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="gpt-4">GPT-4</option>
            <option value="gpt-3.5">GPT-3.5</option>
            <option value="claude">Claude</option>
            <option value="local">Local Model</option>
          </select>
          <button 
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            onclick={startNewChat}
          >
            + New Chat
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-4">Conversations</h3>
            <div class="space-y-2 max-h-96 overflow-y-auto">
              {#each conversations as conv}
                <button 
                  class="w-full text-left p-3 rounded-lg hover:bg-gray-100 {currentConversation?.id === conv.id ? 'bg-blue-50 border border-blue-200' : ''}"
                  onclick={() => loadConversation(conv)}
                >
                  <p class="font-medium text-sm truncate">{conv.title}</p>
                  <p class="text-xs text-gray-500">{new Date(conv.createdAt).toLocaleDateString()}</p>
                </button>
              {/each}
              
              {#if conversations.length === 0}
                <p class="text-gray-500 text-center py-4 text-sm">No conversations yet</p>
              {/if}
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-4 mt-4">
            <h3 class="font-semibold mb-3">Quick Actions</h3>
            <div class="space-y-2">
              {#each quickActions as action}
                <button 
                  class="w-full text-left p-2 rounded-lg hover:bg-gray-100 text-sm flex items-center gap-2"
                  onclick={() => { input = action.prompt; }}
                >
                  <span>{action.icon}</span>
                  <span>{action.label}</span>
                </button>
              {/each}
            </div>
          </div>
        </div>

        <!-- Main Chat Area -->
        <div class="lg:col-span-3">
          <div class="bg-white rounded-lg shadow flex flex-col h-[600px]">
            {#if !currentConversation}
              <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center text-4xl mb-6">
                  🤖
                </div>
                <h2 class="text-2xl font-bold mb-2">How can I help you today?</h2>
                <p class="text-gray-500 mb-8">Ask me anything or try one of the quick actions</p>
                
                <div class="grid grid-cols-2 gap-4 w-full max-w-md">
                  {#each quickActions.slice(0, 4) as action}
                    <button 
                      class="p-4 bg-gray-50 rounded-lg hover:bg-gray-100 text-left"
                      onclick={() => { input = action.prompt; }}
                    >
                      <span class="text-2xl block mb-2">{action.icon}</span>
                      <span class="text-sm font-medium">{action.label}</span>
                    </button>
                  {/each}
                </div>
              </div>
            {:else}
              <div class="flex-1 overflow-y-auto p-4 space-y-4">
                {#each currentConversation.messages as message}
                  <div class="flex {message.role === 'user' ? 'justify-end' : 'justify-start'}">
                    <div class="max-w-[80%] {message.role === 'user' ? 'bg-blue-600 text-white' : 'bg-gray-100'} rounded-2xl px-4 py-3">
                      <p class="whitespace-pre-wrap">{message.content}</p>
                      <p class="text-xs {message.role === 'user' ? 'text-blue-200' : 'text-gray-500'} mt-1">
                        {formatTime(message.timestamp)}
                      </p>
                    </div>
                  </div>
                {/each}
                
                {#if loading}
                  <div class="flex justify-start">
                    <div class="bg-gray-100 rounded-2xl px-4 py-3 flex items-center gap-2">
                      <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                      <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                      <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                  </div>
                {/if}
              </div>
            {/if}

            <div class="border-t p-4">
              <div class="flex gap-2">
                <button 
                  class="p-2 text-gray-500 hover:text-gray-700 {isRecording ? 'text-red-500' : ''}"
                  onclick={toggleRecording}
                >
                  {isRecording ? '🔴' : '🎤'}
                </button>
                <textarea
                  bind:value={input}
                  onkeydown={handleKeyDown}
                  placeholder="Type your message..."
                  rows="1"
                  class="flex-1 px-4 py-2 border rounded-lg resize-none focus:ring-2 focus:ring-blue-500"
                  style="min-height: 44px; max-height: 120px;"
                ></textarea>
                <button 
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                  onclick={handleSend}
                  disabled={!input.trim() || loading}
                >
                  Send
                </button>
              </div>
              <p class="text-xs text-gray-400 mt-2 text-center">AI may produce inaccurate information. Verify important information.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .ai-page {
    min-height: 100vh;
    background: #f9fafb;
  }
</style>
