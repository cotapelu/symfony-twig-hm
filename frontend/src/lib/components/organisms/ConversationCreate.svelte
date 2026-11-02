<script lang="ts">
  import { Modal } from '$lib/components';

  export let showModal = false;

  interface User {
    id: string;
    name: string;
    email: string;
  }

  let title = '';
  let description = '';
  let isPublic = false;
  let selectedParticipants: User[] = [];
  let searchTerm = '';
  let availableUsers: User[] = []; // Mock data - in real app, fetch from API
  let loading = false;
  let error = '';

  // Mock available users for selection
  $: if (showModal && availableUsers.length === 0) {
    loadAvailableUsers();
  }

  async function loadAvailableUsers() {
    // Mock API call - in real app, fetch users from backend
    availableUsers = [
      { id: 'user1', name: 'Alice Johnson', email: 'alice@example.com' },
      { id: 'user2', name: 'Bob Smith', email: 'bob@example.com' },
      { id: 'user3', name: 'Carol Williams', email: 'carol@example.com' },
      { id: 'user4', name: 'David Brown', email: 'david@example.com' },
      { id: 'user5', name: 'Emma Davis', email: 'emma@example.com' },
    ];
  }

  $: filteredUsers = availableUsers.filter(user =>
    user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    user.email.toLowerCase().includes(searchTerm.toLowerCase())
  );

  function toggleParticipant(user: User) {
    const index = selectedParticipants.findIndex(p => p.id === user.id);
    if (index > -1) {
      selectedParticipants = selectedParticipants.filter(p => p.id !== user.id);
    } else {
      selectedParticipants = [...selectedParticipants, user];
    }
  }

  function removeParticipant(userId: string) {
    selectedParticipants = selectedParticipants.filter(p => p.id !== userId);
  }

  async function createConversation(event: Event) {
    event.preventDefault();
    // Validation
    if (!title.trim()) {
      error = 'Conversation title is required';
      return;
    }

    if (!isPublic && selectedParticipants.length === 0) {
      error = 'At least one participant is required for private conversations';
      return;
    }

    loading = true;
    error = '';

    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch('/api/conversations', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': token ? `Bearer ${token}` : ''
        },
        body: JSON.stringify({
          title: title.trim(),
          description: description.trim(),
          isPublic,
          participantIds: selectedParticipants.map(p => p.id)
        })
      });

      if (response.ok) {
        const data = await response.json();
        // Dispatch success event
        const event = new CustomEvent('conversationCreated', { detail: data });
        window.dispatchEvent(event);

        // Reset form and close modal
        resetForm();
        showModal = false;
      } else {
        const errorData = await response.json();
        error = errorData.message || 'Failed to create conversation';
      }
    } catch {
      error = 'Network error. Please try again.';
    } finally {
      loading = false;
    }
  }

  function resetForm() {
    title = '';
    description = '';
    isPublic = false;
    selectedParticipants = [];
    searchTerm = '';
    error = '';
  }

  function closeModal() {
    resetForm();
    showModal = false;
  }
</script>

<Modal open={showModal} title="Create New Conversation" on:close={closeModal}>
  <div class="modal-body">
        {#if error}
          <div class="error-message">
            {error}
          </div>
        {/if}

        <form onsubmit={createConversation}>
          <!-- Title -->
          <div class="form-group">
            <label for="conv-title">Conversation Title *</label>
            <input
              type="text"
              id="conv-title"
              bind:value={title}
              placeholder="Enter conversation title"
              required
              maxlength="100"
            />
          </div>

          <!-- Description -->
          <div class="form-group">
            <label for="conv-description">Description</label>
            <textarea
              id="conv-description"
              bind:value={description}
              placeholder="Optional description..."
              rows="3"
              maxlength="500"
            ></textarea>
          </div>

          <!-- Public/Private Toggle -->
          <div class="form-group">
            <label class="toggle-label">
              <input type="checkbox" bind:checked={isPublic}>
              <span class="toggle-slider"></span>
              Public Conversation
            </label>
            <p class="toggle-description">
              {#if isPublic}
                Anyone can join this conversation
              {:else}
                Only invited participants can join
              {/if}
            </p>
          </div>

          <!-- Participant Selection (only for private) -->
          {#if !isPublic}
            <div class="form-group">
              <h3>Participants ({selectedParticipants.length} selected)</h3>

              <!-- Selected Participants -->
              {#if selectedParticipants.length > 0}
                <div class="selected-participants">
                  {#each selectedParticipants as participant}
                    <div class="participant-tag">
                      {participant.name}
                      <button
                        type="button"
                        onclick={() => removeParticipant(participant.id)}
                        aria-label="Remove {participant.name}"
                      >×</button>
                    </div>
                  {/each}
                </div>
              {/if}

              <!-- Search and Add Participants -->
              <div class="participant-search">
                <input
                  type="text"
                  bind:value={searchTerm}
                  placeholder="Search users to add..."
                  autocomplete="off"
                />

                {#if searchTerm && filteredUsers.length > 0}
                  <div class="user-list">
                    {#each filteredUsers.filter(u => !selectedParticipants.some(p => p.id === u.id)) as user}
                      <div
                        class="user-item"
                        onclick={() => toggleParticipant(user)}
                        role="button"
                        tabindex="0"
                        onkeydown={(e) => { if (e.key === 'Enter') toggleParticipant(user); }}
                      >
                        <div class="user-avatar">{user.name.charAt(0)}</div>
                        <div class="user-info">
                          <div class="user-name">{user.name}</div>
                          <div class="user-email">{user.email}</div>
                        </div>
                        <div class="user-add">➕</div>
                      </div>
                    {/each}
                  </div>
                {/if}
              </div>
            </div>
          {/if}

          <!-- Actions -->
          <div class="modal-actions">
            <button type="button" class="cancel-btn" onclick={closeModal} disabled={loading}>
              Cancel
            </button>
            <button type="submit" class="create-btn" disabled={loading}>
              {#if loading}
                Creating...
              {:else}
                Create Conversation
              {/if}
            </button>
          </div>
        </form>
      </div>
</Modal>

<style>
  .modal-body {
    padding: 1.5rem;
  }

  .error-message {
    background: rgba(255, 107, 107, 0.1);
    color: #ff6b6b;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #ff6b6b;
    margin-bottom: 1rem;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #ffffff;
    font-weight: 500;
  }

  input[type="text"],
  textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #00ffff;
    border-radius: 8px;
    background: rgba(20, 20, 20, 0.8);
    color: #00ffff;
    font-family: 'Courier New', monospace;
    transition: all 0.3s ease;
  }

  input[type="text"]:focus,
  textarea:focus {
    outline: none;
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
    border-color: #ff00ff;
  }

  textarea {
    resize: vertical;
    min-height: 80px;
  }

  .toggle-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    user-select: none;
  }

  .toggle-label input[type="checkbox"] {
    display: none;
  }

  .toggle-slider {
    width: 50px;
    height: 24px;
    background: rgba(100, 100, 100, 0.5);
    border-radius: 12px;
    position: relative;
    transition: background 0.3s ease;
  }

  .toggle-slider::before {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: #ffffff;
    border-radius: 50%;
    transition: transform 0.3s ease;
  }

  .toggle-label input:checked + .toggle-slider {
    background: linear-gradient(45deg, #00ffff, #ff00ff);
  }

  .toggle-label input:checked + .toggle-slider::before {
    transform: translateX(26px);
  }

  .toggle-description {
    margin: 0.5rem 0 0 0;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.7);
  }

  .selected-participants {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .participant-tag {
    background: rgba(0, 255, 255, 0.1);
    border: 1px solid #00ffff;
    border-radius: 20px;
    padding: 0.25rem 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #00ffff;
  }

  .participant-tag button {
    background: none;
    border: none;
    color: #ff00ff;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0;
    width: 1rem;
    height: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .participant-search {
    position: relative;
  }

  .participant-search input {
    margin-bottom: 0.5rem;
  }

  .user-list {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid rgba(0, 255, 255, 0.3);
    border-radius: 8px;
    background: rgba(20, 20, 20, 0.9);
  }

  .user-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    cursor: pointer;
    transition: background 0.2s ease;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .user-item:hover,
  .user-item:focus {
    background: rgba(0, 255, 255, 0.1);
    outline: 2px solid #00ffff;
    outline-offset: -2px;
  }

  .user-item:last-child {
    border-bottom: none;
  }

  .user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(45deg, #00ffff, #ff00ff);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #000000;
    font-weight: bold;
    font-size: 1.2rem;
  }

  .user-info {
    flex: 1;
  }

  .user-name {
    font-weight: 500;
    color: #ffffff;
  }

  .user-email {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.7);
  }

  .user-add {
    color: #00ffff;
    font-size: 1.2rem;
  }

  .modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(0, 255, 255, 0.2);
  }

  .cancel-btn {
    background: rgba(255, 0, 255, 0.1);
    color: #ff00ff;
    border: 1px solid #ff00ff;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    transition: all 0.3s ease;
  }

  .cancel-btn:hover {
    background: rgba(255, 0, 255, 0.2);
    box-shadow: 0 0 10px rgba(255, 0, 255, 0.5);
  }

  .create-btn {
    background: linear-gradient(45deg, #00ffff, #ff00ff);
    color: #000000;
    border: 1px solid #00ffff;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    transition: all 0.3s ease;
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
  }

  .create-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 20px rgba(0, 255, 255, 0.8);
  }

  .create-btn:disabled,
  .cancel-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .modal-actions {
      flex-direction: column;
    }

    .cancel-btn,
    .create-btn {
      width: 100%;
    }
  }
</style>