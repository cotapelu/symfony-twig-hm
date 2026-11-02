<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  
  export let roomId: string | null = null;
  
  let localVideo: HTMLVideoElement;
  let remoteVideo: HTMLVideoElement;
  let localStream: MediaStream | null = null;
  let isMuted = false;
  let isVideoOff = false;
  let participants: any[] = [];
  let error: string | null = null;
  
  onMount(async () => {
    try {
      localStream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: true
      });
      
      if (localVideo) {
        localVideo.srcObject = localStream;
      }
      
      // Fetch participants
      if (roomId) {
        const response = await fetch(`/api/video/rooms/${roomId}/participants`);
        if (response.ok) {
          const data = await response.json();
          participants = data.participants || [];
        }
      }
    } catch (e) {
      error = 'Không thể truy cập camera/microphone';
    }
  });
  
  onDestroy(() => {
    if (localStream) {
      localStream.getTracks().forEach(track => track.stop());
    }
  });
  
  function toggleMute() {
    if (localStream) {
      localStream.getAudioTracks().forEach(track => {
        track.enabled = !track.enabled;
      });
      isMuted = !isMuted;
    }
  }
  
  function toggleVideo() {
    if (localStream) {
      localStream.getVideoTracks().forEach(track => {
        track.enabled = !track.enabled;
      });
      isVideoOff = !isVideoOff;
    }
  }
  
  async function leaveCall() {
    if (roomId) {
      await fetch(`/api/video/rooms/${roomId}/leave`, { method: 'POST' });
    }
    window.location.href = '/calls';
  }
</script>

<div class="video-call">
  {#if error}
    <div class="error">{error}</div>
  {/if}
  
  <div class="video-grid">
    <div class="video-container local">
      <video bind:this={localVideo} autoplay muted playsinline>
        <track kind="captions" />
      </video>
      {#if isVideoOff}
        <div class="video-off">Camera tắt</div>
      {/if}
      <span class="label">Bạn</span>
    </div>
    
    {#each participants as participant}
      <div class="video-container remote">
        <video bind:this={remoteVideo} autoplay playsinline>
          <track kind="captions" />
        </video>
        <span class="label">{participant.name}</span>
      </div>
    {/each}
  </div>
  
  <div class="controls">
    <button class="control-btn {isMuted ? 'active' : ''}" on:click={toggleMute}>
      {isMuted ? '🔇' : '🎤'}
    </button>
    <button class="control-btn {isVideoOff ? 'active' : ''}" on:click={toggleVideo}>
      {isVideoOff ? '📷' : '📹'}
    </button>
    <button class="control-btn end-call" on:click={leaveCall}>
      📞 Kết thúc
    </button>
  </div>
</div>

<style>
  .video-call {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: #1a1a1a;
  }
  
  .video-grid {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
    padding: 16px;
  }
  
  .video-container {
    position: relative;
    background: #333;
    border-radius: 12px;
    overflow: hidden;
  }
  
  .video-container video {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  
  .video-off {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #333;
    color: white;
    font-size: 18px;
  }
  
  .label {
    position: absolute;
    bottom: 8px;
    left: 8px;
    background: rgba(0,0,0,0.6);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
  }
  
  .controls {
    display: flex;
    justify-content: center;
    gap: 16px;
    padding: 20px;
    background: #2a2a2a;
  }
  
  .control-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    background: #444;
    color: white;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .control-btn:hover {
    background: #555;
  }
  
  .control-btn.active {
    background: #ef4444;
  }
  
  .control-btn.end-call {
    width: auto;
    padding: 0 24px;
    background: #ef4444;
    border-radius: 28px;
    font-size: 16px;
  }
  
  .error {
    background: #ef4444;
    color: white;
    padding: 12px;
    text-align: center;
  }
</style>
