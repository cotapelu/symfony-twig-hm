import { browser } from '$app/environment';

declare global {
  interface Window {
    intercomSettings?: { app_id: string; [key: string]: unknown };
    Intercom?: IntercomCommand;
  }
}

/**
 * Intercom boot API before the widget script loads: a callable command queue
 * with the official snippet's `.q` (queued calls) and `.c` (recorder) members.
 */
type IntercomCommand = ((...args: unknown[]) => void) & {
  q: unknown[][];
  c: (args: unknown[]) => void;
};

export function initIntercom(appId: string) {
  if (!browser) return;
  window.intercomSettings = { app_id: appId };

  const w = window;
  if (typeof w.Intercom === 'function') {
    w.Intercom('reattach_activator');
    w.Intercom('update', w.intercomSettings);
    return;
  }

  const queue: unknown[][] = [];
  const record = (args: unknown[]): void => { queue.push(args); };
  const command = ((...args: unknown[]) => record(args)) as IntercomCommand;
  command.q = queue;
  command.c = record;
  w.Intercom = command;

  const load = (): void => {
    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.async = true;
    script.src = `https://widget.intercom.io/widget/${appId}`;
    const anchor = document.getElementsByTagName('script')[0];
    anchor.parentNode?.insertBefore(script, anchor);
  };

  if (document.readyState === 'complete') load();
  else w.addEventListener('load', load, false);
}

export function showMessenger() {
  if (window.Intercom) {
    window.Intercom('show');
  }
}

export function hideMessenger() {
  if (window.Intercom) {
    window.Intercom('hide');
  }
}

export function updateUser(user: { id: string; email?: string; name?: string; created_at?: number }) {
  if (window.Intercom) {
    window.Intercom('update', user);
  }
}

export function logEvent(eventName: string, metadata?: Record<string, any>) {
  if (window.Intercom) {
    window.Intercom('trackEvent', eventName, metadata);
  }
}
