<script lang="ts">
  import { groupsStore } from '$lib/../stores';
  import { onMount } from 'svelte';

  let showCreateModal = false;
  let newGroupName = '';
  let newGroupDescription = '';

  onMount(() => {
    groupsStore.loadGroups();
  });

  async function createGroup() {
    if (!newGroupName.trim()) return;
    
    try {
      await groupsStore.createGroup(newGroupName, newGroupDescription);
      showCreateModal = false;
      newGroupName = '';
      newGroupDescription = '';
    } catch (error) {
      console.error('Failed to create group:', error);
    }
  }

  async function joinGroup(groupId) {
    try {
      await groupsStore.joinGroup(groupId);
    } catch (error) {
      console.error('Failed to join group:', error);
    }
  }

  async function leaveGroup(groupId) {
    try {
      await groupsStore.leaveGroup(groupId);
    } catch (error) {
      console.error('Failed to leave group:', error);
    }
  }
</script>

<div class="groups-page">
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Groups</h1>
        <button 
          class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
          onclick={() => showCreateModal = true}
        >
          Create Group
        </button>
      </div>

      {#if $groupsStore.loading}
        <div class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      {:else}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {#each $groupsStore.groups as group}
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
              <h3 class="text-xl font-semibold mb-2">{group.title}</h3>
              <p class="text-gray-600 mb-4">{group.description || 'No description'}</p>
              
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 text-sm text-gray-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8 0 4 4 0 000-8zm0 0a4 4 0 110 8 0 4 4 0 000-8z" />
                  </svg>
                  <span>{group.participantCount || 0} members</span>
                </div>
                
                <div class="flex space-x-2">
                  <button 
                    class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-sm"
                    onclick={() => joinGroup(group.id)}
                  >
                    Join
                  </button>
                  <button 
                    class="px-3 py-1 border rounded hover:bg-gray-50 text-sm"
                    onclick={() => leaveGroup(group.id)}
                  >
                    Leave
                  </button>
                </div>
              </div>
            </div>
          {/each}
          
          {#if $groupsStore.groups.length === 0}
            <div class="col-span-full text-center py-12 text-gray-600">
              No groups yet
            </div>
          {/if}
        </div>
      {/if}
    </div>
  </div>
</div>

{#if showCreateModal}
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
      <h2 class="text-xl font-bold mb-4">Create New Group</h2>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Group Name</label>
          <input 
            type="text"
            bind:value={newGroupName}
            placeholder="Enter group name"
            class="w-full px-4 py-2 border rounded focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
          <textarea 
            bind:value={newGroupDescription}
            placeholder="Enter group description"
            class="w-full px-4 py-2 border rounded focus:ring-2 focus:ring-blue-500"
            rows="3"
          ></textarea>
        </div>
        
        <div class="flex justify-end space-x-3">
          <button 
            class="px-4 py-2 border rounded hover:bg-gray-50"
            onclick={() => showCreateModal = false}
          >
            Cancel
          </button>
          <button 
            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
            disabled={!newGroupName.trim()}
            onclick={createGroup}
          >
            Create
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}

<style>
  .groups-page {
    min-height: 100vh;
  }
</style>
