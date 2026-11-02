// Elixir Stores - TypeScript Version
// Events, Wallet - Distributed Systems

import { writable } from 'svelte/store';
import { Elixir } from '../lib/api';

interface ElixirEventsState {
	events: any[];
	currentEvent: any | null;
	calendarEvents: Record<string, any>;
	loading: boolean;
	error: string | null;
}

function createElixirEventsStore() {
	const initialState: ElixirEventsState = {
		events: [],
		currentEvent: null,
		calendarEvents: {},
		loading: false,
		error: null,
	};

	const { subscribe, set, update } = writable<ElixirEventsState>(initialState);

	return {
		subscribe,
		
		createEvent: async (data: any): Promise<any> => {
			update(state => ({ ...state, loading: true, error: null }));
			try {
				const event = await Elixir.createEvent(data);
				update(prev => ({
					...prev,
					events: [event, ...prev.events],
					loading: false,
				}));
				return event;
			} catch (error) {
				const errorMessage = error instanceof Error ? error.message : 'Failed to create event';
				update(state => ({ ...state, loading: false, error: errorMessage }));
				throw error;
			}
		},
		
		getEvents: async (userId: string): Promise<any[]> => {
			update(state => ({ ...state, loading: true, error: null }));
			try {
				const events = await Elixir.getEvents(userId);
				update(prev => ({
					...prev,
					events,
					loading: false,
				}));
				return events;
			} catch (error) {
				const errorMessage = error instanceof Error ? error.message : 'Failed to get events';
				update(state => ({ ...state, loading: false, error: errorMessage }));
				throw error;
			}
		},
		
		getEvent: async (eventId: string): Promise<any> => {
			update(state => ({ ...state, loading: true, error: null }));
			try {
				const event = await Elixir.getEvent(eventId);
				update(prev => ({
					...prev,
					currentEvent: event,
					loading: false,
				}));
				return event;
			} catch (error) {
				const errorMessage = error instanceof Error ? error.message : 'Failed to get event';
				update(state => ({ ...state, loading: false, error: errorMessage }));
				throw error;
			}
		},
		
		rsvpEvent: async (eventId: string, status = 'going'): Promise<any> => {
			try {
				const result = await Elixir.rsvpEvent(eventId, status);
				update(prev => ({
					...prev,
					events: prev.events.map(e =>
						e.id === eventId ? { ...e, rsvpStatus: status } : e
					),
				}));
				return result;
			} catch (error) {
				const errorMessage = error instanceof Error ? error.message : 'Failed to RSVP';
				update(state => ({ ...state, error: errorMessage }));
				throw error;
			}
		},
		
		getCalendarIntegration: async (provider: string): Promise<any> => {
			update(state => ({ ...state, loading: true, error: null }));
			try {
				const calendar = await Elixir.getCalendarIntegration(provider);
				update(prev => ({
					...prev,
					calendarEvents: { ...prev.calendarEvents, [provider]: calendar },
					loading: false,
				}));
				return calendar;
			} catch (error) {
				const errorMessage = error instanceof Error ? error.message : 'Failed to get calendar integration';
				update(state => ({ ...state, loading: false, error: errorMessage }));
				throw error;
			}
		},
		
		clearError: (): void => {
			update(state => ({ ...state, error: null }));
		},
		// Test-only reset
		reset: (): void => {
			set({
				events: [],
				currentEvent: null,
				calendarEvents: {},
				loading: false,
				error: null,
			});
		},
	};
}

interface ElixirWalletState {
	wallets: Record<string, { balance: number }>;
	transactions: Record<string, any[]>;
	loading: boolean;
	error: string | null;
}

function createElixirWalletStore() {
	const initialState: ElixirWalletState = {
		wallets: {},
		transactions: {},
		loading: false,
		error: null,
	};

	const { subscribe, set, update } = writable<ElixirWalletState>(initialState);

	return {
		subscribe,
		
		getBalance: async (walletId: string): Promise<{ balance: number }> => {
			update(state => ({ ...state, loading: true, error: null }));
			try {
				const balance = await Elixir.getBalance(walletId);
				update(prev => ({
					...prev,
					wallets: { ...prev.wallets, [walletId]: { ...prev.wallets[walletId], ...balance } },
					loading: false,
				}));
				return balance;
			} catch (error) {
				const errorMessage = error instanceof Error ? error.message : 'Failed to get balance';
				update(state => ({ ...state, loading: false, error: errorMessage }));
				throw error;
			}
		},
		
		transfer: async (fromWalletId: string, toWalletId: string, amount: number): Promise<any> => {
			update(state => ({ ...state, loading: true, error: null }));
			try {
				const transaction = await Elixir.transfer(fromWalletId, toWalletId, amount);
				update(prev => ({
					...prev,
					transactions: {
						...prev.transactions,
						[transaction.id]: transaction,
					},
					loading: false,
				}));
				return transaction;
			} catch (error) {
				const errorMessage = error instanceof Error ? error.message : 'Failed to transfer';
				update(state => ({ ...state, loading: false, error: errorMessage }));
				throw error;
			}
		},
		
		getTransactionHistory: async (walletId: string): Promise<any[]> => {
			update(state => ({ ...state, loading: true, error: null }));
			try {
				const transactions = await Elixir.getTransactionHistory(walletId);
				update(prev => ({
					...prev,
					transactions: {
						...prev.transactions,
						[walletId]: transactions,
					},
					loading: false,
				}));
				return transactions;
			} catch (error) {
				const errorMessage = error instanceof Error ? error.message : 'Failed to get transaction history';
				update(state => ({ ...state, loading: false, error: errorMessage }));
				throw error;
			}
		},
		
		createTransaction: async (data: any): Promise<any> => {
			update(state => ({ ...state, loading: true, error: null }));
			try {
				const transaction = await Elixir.createTransaction(data);
				update(prev => ({
					...prev,
					transactions: {
						...prev.transactions,
						[transaction.id]: transaction,
					},
					loading: false,
				}));
				return transaction;
			} catch (error) {
				const errorMessage = error instanceof Error ? error.message : 'Failed to create transaction';
				update(state => ({ ...state, loading: false, error: errorMessage }));
				throw error;
			}
		},
		
		clearError: (): void => {
			update(state => ({ ...state, error: null }));
		},
		// Test-only reset
		reset: (): void => {
			set({
				wallets: {},
				transactions: {},
				loading: false,
				error: null,
			});
		},
	};
}

export const elixirEventsStore = createElixirEventsStore();
export const elixirWalletStore = createElixirWalletStore();

export const {
	createEvent,
	getEvents,
	getEvent,
	rsvpEvent,
	getCalendarIntegration,
	clearError: clearEventsError,
} = elixirEventsStore;

export const {
	getBalance,
	transfer,
	getTransactionHistory,
	createTransaction,
	clearError: clearWalletError,
} = elixirWalletStore;
