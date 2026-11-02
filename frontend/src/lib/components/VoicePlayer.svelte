<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import { createEventDispatcher } from 'svelte';

  const {
    audioUrl = '',
    audioBlob = null,
    duration: initialDuration = 0,
    showControls = true,
    autoplay = false
  } = $props();

  const dispatch = createEventDispatcher();

  let audioElement;
  let isPlaying = $state(false);
  let currentTime = $state(0);
  let duration = $state(initialDuration);
  let volume = $state(1);
  let playbackRate = $state(1);
  let isMuted = $state(false);

  // Exported state for testing
  export const getVolume = () => volume;
  export const getPlaybackRate = () => playbackRate;
  export const getIsMuted = () => isMuted;
  let progressBar;
  let isDragging = false;

  // Waveform data (simulated for now)
  let waveformData = [];
  let canvas;
  let canvasContext;

  $effect(() => {
    if (audioUrl) {
      loadAudio();
    }
  });

  onMount(() => {
    try {
      if (canvas && typeof canvas.getContext === 'function') {
        canvasContext = canvas.getContext('2d');
        if (canvasContext) {
          generateWaveform();
        }
      }
    } catch (e) {
      // Canvas not supported in test environment
      console.warn('Canvas not available, skipping waveform generation');
    }
  });

  onDestroy(() => {
    if (audioElement) {
      audioElement.pause();
      audioElement = null;
    }
  });

  function loadAudio() {
    if (audioElement) {
      audioElement.pause();
    }

    audioElement = new Audio(audioUrl);
    audioElement.preload = 'metadata';

    audioElement.addEventListener('loadedmetadata', () => {
      if (duration === 0) {
        duration = audioElement.duration;
      }
      generateWaveform();
      dispatch('loaded', { duration: audioElement.duration });
    });

    audioElement.addEventListener('timeupdate', () => {
      if (!isDragging) {
        currentTime = audioElement.currentTime;
      }
    });

    audioElement.addEventListener('ended', () => {
      isPlaying = false;
      currentTime = 0;
      dispatch('ended');
    });

    audioElement.addEventListener('play', () => {
      isPlaying = true;
      dispatch('play');
    });

    audioElement.addEventListener('pause', () => {
      isPlaying = false;
      dispatch('pause');
    });

    audioElement.addEventListener('error', (e) => {
      console.error('Audio error:', e);
      dispatch('error', { message: 'Failed to load audio' });
    });

    if (autoplay) {
      play();
    }
  }

  export async function play() {
    if (audioElement) {
      try {
        await audioElement.play();
      } catch (error) {
        console.error('Playback failed:', error);
        dispatch('error', { message: 'Playback failed' });
      }
    }
  }

  export function pause() {
    if (audioElement) {
      audioElement.pause();
    }
  }

  export function togglePlay() {
    if (isPlaying) {
      pause();
    } else {
      play();
    }
  }

  export function seek(time) {
    if (audioElement) {
      audioElement.currentTime = time;
      currentTime = time;
    }
  }

  function handleProgressClick(event) {
    if (!audioElement || !progressBar) return;

    const rect = progressBar.getBoundingClientRect();
    const clickX = event.clientX - rect.left;
    const percentage = clickX / rect.width;
    const newTime = percentage * duration;

    seek(newTime);
  }

  function handleVolumeChange(event) {
    const newVolume = parseFloat((event.target as HTMLInputElement).value);
    setVolume(newVolume);
  }

  export function setVolume(vol) {
    volume = Math.max(0, Math.min(1, vol));
    if (audioElement) {
      audioElement.volume = volume;
    }
  }

  export function toggleMute() {
    if (isMuted) {
      setVolume(volume);
      isMuted = false;
    } else {
      if (audioElement) {
        audioElement.volume = 0;
      }
      isMuted = true;
    }
  }

  export function changePlaybackRate(rate) {
    playbackRate = rate;
    if (audioElement) {
      audioElement.playbackRate = rate;
    }
  }

  export function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  }

  function generateWaveform() {
    // Generate simulated waveform data
    waveformData = Array.from({ length: 100 }, () => Math.random() * 0.8 + 0.2);
    drawWaveform();
  }

  function drawWaveform() {
    if (!canvasContext || !waveformData.length) return;

    const width = canvas.width;
    const height = canvas.height;
    const barWidth = width / waveformData.length;

    // Clear canvas
    canvasContext.fillStyle = '#f3f4f6';
    canvasContext.fillRect(0, 0, width, height);

    // Draw waveform
    waveformData.forEach((amplitude, index) => {
      const barHeight = amplitude * height;
      const x = index * barWidth;
      const y = (height - barHeight) / 2;

      // Color based on playback progress
      const progressRatio = currentTime / duration;
      const isPlayed = index / waveformData.length <= progressRatio;

      canvasContext.fillStyle = isPlayed ? '#3b82f6' : '#d1d5db';
      canvasContext.fillRect(x, y, barWidth - 1, barHeight);
    });
  }

  $effect(() => {
    if (currentTime || duration) {
      drawWaveform();
    }
  });
</script>

<div class="voice-player" data-testid="voice-player">
  <!-- Waveform visualization -->
  <div class="waveform-section" data-testid="waveform-section">
    <canvas
      bind:this={canvas}
      width="300"
      height="60"
      class="waveform-canvas"
      onclick={handleProgressClick}
      data-testid="waveform-canvas"
    ></canvas>

    <!-- Play button overlay -->
    <button
      class="play-overlay"
      onclick={togglePlay}
      aria-label={isPlaying ? 'Pause' : 'Play'}
      data-testid="play-overlay"
    >
      <div class="play-icon">{isPlaying ? '⏸️' : '▶️'}</div>
    </button>
  </div>

  <!-- Progress bar -->
  {#if showControls}
    <div class="progress-section" data-testid="progress-section">
      <div class="time-display" data-testid="time-display">
        <span class="current-time">{formatTime(currentTime)}</span>
        <span class="duration">/ {formatTime(duration)}</span>
      </div>

      <div
        class="progress-bar"
        bind:this={progressBar}
        onclick={handleProgressClick}
        data-testid="progress-bar"
      >
        <div
          class="progress-fill"
          style="width: {duration ? (currentTime / duration) * 100 : 0}%"
          data-testid="progress-fill"
        ></div>
        <div
          class="progress-handle"
          style="left: {duration ? (currentTime / duration) * 100 : 0}%"
          data-testid="progress-handle"
        ></div>
      </div>
    </div>

    <!-- Control buttons -->
    <div class="controls-section" data-testid="controls-section">
      <!-- Playback rate -->
      <select
        class="rate-select"
        bind:value={playbackRate}
        onchange={(e) => changePlaybackRate(parseFloat((e.target as HTMLInputElement).value))}
        data-testid="rate-select"
      >
        <option value="0.5">0.5x</option>
        <option value="0.75">0.75x</option>
        <option value="1">1x</option>
        <option value="1.25">1.25x</option>
        <option value="1.5">1.5x</option>
        <option value="2">2x</option>
      </select>

      <!-- Volume control -->
      <div class="volume-control">
        <button
          class="volume-btn"
          onclick={toggleMute}
          aria-label={isMuted ? 'Unmute' : 'Mute'}
          data-testid="volume-btn"
        >
          {isMuted ? '🔇' : volume > 0.5 ? '🔊' : '🔉'}
        </button>

        <input
          type="range"
          class="volume-slider"
          min="0"
          max="1"
          step="0.1"
          bind:value={volume}
          oninput={handleVolumeChange}
          disabled={isMuted}
          data-testid="volume-slider"
        />
      </div>

      <!-- Download button -->
      {#if audioUrl}
        <a
          href={audioUrl}
          download="voice-message.webm"
          class="download-btn"
          aria-label="Download audio"
          data-testid="download-btn"
        >
          ⬇️
        </a>
      {/if}
    </div>
  {/if}
</div>

<style>
  .voice-player {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
    max-width: 400px;
  }

  .waveform-section {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .waveform-canvas {
    width: 100%;
    height: 60px;
    border-radius: 0.375rem;
    cursor: pointer;
    background: white;
    border: 1px solid #d1d5db;
  }

  .play-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.2rem;
  }

  .play-overlay:hover {
    background: rgba(0, 0, 0, 0.9);
    transform: translate(-50%, -50%) scale(1.1);
  }

  .progress-section {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .time-display {
    font-size: 0.875rem;
    color: #6b7280;
    font-variant-numeric: tabular-nums;
    min-width: 80px;
  }

  .progress-bar {
    flex: 1;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    cursor: pointer;
    position: relative;
  }

  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    border-radius: 3px;
    transition: width 0.1s ease;
  }

  .progress-handle {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 16px;
    height: 16px;
    background: #3b82f6;
    border: 2px solid white;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.1s ease;
  }

  .progress-handle:hover {
    transform: translate(-50%, -50%) scale(1.2);
  }

  .controls-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }

  .rate-select {
    padding: 0.375rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: white;
    font-size: 0.875rem;
    color: #374151;
  }

  .volume-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .volume-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background 0.15s ease;
  }

  .volume-btn:hover {
    background: #e5e7eb;
  }

  .volume-slider {
    width: 80px;
    height: 4px;
    border-radius: 2px;
    background: #e5e7eb;
    outline: none;
    cursor: pointer;
  }

  .volume-slider::-webkit-slider-thumb {
    appearance: none;
    width: 16px;
    height: 16px;
    background: #3b82f6;
    border-radius: 50%;
    cursor: pointer;
  }

  .volume-slider::-moz-range-thumb {
    width: 16px;
    height: 16px;
    background: #3b82f6;
    border-radius: 50%;
    cursor: pointer;
    border: none;
  }

  .download-btn {
    color: #6b7280;
    text-decoration: none;
    font-size: 1.2rem;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.15s ease;
  }

  .download-btn:hover {
    background: #e5e7eb;
    color: #374151;
  }

  /* Dark theme support */
  @media (prefers-color-scheme: dark) {
    .voice-player {
      background: #374151;
      border-color: #4b5563;
    }

    .waveform-canvas {
      background: #1f2937;
      border-color: #374151;
    }

    .time-display {
      color: #9ca3af;
    }

    .rate-select {
      background: #1f2937;
      border-color: #374151;
      color: #f9fafb;
    }

    .volume-slider {
      background: #374151;
    }
  }

  /* Mobile responsive */
  @media (max-width: 640px) {
    .voice-player {
      padding: 0.75rem;
    }

    .controls-section {
      flex-direction: column;
      gap: 0.75rem;
    }

    .volume-control {
      width: 100%;
    }

    .volume-slider {
      flex: 1;
    }
  }
</style>