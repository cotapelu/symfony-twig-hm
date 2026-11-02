<script lang="ts">
  import { enhance } from '$app/forms';
  import Input from './atoms/Input.svelte';
  import Textarea from './Textarea.svelte';
  import Button from './atoms/Button.svelte';

  function handleSubmit() {
  }
</script>

<form method="POST" action="/api/contact" use:enhance={handleSubmit}>
  <Input name="email" type="email" required label="Email" />
  <Textarea name="message" required label="Message" />
  <Button type="submit">Send</Button>
</form>