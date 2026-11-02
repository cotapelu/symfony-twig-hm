<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import Button from './atoms/Button.svelte';
  import Icon from './atoms/Icon.svelte';

  interface FieldGroup {
    id: string;
    title: string;
    fields: any[];
    collapsed?: boolean;
  }

  export let groups: FieldGroup[] = [];
  export let defaultCollapsed: boolean = false;

  const dispatch = createEventDispatcher<{ groupToggle: { id: string; collapsed: boolean } }>();

  // Initialize collapsed state
  $: groupStates = groups.reduce((acc, group) => {
    acc[group.id] = group.collapsed ?? defaultCollapsed;
    return acc;
  }, {} as Record<string, boolean>);

  function toggleGroup(id: string) {
    groupStates[id] = !groupStates[id];
    groupStates = { ...groupStates }; // trigger reactivity
    dispatch('groupToggle', { id, collapsed: groupStates[id] });
  }
</script>

<div class="field-groups space-y-4">
  {#each groups as group}
    <div class="field-group border rounded-lg overflow-hidden">
      <button
        class="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 transition-colors"
        onclick={() => toggleGroup(group.id)}
        aria-expanded={!groupStates[group.id]}
      >
        <h3 class="text-lg font-semibold">{group.title}</h3>
        <Icon name={groupStates[group.id] ? 'chevron-down' : 'chevron-up'} />
      </button>

      {#if !groupStates[group.id]}
        <div class="p-4 space-y-4">
          {#each group.fields as field}
            <svelte:component this={field.component} {...field.props} />
          {/each}
        </div>
      {/if}
    </div>
  {/each}
</div>