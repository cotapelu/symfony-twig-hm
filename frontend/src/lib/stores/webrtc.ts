import { writable } from "svelte/store";

export const callState = writable({
  isInCall: false,
  roomId: null,
  participants: [],
  localStream: null,
  remoteStreams: new Map(),
  isMuted: false,
  isVideoOff: false,
  isScreenSharing: false,
});

function getInitialCallState() {
  return {
    isInCall: false,
    roomId: null,
    participants: [],
    localStream: null,
    remoteStreams: new Map(),
    isMuted: false,
    isVideoOff: false,
    isScreenSharing: false,
  };
}

export const webrtcStore = {
  ...callState,

  // WebRTC peer connections
  peerConnections: new Map(),

  // Signaling
  async joinRoom(roomId: string, userId: string) {
    try {
      const response = await fetch(`/api/webrtc/room/${roomId}/join`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
        },
        body: JSON.stringify({ userId }),
      });
      return await response.json();
    } catch (error) {
      console.error("Failed to join room:", error);
      throw error;
    }
  },

  async sendOffer(roomId: string, offer: RTCSessionDescriptionInit) {
    try {
      const response = await fetch(`/api/webrtc/room/${roomId}/offer`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
        },
        body: JSON.stringify({ offer }),
      });
      return await response.json();
    } catch (error) {
      console.error("Failed to send offer:", error);
      throw error;
    }
  },

  async sendAnswer(roomId: string, answer: RTCSessionDescriptionInit) {
    try {
      const response = await fetch(`/api/webrtc/room/${roomId}/answer`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
        },
        body: JSON.stringify({ answer }),
      });
      return await response.json();
    } catch (error) {
      console.error("Failed to send answer:", error);
      throw error;
    }
  },

  async sendCandidate(roomId: string, candidate: RTCIceCandidateInit) {
    try {
      const response = await fetch(`/api/webrtc/room/${roomId}/candidate`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
        },
        body: JSON.stringify({ candidate }),
      });
      return await response.json();
    } catch (error) {
      console.error("Failed to send candidate:", error);
      throw error;
    }
  },

  async getRoomData(roomId: string) {
    try {
      const response = await fetch(`/api/webrtc/room/${roomId}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
        },
      });
      return await response.json();
    } catch (error) {
      console.error("Failed to get room data:", error);
      throw error;
    }
  },

  async leaveRoom(roomId: string) {
    try {
      const response = await fetch(`/api/webrtc/room/${roomId}/leave`, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
        },
      });
      return await response.json();
    } catch (error) {
      console.error("Failed to leave room:", error);
      throw error;
    }
  },

  reset() {
    callState.set(getInitialCallState());
    this.peerConnections.clear();
  },
};
