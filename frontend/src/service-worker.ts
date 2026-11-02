/// <reference types="@sveltejs/kit" />
/// <reference lib="webworker" />

import { build, files, version } from '$service-worker';

declare const self: ServiceWorkerGlobalScope;
declare const clients: Clients;

export const CACHE = `cache-${version}`;
export const ASSETS = [
	...build,
	...files
] as string[];

self.addEventListener('install', (event: ExtendableEvent) => {
	async function addFilesToCache() {
		const cache = await caches.open(CACHE);
		await cache.addAll(ASSETS);
	}

	event.waitUntil(addFilesToCache());
});

self.addEventListener('activate', (event: ExtendableEvent) => {
	async function deleteOldCaches() {
		for (const key of await caches.keys()) {
			if (key !== CACHE) {
				await caches.delete(key);
			}
		}
	}

	event.waitUntil(deleteOldCaches());
});

self.addEventListener('message', (event: ExtendableMessageEvent) => {
	if (event.data && event.data.type === 'SKIP_WAITING') {
		self.skipWaiting();
	}
});

self.addEventListener('fetch', (event: FetchEvent) => {
	if (event.request.method !== 'GET') return;

	async function respond(): Promise<Response> {
		const url = new URL(event.request.url);
		const cache = await caches.open(CACHE);

		if (ASSETS.includes(url.pathname)) {
			const response = await cache.match(url.pathname);
			if (response) return response;
		}

		try {
			const response = await fetch(event.request);

			if (response.status === 200) {
				cache.put(event.request, response.clone());
			}

			return response;
		} catch {
			const response = await cache.match(event.request);
			if (response) return response;

			return cache.match('/offline') as Promise<Response>;
		}
	}

	event.respondWith(respond());
});

interface PushNotificationData {
	title?: string;
	body?: string;
	url?: string;
	messageId?: string;
	conversationId?: string;
}

interface NotificationAction {
	action: string;
	title: string;
}

self.addEventListener('push', (event: PushEvent) => {
	const data: PushNotificationData = event.data ? event.data.json() : {};

	const options: NotificationOptions & { actions?: NotificationAction[] } = {
		body: data.body || 'You have a new message',
		icon: '/favicon.ico',
		badge: '/favicon.ico',
		data: {
			url: data.url || '/messages',
			messageId: data.messageId,
			conversationId: data.conversationId
		},
		actions: [
			{
				action: 'view',
				title: 'View Message'
			},
			{
				action: 'reply',
				title: 'Reply'
			}
		],
		requireInteraction: true,
		silent: false
	};

	event.waitUntil(
		self.registration.showNotification(data.title || 'New Message', options)
	);
});

self.addEventListener('notificationclick', (event: NotificationEvent) => {
	event.notification.close();

	const data = event.notification.data as { url?: string; conversationId?: string };
	let url = data.url || '/messages';

	if (event.action === 'view') {
		url = data.url || '/messages';
	} else if (event.action === 'reply') {
		url = `/messages?conversationId=${data.conversationId}`;
	}

	event.waitUntil(
		clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients: readonly WindowClient[]): WindowClient | Promise<WindowClient | null> | null => {
			for (const client of windowClients) {
				if (client.url === url && 'focus' in client) {
					return client.focus();
				}
			}
			if (clients.openWindow) {
				return clients.openWindow(url);
			}
			return null;
		})
	);
});

self.addEventListener('sync' as any, ((event: Event) => {
	const syncEvent = event as ExtendableEvent & { tag: string };
	if (syncEvent.tag === 'background-sync-messages') {
		syncEvent.waitUntil(syncMessages());
	}
}) as EventListener);

interface PendingMessage {
	id: string;
	token: string;
	conversationId: string;
	content: string;
	attachments?: string[];
}

async function syncMessages(): Promise<void> {
	try {
		const pendingMessages = await getPendingMessages();

		for (const message of pendingMessages) {
			try {
				const response = await fetch('/api/messages', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'Authorization': `Bearer ${message.token}`
					},
					body: JSON.stringify({
						conversationId: message.conversationId,
						content: message.content,
						attachments: message.attachments
					})
				});

				if (response.ok) {
					await removePendingMessage(message.id);
				}
			} catch (error) {
				console.error('Failed to sync message:', error);
			}
		}
	} catch (error) {
		console.error('Background sync failed:', error);
	}
}

async function getPendingMessages(): Promise<PendingMessage[]> {
	return [];
}

async function removePendingMessage(_id: string): Promise<void> {
}
