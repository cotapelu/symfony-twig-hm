<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { fade } from 'svelte/transition';
  import Button from '$lib/components/atoms/Button.svelte';

  const dispatch = createEventDispatcher();
  const steps = ['profile', 'interests', 'notifications', 'finish'];
  let current = 0;

  let profile = { name: '', company: '' };
  let interests: string[] = [];
  let notifications = { email: true, push: true };

  export let onComplete: (() => void) | undefined = undefined;

  function next() {
    if (current < steps.length - 1) current += 1;
    else {
      dispatch('complete');
      onComplete?.();
    }
  }
  function back() { if (current > 0) current -= 1; }

  function toggleInterest(interest: string) {
    if (interests.includes(interest)) {
      interests = interests.filter(i => i !== interest);
    } else {
      interests = [...interests, interest];
    }
  }
</script>

<div class="wizard">
  <div class="progress">
    {#each steps as step, i}
      <span class:active={i === current}>{i + 1}</span>
    {/each}
  </div>

  {#if steps[current] === 'profile'}
    <div transition:fade>
      <h2>Your Profile</h2>
      <label>Name <input bind:value={profile.name} /></label>
      <label>Company <input bind:value={profile.company} /></label>
    </div>
  {:else if steps[current] === 'interests'}
    <div transition:fade>
      <h2>Interests</h2>
      <label><input type="checkbox" checked={interests.includes('tech')} on:change={() => toggleInterest('tech')} /> Technology</label>
      <label><input type="checkbox" checked={interests.includes('business')} on:change={() => toggleInterest('business')} /> Business</label>
      <label><input type="checkbox" checked={interests.includes('finance')} on:change={() => toggleInterest('finance')} /> Finance</label>
    </div>
  {:else if steps[current] === 'notifications'}
    <div transition:fade>
      <h2>Notifications</h2>
      <label><input type="checkbox" bind:checked={notifications.email} /> Email updates</label>
      <label><input type="checkbox" bind:checked={notifications.push} /> Push notifications</label>
    </div>
  {:else if steps[current] === 'finish'}
    <div transition:fade>
      <h2>All Set!</h2>
      <p>Thank you for completing the setup.</p>
    </div>
  {/if}

  <div class="actions">
    {#if current > 0 && current < steps.length - 1}<Button variant="secondary" on:click={back}>Back</Button>{/if}
    {#if current < steps.length}<Button on:click={next}>Continue</Button>{/if}
  </div>
</div>

<style>
  .wizard { max-width: 500px; margin: auto; }
  .progress { display: flex; gap: 1rem; margin-bottom: 2rem; }
  .progress span { width: 2rem; height: 2rem; border-radius: 50%; background: #ccc; display: inline-flex; align-items: center; justify-content: center; }
  .progress span.active { background: #007bff; color: white; }
  label { display: block; margin: 1rem 0; }
</style>
