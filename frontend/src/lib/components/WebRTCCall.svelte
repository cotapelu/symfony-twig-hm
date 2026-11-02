<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import { goto } from '$app/navigation';
  import { callState, webrtcStore } from '$lib/stores/webrtc';
  import { user } from '$lib/stores/global';

  export let roomId: string;

  let localVideo: HTMLVideoElement;
  let remoteVideos: HTMLVideoElement[] = [];
  let localStream: MediaStream;
  let isMuted = false;
  let isVideoOff = false;
  let isScreenSharing = false;

  // WebRTC configuration
  const rtcConfiguration = {
    iceServers: [
      { urls: 'stun:stun.l.google.com:19302' },
      { urls: 'stun:stun1.l.google.com:19302' },
    ],
  };

  onMount(async () => {
    try {
      // Get user media
      localStream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: true,
      });

      // Display local video
      if (localVideo) {
        localVideo.srcObject = localStream;
      }

      // Join room
      await joinRoom();
    } catch (error) {
      console.error('Failed to initialize WebRTC:', error);
      alert('Failed to access camera/microphone. Please check permissions.');
    }
  });

  onDestroy(() => {
    // Cleanup
    if (localStream) {
      localStream.getTracks().forEach(track => track.stop());
    }
    webrtcStore.peerConnections.forEach((pc: RTCPeerConnection) => {
      pc.close();
    });
    webrtcStore.peerConnections.clear();
  });

  async function joinRoom() {
    try {
      const roomData = await webrtcStore.joinRoom(roomId, $user?.id);

      callState.update(state => ({
        ...state,
        isInCall: true,
        roomId,
        participants: roomData.participants || [],
      }));

      // Setup signaling listeners
      setupSignaling();
    } catch (error) {
      console.error('Failed to join room:', error);
      alert('Failed to join call');
    }
  }

  function setupSignaling() {
    // Listen for signaling messages via Mercure or WebSocket
    // For now, use polling or implement WebSocket
  }

  async function createPeerConnection(participantId) {
    const pc = new RTCPeerConnection(rtcConfiguration);

    // Add local stream
    localStream.getTracks().forEach((track: MediaStreamTrack) => {
      pc.addTrack(track, localStream);
      return;
    });

    // Handle remote stream
    pc.ontrack = (event) => {
      const remoteStream = event.streams[0];
      callState.update(state => ({
        ...state,
        remoteStreams: new Map(state.remoteStreams.set(participantId, remoteStream)),
      }));
    };

    // Handle ICE candidates
    pc.onicecandidate = (event) => {
      if (event.candidate) {
        webrtcStore.sendCandidate(roomId, event.candidate);
      }
    };

    webrtcStore.peerConnections.set(participantId, pc);
    return pc;
  }

  async function startCall() {
    const pc = await createPeerConnection('remote-peer');

    // Create offer
    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);

    await webrtcStore.sendOffer(roomId, offer);
  }

  export function toggleMute() {
    if (localStream) {
      localStream.getAudioTracks().forEach(track => {
        track.enabled = isMuted;
      });
      isMuted = !isMuted;
    }
  }

  export function toggleVideo() {
    if (localStream) {
      localStream.getVideoTracks().forEach(track => {
        track.enabled = isVideoOff;
      });
      isVideoOff = !isVideoOff;
    }
  }

  export function toggleScreenShare() {
    // Implement screen sharing
    isScreenSharing = !isScreenSharing;
  }

  export function hangup() {
    // Leave room and cleanup
    webrtcStore.leaveRoom(roomId);
    callState.update(state => ({
      ...state,
      isInCall: false,
      roomId: null,
      participants: [],
      remoteStreams: new Map(),
    }));
    goto('/messages');
  }

  // Exported state getters for testing
  export const getIsMuted = () => isMuted;
  export const getIsVideoOff = () => isVideoOff;
  export const getIsScreenSharing = () => isScreenSharing;
  export const getLocalStream = () => localStream;
  export const getRtcConfiguration = () => rtcConfiguration;
</script>

<div class="webrtc-call" data-testid="webrtc-call">
  <!-- Local video -->
  <div class="local-video" data-testid="local-video">
    <video
      bind:this={localVideo}
      autoplay
      muted
      playsinline
      class="video-element" data-testid="local-video-element"
    ></video>
    <div class="video-label">You</div>
  </div>

  <!-- Remote videos -->
  {#each Array.from($callState.remoteStreams.entries()) as [participantId, stream]}
    <div class="remote-video">
      <video
        srcObject={stream}
        autoplay
        playsinline
        class="video-element" data-testid="remote-video-element-{participantId}"
      ></video>
      <div class="video-label">{participantId}</div>
    </div>
  {/each}

  <!-- Call controls -->
  <div class="call-controls" data-testid="call-controls">
    <button
      class="control-btn {isMuted ? 'muted' : ''}"
      onclick={toggleMute}
      data-testid="toggle-mute-btn"
    >
      {isMuted ? '🔇' : '🎤'}
    </button>

    <button
      class="control-btn {isVideoOff ? 'off' : ''}"
      onclick={toggleVideo}
      data-testid="toggle-video-btn"
    >
      {isVideoOff ? '📷❌' : '📷'}
    </button>

    <button
      class="control-btn hangup-btn"
      onclick={hangup}
      data-testid="hangup-btn"
    >
      📞❌
    </button>

    <button
      class="control-btn {isScreenSharing ? 'active' : ''}"
      onclick={toggleScreenShare}
      data-testid="toggle-screen-share-btn"
    >
      🖥️
    </button>
  </div>
</div>

<style>
  .webrtc-call {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: #000;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }

  .local-video,
  .remote-video {
    position: relative;
    margin: 10px;
  }

  .video-element {
    width: 300px;
    height: 200px;
    border-radius: 8px;
    object-fit: cover;
  }

  .video-label {
    position: absolute;
    bottom: 8px;
    left: 8px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
  }

  .call-controls {
    position: fixed;
    bottom: 20px;
    display: flex;
    gap: 20px;
  }

  .control-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: none;
    background: #333;
    color: white;
    font-size: 24px;
    cursor: pointer;
    transition: background 0.2s;
  }

  .control-btn:hover {
    background: #555;
  }

  .control-btn.muted {
    background: #ff4444;
  }

  .control-btn.off {
    background: #ff4444;
  }

  .control-btn.active {
    background: #44ff44;
  }

  .hangup-btn {
    background: #ff4444;
  }

  .hangup-btn:hover {
    background: #cc0000;
  }
</style>