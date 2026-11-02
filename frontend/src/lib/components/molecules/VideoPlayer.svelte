<script lang="ts">
  import { createEventDispatcher, onMount, onDestroy } from 'svelte';
  import Button from '../atoms/Button.svelte';
  import Icon from '../atoms/Icon.svelte';

  export let src: string = '';
  export let poster: string = '';
  export let autoplay: boolean = false;
  export let muted: boolean = false;
  export let loop: boolean = false;
  export let controls: boolean = true;
  export let preload: 'none' | 'metadata' | 'auto' = 'metadata';
  export let width: string = '100%';
  export let height: string = 'auto';

  const dispatch = createEventDispatcher<{
    play: void;
    pause: void;
    ended: void;
    timeupdate: { currentTime: number; duration: number };
    volumechange: { volume: number; muted: boolean };
    fullscreen: boolean;
  }>();

  let videoElement: HTMLVideoElement;
  let containerElement: HTMLElement;
  let progressBar: HTMLElement;
  let volumeBar: HTMLElement;
  let isPlaying: boolean = false;
  let currentTime: number = 0;
  let duration: number = 0;
  let volume: number = 1;
  let isMuted: boolean = false;
  let isFullscreen: boolean = false;
  let showControls: boolean = true;
  let controlsTimeout: any;

  onMount(() => {
    if (videoElement) {
      videoElement.addEventListener('loadedmetadata', handleLoadedMetadata);
      videoElement.addEventListener('timeupdate', handleTimeUpdate);
      videoElement.addEventListener('ended', handleEnded);
      videoElement.addEventListener('play', () => { isPlaying = true; dispatch('play'); });
      videoElement.addEventListener('pause', () => { isPlaying = false; dispatch('pause'); });
      videoElement.addEventListener('volumechange', handleVolumeChange);
    }

    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);
  });

  onDestroy(() => {
    if (videoElement) {
      videoElement.removeEventListener('loadedmetadata', handleLoadedMetadata);
      videoElement.removeEventListener('timeupdate', handleTimeUpdate);
      videoElement.removeEventListener('ended', handleEnded);
    }

    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.removeEventListener('mozfullscreenchange', handleFullscreenChange);
    document.removeEventListener('MSFullscreenChange', handleFullscreenChange);

    clearTimeout(controlsTimeout);
  });

  function handleLoadedMetadata() {
    duration = videoElement.duration;
  }

  function handleTimeUpdate() {
    currentTime = videoElement.currentTime;
    dispatch('timeupdate', { currentTime, duration });
  }

  function handleEnded() {
    dispatch('ended');
  }

  function handleVolumeChange() {
    volume = videoElement.volume;
    isMuted = videoElement.muted;
    dispatch('volumechange', { volume, muted: isMuted });
  }

  function handleFullscreenChange() {
    isFullscreen = !!(
      document.fullscreenElement ||
      (document as any).webkitFullscreenElement ||
      (document as any).mozFullScreenElement ||
      (document as any).msFullscreenElement
    );
    dispatch('fullscreen', isFullscreen);
  }

  export function togglePlay() {
    if (videoElement.paused) {
      videoElement.play();
    } else {
      videoElement.pause();
    }
  }

  export function seekTo(time: number) {
    videoElement.currentTime = time;
  }

  function handleProgressClick(event: MouseEvent) {
    const rect = progressBar.getBoundingClientRect();
    const percent = (event.clientX - rect.left) / rect.width;
    seekTo(percent * duration);
  }

  export function setVolume(newVolume: number) {
    videoElement.volume = newVolume;
    volume = newVolume;
  }

  export function toggleMute() {
    videoElement.muted = !videoElement.muted;
  }

  function handleVolumeClick(event: MouseEvent) {
    const rect = volumeBar.getBoundingClientRect();
    const percent = (event.clientX - rect.left) / rect.width;
    setVolume(Math.max(0, Math.min(1, percent)));
  }

  export function toggleFullscreen() {
    if (!isFullscreen) {
      if (containerElement.requestFullscreen) {
        containerElement.requestFullscreen();
      } else if ((containerElement as any).webkitRequestFullscreen) {
        (containerElement as any).webkitRequestFullscreen();
      } else if ((containerElement as any).mozRequestFullScreen) {
        (containerElement as any).mozRequestFullScreen();
      } else if ((containerElement as any).msRequestFullscreen) {
        (containerElement as any).msRequestFullscreen();
      }
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      } else if ((document as any).webkitExitFullscreen) {
        (document as any).webkitExitFullscreen();
      } else if ((document as any).mozCancelFullScreen) {
        (document as any).mozCancelFullScreen();
      } else if ((document as any).msExitFullscreen) {
        (document as any).msExitFullscreen();
      }
    }
  }

  export function formatTime(time: number): string {
    const minutes = Math.floor(time / 60);
    const seconds = Math.floor(time % 60);
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
  }

  function showControlsTemporarily() {
    showControls = true;
    clearTimeout(controlsTimeout);
    controlsTimeout = setTimeout(() => {
      if (isPlaying) showControls = false;
    }, 3000);
  }

  // Exported state getters for testing
  export const getIsPlaying = () => isPlaying;
  export const getCurrentTime = () => currentTime;
  export const getDuration = () => duration;
  export const getVolume = () => volume;
  export const getIsMuted = () => isMuted;
  export const getIsFullscreen = () => isFullscreen;
  export const getShowControls = () => showControls;

  // Exported functions for testing
  export { togglePlay, seekTo, setVolume, toggleMute, toggleFullscreen, formatTime };
</script>

<div
  class="video-player" data-testid="video-player"
  class:fullscreen={isFullscreen}
  bind:this={containerElement}
  style="width: {width}; height: {height}"
  role="region"
  aria-label="Video player"
  tabindex="0"
  onmousemove={showControlsTemporarily}
  onmouseenter={() => showControls = true}
  onmouseleave={() => { if (isPlaying) showControls = false; }}
  onkeydown={(e) => { if (e.key === ' ' || e.key === 'k') { togglePlay(); e.preventDefault(); } }}
>
  <video
    bind:this={videoElement} data-testid="video-element"
    {src}
    {poster}
    {autoplay}
    {muted}
    {loop}
    {preload}
    class="video-element"
  ></video>

  {#if controls}
    <div class="video-controls" class:visible={showControls} data-testid="video-controls">
      <div
        class="progress-container" data-testid="progress-bar"
        bind:this={progressBar}
        role="slider"
        aria-label="Video progress"
        aria-valuemin={0}
        aria-valuemax={duration}
        aria-valuenow={currentTime}
        tabindex="0"
        onclick={handleProgressClick}
        onkeydown={(e) => { if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') { e.preventDefault(); const percent = (currentTime + (e.key === 'ArrowRight' ? 5 : -5)) / duration; seekTo(Math.max(0, Math.min(1, percent)) * duration); } }}
      >
        <div class="progress-bar" data-testid="progress-fill" style="width: {duration ? (currentTime / duration) * 100 : 0}%"></div>
      </div>

      <div class="controls-row">
        <Button variant="ghost" onclick={togglePlay} data-testid="play-pause-btn" aria-label={isPlaying ? 'Pause' : 'Play'}>
          <Icon name={isPlaying ? 'pause' : 'play'} />
        </Button>

        <div class="time-display" aria-label="Current time" data-testid="time-display">
          {formatTime(currentTime)} / {formatTime(duration)}
        </div>

        <div class="volume-controls">
          <Button variant="ghost" size="sm" onclick={toggleMute} data-testid="mute-btn" aria-label={isMuted ? 'Unmute' : 'Mute'}>
            <Icon name={isMuted ? 'volume-x' : 'volume-2'} />
          </Button>
          <div
            class="volume-container" data-testid="volume-container"
            bind:this={volumeBar}
            role="slider"
            aria-label="Volume"
            aria-valuemin={0}
            aria-valuemax={1}
            aria-valuenow={volume}
            tabindex="0"
            onclick={handleVolumeClick}
            onkeydown={(e) => { if (e.key === 'ArrowUp' || e.key === 'ArrowDown') { e.preventDefault(); const delta = e.key === 'ArrowUp' ? 0.1 : -0.1; setVolume(Math.max(0, Math.min(1, volume + delta))); } }}
          >
            <div class="volume-bar" data-testid="volume-fill" style="width: {isMuted ? 0 : volume * 100}%"></div>
          </div>
        </div>

        <Button variant="ghost" onclick={toggleFullscreen} data-testid="fullscreen-btn" aria-label={isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'}>
          <Icon name={isFullscreen ? 'minimize' : 'maximize'} />
        </Button>
      </div>
    </div>
  {/if}
</div>

<style>
  .video-player {
    position: relative;
    background: #000;
    border-radius: var(--border-radius, 8px);
    overflow: hidden;
  }

  .video-player.fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 9999;
  }

  .video-element {
    width: 100%;
    height: 100%;
    display: block;
  }

  .video-controls {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
    padding: 1rem;
    opacity: 0;
    transition: opacity 0.3s ease;
  }

  .video-controls.visible {
    opacity: 1;
  }

  .progress-container {
    width: 100%;
    height: 6px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
    cursor: pointer;
    margin-bottom: 0.5rem;
  }

  .progress-bar {
    height: 100%;
    background: var(--primary-500, #3b82f6);
    border-radius: 3px;
    transition: width 0.1s ease;
  }

  .controls-row {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .time-display {
    font-size: 0.875rem;
    color: white;
    min-width: 4rem;
  }

  .volume-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .volume-container {
    width: 60px;
    height: 4px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
    cursor: pointer;
  }

  .volume-bar {
    height: 100%;
    background: white;
    border-radius: 2px;
    transition: width 0.1s ease;
  }

  @media (max-width: 768px) {
    .controls-row {
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .time-display {
      order: 1;
      flex: 1;
      text-align: center;
    }

    .volume-controls {
      order: 2;
    }
  }
</style>