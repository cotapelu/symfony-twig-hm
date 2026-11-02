<script lang="ts">
  import { onMount } from 'svelte';
  import { startCall, joinCall, subscribeToTopic } from '$lib/api';

  export let roomId = '';
  export let participants = [];

  let localVideo;
  let remoteVideo;
  let localStream;
  let peerConnection;
  let isAudioEnabled = true;
  let isVideoEnabled = true;
  let isScreenSharing = false;
  let isRecording = false;
  let callStatus = 'idle'; // idle, connecting, connected, ended
  let currentCallId = null;

  onMount(async () => {
    // Subscribe to call events
    subscribeToTopic('calls', (data) => {
      if (data.type === 'call_started') {
        console.log('Call started:', data.callId);
      }
    });

    subscribeToTopic('call/' + currentCallId, (data) => {
      if (data.type === 'user_joined_call') {
        // User joined call
        console.log('User joined call:', data.userId);
      }
    });
  });

  async function startCallHandler() {
    if (!roomId.trim()) {
      alert('Please enter a room ID');
      return;
    }

    try {
      callStatus = 'connecting';
      const result = await startCall('audio');
      currentCallId = result.callId;
      callStatus = 'connected';

      // Initialize WebRTC (simplified)
      await initializeWebRTC();

    } catch (error) {
      console.error('Failed to start call:', error);
      callStatus = 'ended';
    }
  }

  async function joinCallHandler() {
    if (!roomId.trim()) {
      alert('Please enter a room ID');
      return;
    }

    try {
      callStatus = 'connecting';
      await joinCall(roomId);
      currentCallId = roomId;
      callStatus = 'connected';

      // Initialize WebRTC (simplified)
      await initializeWebRTC();

    } catch (error) {
      console.error('Failed to join call:', error);
      callStatus = 'ended';
    }
  }

  async function initializeWebRTC() {
    try {
      // Get user media
      localStream = await navigator.mediaDevices.getUserMedia({
        video: isVideoEnabled,
        audio: isAudioEnabled
      });

      if (localVideo) {
        localVideo.srcObject = localStream;
      }

      // Create peer connection (simplified)
      peerConnection = new RTCPeerConnection();

      // Add tracks
      localStream.getTracks().forEach(track => {
        peerConnection.addTrack(track, localStream);
      });

      // Handle remote stream
      peerConnection.ontrack = (event) => {
        if (remoteVideo && event.streams[0]) {
          remoteVideo.srcObject = event.streams[0];
        }
      };

    } catch (error) {
      console.error('WebRTC initialization failed:', error);
    }
  }

  function toggleAudio() {
    if (localStream) {
      const audioTrack = localStream.getAudioTracks()[0];
      if (audioTrack) {
        audioTrack.enabled = !audioTrack.enabled;
        isAudioEnabled = audioTrack.enabled;
      }
    }
  }

  function toggleVideo() {
    if (localStream) {
      const videoTrack = localStream.getVideoTracks()[0];
      if (videoTrack) {
        videoTrack.enabled = !videoTrack.enabled;
        isVideoEnabled = videoTrack.enabled;
      }
    }
  }

  async function toggleScreenShare() {
    try {
      if (isScreenSharing) {
        // Stop screen sharing
        const screenTrack = localStream.getVideoTracks().find(track =>
          track.label.includes('screen')
        );
        if (screenTrack) {
          screenTrack.stop();
          localStream.removeTrack(screenTrack);
        }
        isScreenSharing = false;
      } else {
        // Start screen sharing
        const screenStream = await navigator.mediaDevices.getDisplayMedia({
          video: true
        });

        const screenTrack = screenStream.getVideoTracks()[0];
        const sender = peerConnection.getSenders().find(s =>
          s.track && s.track.kind === 'video'
        );

        if (sender) {
          await sender.replaceTrack(screenTrack);
        }

        screenTrack.onended = () => {
          toggleScreenShare(); // Stop sharing when user cancels
        };

        isScreenSharing = true;
      }
    } catch (error) {
      console.error('Screen sharing failed:', error);
    }
  }

  function toggleRecording() {
    // Mock recording toggle
    isRecording = !isRecording;
    if (isRecording) {
      console.log('Started recording');
    } else {
      console.log('Stopped recording');
    }
  }

  function endCall() {
    callStatus = 'ended';
    currentCallId = null;

    // Clean up WebRTC
    if (localStream) {
      localStream.getTracks().forEach(track => track.stop());
    }
    if (peerConnection) {
      peerConnection.close();
    }
  }
</script>

<div class="call-interface">
  <div class="call-header">
    <h2>Voice & Video Call</h2>
    <span class="call-status">Status: {callStatus}</span>
  </div>

  <div class="call-setup">
    <input
      type="text"
      bind:value={roomId}
      placeholder="Enter room ID"
      disabled={callStatus !== 'idle'}
    />

    {#if callStatus === 'idle'}
      <button onclick={startCallHandler}>Start Call</button>
      <button onclick={joinCallHandler}>Join Call</button>
    {/if}
  </div>

  {#if callStatus === 'connected'}
    <div class="video-container">
      <video
        bind:this={localVideo}
        autoplay
        muted
        class="local-video"
      ></video>
      <video
        bind:this={remoteVideo}
        autoplay
        class="remote-video"
      ></video>
    </div>

    <div class="call-controls">
      <button onclick={toggleAudio} class={isAudioEnabled ? 'active' : ''}>
        {isAudioEnabled ? '🔊' : '🔇'} Mic
      </button>
      <button onclick={toggleVideo} class={isVideoEnabled ? 'active' : ''}>
        {isVideoEnabled ? '📹' : '📷'} Camera
      </button>
      <button onclick={toggleScreenShare} class={isScreenSharing ? 'active' : ''}>
        🖥️ Share
      </button>
      <button onclick={toggleRecording} class={isRecording ? 'recording' : ''}>
        ⏺️ Record
      </button>
      <button onclick={endCall} class="end-call">
        📞 End Call
      </button>
    </div>
  {/if}
</div>

<style>
  .call-interface {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
  }

  .call-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
  }

  .call-status {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    background: #e3f2fd;
    color: #1976d2;
  }

  .call-setup {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .call-setup input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
  }

  .call-setup button {
    padding: 0.75rem 1.5rem;
    background: #1976d2;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
  }

  .call-setup button:hover {
    background: #1565c0;
  }

  .video-container {
    position: relative;
    width: 100%;
    height: 400px;
    background: #000;
    border-radius: 12px;
    margin-bottom: 2rem;
    overflow: hidden;
  }

  .local-video {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 150px;
    height: 100px;
    border-radius: 8px;
    border: 2px solid white;
    z-index: 10;
  }

  .remote-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .call-controls {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .call-controls button {
    padding: 0.75rem 1rem;
    border: 1px solid #ddd;
    border-radius: 50px;
    background: white;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
  }

  .call-controls button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }

  .call-controls button.active {
    background: #1976d2;
    color: white;
  }

  .call-controls button.recording {
    background: #d32f2f;
    color: white;
    animation: pulse 1s infinite;
  }

  .call-controls button.end-call {
    background: #d32f2f;
    color: white;
  }

  .call-controls button.end-call:hover {
    background: #b71c1c;
  }

  @keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
  }
</style>