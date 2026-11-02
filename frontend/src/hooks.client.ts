import { browser } from '$app/environment';
import { user, isAuthenticated, connectRealtime, disconnectRealtime } from '$lib/stores/global';

if (browser) {
	const token = localStorage.getItem('auth_token');
	const userData = localStorage.getItem('user_data');

	if (token && userData) {
		try {
			const parsedUser = JSON.parse(userData);
			user.set(parsedUser);
			isAuthenticated.set(true);
			validateTokenOnLoad(token);
			connectRealtime();
		} catch {
			console.warn('Invalid stored auth data, clearing...');
			localStorage.removeItem('auth_token');
			localStorage.removeItem('user_data');
		}
	}

	user.subscribe((currentUser) => {
		if (!currentUser) {
			disconnectRealtime();
		}
	});

	window.addEventListener('error', (event: ErrorEvent) => {
		console.error('Global JavaScript error:', event.error);

		if (import.meta.env.PROD) {
			// sendToErrorTracking(event.error, {
			//   url: window.location.href,
			//   userAgent: navigator.userAgent,
			//   timestamp: new Date().toISOString()
			// });
		}

		showGlobalError('An unexpected error occurred. Please refresh the page.');
	});

	window.addEventListener('unhandledrejection', (event: PromiseRejectionEvent) => {
		console.error('Unhandled promise rejection:', event.reason);

		if (import.meta.env.PROD) {
			// sendToErrorTracking(event.reason, {
			//   type: 'promise_rejection',
			//   url: window.location.href
			// });
		}

		showGlobalError('Something went wrong. Please try again.');
	});

	window.addEventListener('online', () => {
		console.log('Connection restored');
		showToast('Connection restored', 'success');
	});

	window.addEventListener('offline', () => {
		console.log('Connection lost');
		showToast('You are offline. Some features may not work.', 'warning');
	});

	window.addEventListener('load', () => {
		if ('performance' in window) {
			const perfData = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming;

			if (perfData) {
				const loadTime = perfData.loadEventEnd - perfData.loadEventEnd;
				console.log(`Page load time: ${loadTime}ms`);

				if (import.meta.env.PROD && loadTime > 3000) {
					// sendPerformanceMetric('slow_page_load', loadTime);
				}
			}

			if ('memory' in performance) {
				const memInfo = (performance as any).memory;
				console.log(`Memory usage: ${(memInfo.usedJSHeapSize / 1024 / 1024).toFixed(2)}MB`);
			}
		}
	});

	document.addEventListener('visibilitychange', () => {
		if (document.hidden) {
			console.log('Page hidden - user switched away');
		} else {
			console.log('Page visible - user returned');
		}
	});

	window.addEventListener('beforeunload', (event: BeforeUnloadEvent) => {
		const hasUnsavedChanges = checkForUnsavedChanges();

		if (hasUnsavedChanges) {
			event.preventDefault();
			event.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
		}
	});
}

async function validateTokenOnLoad(token: string) {
	try {
		const response = await fetch('/api/user/profile', {
			headers: {
				'Authorization': `Bearer ${token}`,
				'Content-Type': 'application/json'
			}
		});

		if (!response.ok) {
			console.warn('Stored token invalid, clearing auth state');
			localStorage.removeItem('auth_token');
			localStorage.removeItem('user_data');
			user.set(null);
			isAuthenticated.set(false);
		}
	} catch (error) {
		console.warn('Token validation failed:', error);
	}
}

function showGlobalError(message: string) {
	console.error('Global Error:', message);

	window.dispatchEvent(new CustomEvent('global-error', {
		detail: { message, timestamp: Date.now() }
	}));
}

function showToast(message: string, type: 'success' | 'error' | 'warning' | 'info' = 'info') {
	console.log(`Toast [${type}]:`, message);

	window.dispatchEvent(new CustomEvent('show-toast', {
		detail: { message, type, timestamp: Date.now() }
	}));
}

function checkForUnsavedChanges(): boolean {
	return false;
}

if (browser) {
	const originalFetch = window.fetch;

	window.fetch = async function(...args: [RequestInfo | URL, RequestInit | undefined]) {
		try {
			const response = await originalFetch.apply(this, args);

			if (response.status === 401) {
				console.warn('401 Unauthorized - redirecting to login');
				localStorage.removeItem('auth_token');
				localStorage.removeItem('user_data');
				user.set(null);
				isAuthenticated.set(false);
				window.location.href = '/auth';
				return response;
			}

			if (response.status === 403) {
				showGlobalError('You do not have permission to perform this action.');
			}

			if (response.status >= 500) {
				showGlobalError('Server error. Please try again later.');
			}

			return response;
		} catch (error) {
			if (!navigator.onLine) {
				showToast('You are offline. Please check your connection.', 'warning');
			} else {
				showGlobalError('Network error. Please check your connection.');
			}

			throw error;
		}
	};
}
