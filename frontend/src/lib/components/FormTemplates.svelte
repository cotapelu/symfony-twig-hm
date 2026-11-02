<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import Button from './atoms/Button.svelte';
  import Card from './molecules/Card.svelte';
  import Input from './atoms/Input.svelte';
  import Textarea from './Textarea.svelte';
  import Checkbox from './atoms/Checkbox.svelte';

  interface FormTemplate {
    id: string;
    name: string;
    description: string;
    fields: any[];
    layout?: string;
  }

  export let templates: FormTemplate[] = [
    {
      id: 'contact',
      name: 'Contact Form',
      description: 'Basic contact form with name, email, message',
      fields: [
        { component: Input, props: { label: 'Name', type: 'text', required: true } },
        { component: Input, props: { label: 'Email', type: 'email', required: true } },
        { component: Textarea, props: { label: 'Message', required: true } },
      ]
    },
    {
      id: 'registration',
      name: 'User Registration',
      description: 'Registration form with username, email, password',
      fields: [
        { component: Input, props: { label: 'Username', type: 'text', required: true } },
        { component: Input, props: { label: 'Email', type: 'email', required: true } },
        { component: Input, props: { label: 'Password', type: 'password', required: true } },
        { component: Checkbox, props: { label: 'Agree to terms', required: true } },
      ]
    },
    {
      id: 'survey',
      name: 'Simple Survey',
      description: 'Survey form with questions',
      fields: [
        { component: Input, props: { label: 'What is your name?', type: 'text', required: true } },
        { component: Input, props: { label: 'How old are you?', type: 'number', required: true } },
        { component: Textarea, props: { label: 'Any comments?', required: false } },
      ]
    }
  ];

  const dispatch = createEventDispatcher<{ selected: FormTemplate }>();

  let selectedTemplate: FormTemplate | null = null;

  function selectTemplate(template: FormTemplate) {
    selectedTemplate = template;
    dispatch('selected', template);
  }
</script>

<div class="form-templates">
  {#if !selectedTemplate}
    <h2 class="text-xl font-bold mb-4">Choose a Form Template</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {#each templates as template}
        <div
          class="cursor-pointer hover:shadow-lg transition-shadow"
          role="button"
          tabindex="0"
          onclick={() => selectTemplate(template)}
          onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { selectTemplate(template); e.preventDefault(); } }}
        >
          <Card>
          <h3 class="text-lg font-semibold mb-2">{template.name}</h3>
          <p class="text-sm text-gray-600 mb-4">{template.description}</p>
          <Button variant="secondary" size="sm">Use This Template</Button>
        </Card>
        </div>
      {/each}
    </div>
  {:else}
    <div class="mb-4">
      <Button variant="secondary" onclick={() => selectedTemplate = null}>Back to Templates</Button>
    </div>
    <Card>
      <h3 class="text-lg font-semibold mb-4">{selectedTemplate.name}</h3>
      <form class="space-y-4">
        {#each selectedTemplate.fields as field}
          <svelte:component this={field.component} {...field.props} />
        {/each}
        <Button type="submit">Submit</Button>
      </form>
    </Card>
  {/if}
</div>