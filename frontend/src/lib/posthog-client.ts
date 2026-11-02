import { browser } from '$app/environment';

export function initPosthog() {
  if (!browser) return;
  // @ts-ignore
  window.posthog = window.posthog || [];
  // @ts-ignore
  window.posthog.init('phc_project_token', {
    api_host: 'https://us.i.posthog.com',
    person_profiles: 'identified_only' // respect privacy
  });
}

export function identify(userId: string, traits?: Record<string, any>) {
  if (!browser) return;
  // @ts-ignore
  const ph = window.posthog;
  if (!ph) return;
  ph.identify(userId, traits);
}

export function capture(event: string, properties?: Record<string, any>) {
  if (!browser) return;
  // @ts-ignore
  const ph = window.posthog;
  if (!ph) return;
  ph.capture(event, properties);
}

export function screen(name: string, properties?: Record<string, any>) {
  if (!browser) return;
  // @ts-ignore
  const ph = window.posthog;
  if (!ph) return;
  ph.screen(name, properties);
}

export function reset() {
  if (!browser) return;
  // @ts-ignore
  const ph = window.posthog;
  if (!ph) return;
  ph.reset();
}
