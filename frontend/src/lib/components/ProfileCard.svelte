<script lang="ts">
  interface User {
    name?: string;
    email?: string;
    bio?: string;
    avatar?: string;
    created_at?: string;
    createdAt?: string;
  }

  export let user: User | null = null;
  export let onEdit: ((user: User) => void) | null = null;

  function handleEdit() {
    if (user && onEdit) {
      onEdit(user);
    }
  }
</script>

{#if user}
  <div class="profile-card" role="article" aria-labelledby="profile-name">
    <div class="profile-header">
      <h2 id="profile-name">{user.name}</h2>
      <button onclick={handleEdit} class="edit-btn" aria-label="Edit profile">✏️</button>
    </div>
    <div class="profile-details">
      <div class="profile-field">
        <label>Email:</label>
        <span>{user.email}</span>
      </div>
      <div class="profile-field">
        <label>Bio:</label>
        <span>{user.bio || 'No bio available'}</span>
      </div>
      <div class="profile-field">
        <label>Member since:</label>
        <span>{new Date(user.created_at).toLocaleDateString()}</span>
      </div>
    </div>
  </div>
{/if}

<style>
  .profile-card {
    background: rgba(15, 15, 15, 0.9);
    padding: 2rem;
    border-radius: 15px;
    border: 1px solid #00ffff;
    box-shadow:
      0 0 20px rgba(0, 255, 255, 0.4),
      inset 0 0 20px rgba(0, 255, 255, 0.1);
    position: relative;
  }

  .profile-card::before {
    content: 'USER_PROFILE.exe';
    position: absolute;
    top: 10px;
    left: 15px;
    color: #00ffff;
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    text-shadow: 0 0 5px #00ffff;
  }

  .profile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
  }

  .profile-header h2 {
    color: #00ffff;
    margin: 0;
    font-size: 1.8rem;
    text-shadow: 0 0 10px #00ffff;
  }

  .edit-btn {
    background: rgba(0, 255, 255, 0.1);
    border: 1px solid #00ffff;
    border-radius: 8px;
    padding: 0.5rem;
    cursor: pointer;
    color: #00ffff;
    transition: all 0.3s ease;
    font-size: 1.2rem;
  }

  .edit-btn:hover {
    background: rgba(0, 255, 255, 0.2);
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
  }

  .profile-details {
    display: grid;
    gap: 1.5rem;
  }

  .profile-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .profile-field label {
    color: #ff00ff;
    font-weight: bold;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  .profile-field span {
    color: #ffffff;
    background: rgba(0, 255, 255, 0.1);
    padding: 0.75rem;
    border-radius: 8px;
    border: 1px solid rgba(0, 255, 255, 0.3);
    font-size: 1rem;
  }
</style>