import type { Call, CallRequest, CallsState } from '../contracts/callTypes';

const API_BASE = '/api/calls';

export class CallService {
  async getCalls(): Promise<Call[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch calls');
    return response.json();
  }

  async initiateCall(request: CallRequest): Promise<Call> {
    const response = await fetch(`${API_BASE}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to initiate call');
    return response.json();
  }

  async endCall(id: string): Promise<Call> {
    const response = await fetch(`${API_BASE}/${id}/end`, { method: 'PUT' });
    if (!response.ok) throw new Error('Failed to end call');
    return response.json();
  }

  async answerCall(id: string): Promise<Call> {
    const response = await fetch(`${API_BASE}/${id}/answer`, { method: 'PUT' });
    if (!response.ok) throw new Error('Failed to answer call');
    return response.json();
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};

export const callService = new CallService();
export default callService;
