import type { 
  Call, 
  CallHistory, 
  CreateCallRequest, 
  JoinCallRequest, 
  CallSettings, 
  MediaDevice,
  CallQuality 
} from '../contracts/callTypes';

export class CallService {
  private baseUrl = '/api/calls';

  async createCall(request: CreateCallRequest): Promise<Call> {
    const response = await fetch(this.baseUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create call');
    return response.json();
  }

  async joinCall(request: JoinCallRequest): Promise<Call> {
    const response = await fetch(`${this.baseUrl}/${request.callId}/join`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to join call');
    return response.json();
  }

  async leaveCall(callId: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/${callId}/leave`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to leave call');
  }

  async endCall(callId: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/${callId}/end`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to end call');
  }

  async getActiveCalls(): Promise<Call[]> {
    const response = await fetch(`${this.baseUrl}/active`);
    if (!response.ok) throw new Error('Failed to fetch active calls');
    return response.json();
  }

  async getCallHistory(): Promise<CallHistory[]> {
    const response = await fetch(`${this.baseUrl}/history`);
    if (!response.ok) throw new Error('Failed to fetch call history');
    return response.json();
  }

  async getCallById(callId: string): Promise<Call> {
    const response = await fetch(`${this.baseUrl}/${callId}`);
    if (!response.ok) throw new Error('Failed to fetch call');
    return response.json();
  }

  async toggleMute(callId: string, muted: boolean): Promise<void> {
    const response = await fetch(`${this.baseUrl}/${callId}/mute`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ muted })
    });
    if (!response.ok) throw new Error('Failed to toggle mute');
  }

  async toggleVideo(callId: string, enabled: boolean): Promise<void> {
    const response = await fetch(`${this.baseUrl}/${callId}/video`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ enabled })
    });
    if (!response.ok) throw new Error('Failed to toggle video');
  }

  async startScreenShare(callId: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/${callId}/screen-share`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to start screen share');
  }

  async stopScreenShare(callId: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/${callId}/screen-share`, {
      method: 'DELETE'
    });
    if (!response.ok) throw new Error('Failed to stop screen share');
  }

  async startRecording(callId: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/${callId}/recording`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to start recording');
  }

  async stopRecording(callId: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/${callId}/recording`, {
      method: 'DELETE'
    });
    if (!response.ok) throw new Error('Failed to stop recording');
  }

  async getCallSettings(): Promise<CallSettings> {
    const response = await fetch(`${this.baseUrl}/settings`);
    if (!response.ok) throw new Error('Failed to fetch settings');
    return response.json();
  }

  async updateCallSettings(settings: Partial<CallSettings>): Promise<void> {
    const response = await fetch(`${this.baseUrl}/settings`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(settings)
    });
    if (!response.ok) throw new Error('Failed to update settings');
  }

  async getMediaDevices(): Promise<MediaDevice[]> {
    try {
      const devices = await navigator.mediaDevices.enumerateDevices();
      return devices.map(device => ({
        id: device.deviceId,
        label: device.label || `${device.kind} ${device.deviceId.slice(0, 8)}...`,
        kind: device.kind as 'audioinput' | 'audiooutput' | 'videoinput'
      }));
    } catch (error) {
      throw new Error('Failed to enumerate media devices');
    }
  }

  async getCallQuality(callId: string): Promise<CallQuality> {
    const response = await fetch(`${this.baseUrl}/${callId}/quality`);
    if (!response.ok) throw new Error('Failed to fetch call quality');
    return response.json();
  }

  async inviteParticipant(callId: string, userId: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/${callId}/invite`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId })
    });
    if (!response.ok) throw new Error('Failed to invite participant');
  }

  async removeParticipant(callId: string, userId: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/${callId}/participants/${userId}`, {
      method: 'DELETE'
    });
    if (!response.ok) throw new Error('Failed to remove participant');
  }

  subscribeToCallEvents(callId: string, callback: (event: any) => void): () => void {
    const ws = new WebSocket(`wss://${window.location.host}/ws/calls/${callId}`);
    
    ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      callback(data);
    };

    ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };

    return () => ws.close();
  }

  subscribeToIncomingCalls(callback: (call: Call) => void): () => void {
    const ws = new WebSocket(`wss://${window.location.host}/ws/calls/incoming`);
    
    ws.onmessage = (event) => {
      const call = JSON.parse(event.data);
      callback(call);
    };

    ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };

    return () => ws.close();
  }
}