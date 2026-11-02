import { browser } from '$app/environment';
import { getContext } from 'svelte';

declare global {
  interface Window {
    intercomSettings?: { app_id: string; [key: string]: unknown };
    Intercom?: (...args: unknown[]) => void;
  }
}

export function initIntercom(appId: string) {
  if (!browser) return;
  window.intercomSettings = { app_id: appId };
  (function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments)};i.q=[];i.c=function(args){i.q.push(args)};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/'+appId;var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(document.readyState==='complete'){l();}else{w.addEventList ser('load',l,false);}}})();
}

export function show Messenger() {
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
