<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import Input from './atoms/Input.svelte';
  import Select from './atoms/Select.svelte';
  import Checkbox from './atoms/Checkbox.svelte';
  import Radio from './atoms/Radio.svelte';
  import Button from './atoms/Button.svelte';

  interface SchemaProperty {
    type: string;
    title?: string;
    format?: string;
    required?: boolean;
    enum?: string[];
    items?: { enum?: string[] };
  }

  interface Schema {
    properties: Record<string, SchemaProperty>;
  }

  export let schema: Schema = { properties: {} };
  export let data: Record<string, any> = {};

  const dispatch = createEventDispatcher<{ submit: { data: Record<string, any> } }>();

  // Render field based on schema property
  function renderField(key: string, property: SchemaProperty): { component: any; props: any } {
    const value = data[key] || '';

    if (property.type === 'string') {
      if (property.format === 'email') {
        return { component: Input, props: { type: 'email', label: property.title || key, value, required: property.required } };
      } else if (property.enum) {
        return { component: Select, props: { label: property.title || key, value, options: property.enum.map((opt: string) => ({ value: opt, label: opt })), required: property.required } };
      } else {
        return { component: Input, props: { label: property.title || key, value, required: property.required } };
      }
    } else if (property.type === 'boolean') {
      return { component: Checkbox, props: { label: property.title || key, checked: value, required: property.required } };
    } else if (property.type === 'number' || property.type === 'integer') {
      return { component: Input, props: { type: 'number', label: property.title || key, value, required: property.required } };
    } else if (property.type === 'array' && property.items && property.items.enum) {
      return { component: Radio, props: { label: property.title || key, value, options: property.items.enum.map((opt: string) => ({ value: opt, label: opt })), required: property.required } };
    }

    // Default to text input
    return { component: Input, props: { label: property.title || key, value, required: property.required } };
  }

  // Handle form submission
  function handleSubmit(e: Event) {
    e.preventDefault();
    dispatch('submit', { data });
  }

  // Update data on input change
  function updateData(key: string, value: any) {
    data = { ...data, [key]: value };
  }
</script>

{#if schema.properties}
  <form onsubmit={handleSubmit} class="space-y-4">
    {#each Object.entries(schema.properties) as [key, property]}
      {@const field = renderField(key, property)}
      <svelte:component
        this={field.component}
        {...field.props}
        oninput={(e: any) => updateData(key, e.target?.value || e.detail?.value)}
      />
    {/each}
    <Button type="submit">Submit</Button>
  </form>
{:else}
  <p>No schema provided.</p>
{/if}