<script lang="ts">
  import { createEventDispatcher, onMount, onDestroy } from 'svelte';
  import Button from '../atoms/Button.svelte';
  import Icon from '../atoms/Icon.svelte';

  interface PlaylistItem {
    id: string;
    title: string;
    artist?: string;
    src: string;
    duration?: number;
  }

  export let playlist: PlaylistItem[] = [];
  export let currentIndex: number = 0;
  export let autoplay: boolean = false;
  export let loop: boolean = false;
  export let shuffle: boolean = false;
  export let showWaveform: boolean = true;
  export let waveformColor: string = '#3b82f6';
  export let progressColor: string = '#10b981';

  const dispatch = createEventDispatcher<{
    play: PlaylistItem;
    pause: void;
    ended: PlaylistItem;
    trackChange: { item: PlaylistItem; index: number };
    timeupdate: { currentTime: number; duration: number };
  }>();

  let audioElement: HTMLAudioElement;
  let canvasElement: HTMLCanvasElement;
  let progressBar: HTMLElement;
  let isPlaying: boolean = false;
  let currentTime: number = 0;
  let duration: number = 0;
  let volume: number = 1;
  let isMuted: boolean = false;
  let waveformData: number[] = [];
  let shuffledIndices: number[] = [];

  $: currentTrack = playlist[currentIndex];
  $: playlistLength = playlist.length;

  onMount(() => {
    if (audioElement) {
      audioElement.addEventListener('loadedmetadata', handleLoadedMetadata);
      audioElement.addEventListener('timeupdate', handleTimeUpdate);
      audioElement.addEventListener('ended', handleEnded);
      audioElement.addEventListener('play', () => { isPlaying = true; dispatch('play', currentTrack); });
      audioElement.addEventListener('pause', () => { isPlaying = false; dispatch('pause'); });
    }

    if (shuffle) {
      generateShuffledIndices();
    }
  });

  onDestroy(() => {
    if (audioElement) {
      audioElement.removeEventListener('loadedmetadata', handleLoadedMetadata);
      audioElement.removeEventListener('timeupdate', handleTimeUpdate);
      audioElement.removeEventListener('ended', handleEnded);
    }
  });

  function handleLoadedMetadata() {
    duration = audioElement.duration;
    if (showWaveform) {
      generateWaveform();
    }
  }

  function handleTimeUpdate() {
    currentTime = audioElement.currentTime;
    dispatch('timeupdate', { currentTime, duration });
    if (showWaveform && canvasElement) {
      drawWaveform();
    }
  }

  function handleEnded() {
    dispatch('ended', currentTrack);
    if (loop) {
      audioElement.currentTime = 0;
      audioElement.play();
    } else {
      nextTrack();
    }
  }

  function generateWaveform() {
    // Mock waveform data - in real app, analyze audio file
    waveformData = Array.from({ length: 200 }, () => Math.random() * 100);
  }

  function drawWaveform() {
    if (!canvasElement || !waveformData.length) return;

    const ctx = canvasElement.getContext('2d');
    if (!ctx) return;

    const width = canvasElement.width;
    const height = canvasElement.height;
    const progress = duration ? currentTime / duration : 0;

    ctx.clearRect(0, 0, width, height);

    // Draw background waveform
    ctx.fillStyle = waveformColor;
    waveformData.forEach((amplitude, index) => {
      const x = (index / waveformData.length) * width;
      const barHeight = (amplitude / 100) * height * 0.8;
      const y = (height - barHeight) / 2;

      if (x / width <= progress) {
        ctx.fillStyle = progressColor;
      } else {
        ctx.fillStyle = waveformColor;
      }

      ctx.fillRect(x, y, 1, barHeight);
    });
  }

  function togglePlay() {
    if (audioElement.paused) {
      audioElement.play();
    } else {
      audioElement.pause();
    }
  }

  function seekTo(time: number) {
    audioElement.currentTime = time;
  }

  function handleProgressClick(event: MouseEvent) {
    const rect = progressBar.getBoundingClientRect();
    const percent = (event.clientX - rect.left) / rect.width;
    seekTo(percent * duration);
  }

  function handleWaveformClick(event: MouseEvent) {
    if (!canvasElement) return;
    const rect = canvasElement.getBoundingClientRect();
    const percent = (event.clientX - rect.left) / rect.width;
    seekTo(percent * duration);
  }

  function setVolume(newVolume: number) {
    audioElement.volume = newVolume;
    volume = newVolume;
  }

  function toggleMute() {
    audioElement.muted = !audioElement.muted;
    isMuted = audioElement.muted;
  }

  function nextTrack() {
    if (playlistLength <= 1) return;

    if (shuffle) {
      const nextShuffleIndex = shuffledIndices.indexOf(currentIndex) + 1;
      if (nextShuffleIndex < shuffledIndices.length) {
        currentIndex = shuffledIndices[nextShuffleIndex];
      } else {
        // End of shuffled playlist
        isPlaying = false;
        return;
      }
    } else {
      currentIndex = (currentIndex + 1) % playlistLength;
    }

    dispatch('trackChange', { item: currentTrack, index: currentIndex });
  }

  function prevTrack() {
    if (playlistLength <= 1) return;

    if (shuffle) {
      const prevShuffleIndex = shuffledIndices.indexOf(currentIndex) - 1;
      if (prevShuffleIndex >= 0) {
        currentIndex = shuffledIndices[prevShuffleIndex];
      } else {
        currentIndex = shuffledIndices[0];
      }
    } else {
      currentIndex = currentIndex === 0 ? playlistLength - 1 : currentIndex - 1;
    }

    dispatch('trackChange', { item: currentTrack, index: currentIndex });
  }

  function selectTrack(index: number) {
    currentIndex = index;
    dispatch('trackChange', { item: playlist[index], index });
  }

  function toggleShuffle() {
    shuffle = !shuffle;
    if (shuffle) {
      generateShuffledIndices();
    }
  }

  function generateShuffledIndices() {
    shuffledIndices = Array.from({ length: playlistLength }, (_, i) => i).sort(() => Math.random() - 0.5);
  }

  function formatTime(time: number): string {
    const minutes = Math.floor(time / 60);
    const seconds = Math.floor(time % 60);
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
  }

  // Exported functions and state for testing
  export { formatTime };
  export const getShuffle = () => shuffle;
  export const getIsPlaying = () => isPlaying;
  export const getIsMuted = () => isMuted;
  export const getVolume = () => volume;
  export const getWaveformData = () => waveformData;
  export const getCurrentTime = () => currentTime;
  export const getDuration = () => duration;
  export const getCurrentTrack = () => currentTrack;
  export const getCurrentIndex = () => currentIndex;
  export const getShuffledIndices = () => shuffledIndices;

  // Exported functions for testing
  export { togglePlay, toggleMute, toggleShuffle, setVolume, nextTrack, prevTrack, selectTrack, seekTo };

  // Reactive: when currentIndex changes, load new track
  $: if (currentTrack && audioElement) {
    audioElement.src = currentTrack.src;
    if (autoplay) {
      audioElement.play();
    }
  }
</script>

<div class="audio-player">
  {#if currentTrack}
    <audio bind:this={audioElement} {autoplay} {loop} preload="metadata" data-testid="audio-element"></audio>

    <div class="player-main">
      <div class="track-info">
        <h3 class="track-title" data-testid="current-track-title">{currentTrack.title}</h3>
        {#if currentTrack.artist}
          <p class="track-artist" data-testid="current-track-artist">{currentTrack.artist}</p>
        {/if}
      </div>

      {#if showWaveform}
        <canvas
          bind:this={canvasElement}
          class="waveform"
          onclick={handleWaveformClick}
          width="400"
          height="80"
          data-testid="waveform-canvas"
        ></canvas>
      {:else}
        <div
          class="progress-container"
          bind:this={progressBar}
          role="slider"
          aria-label="Audio progress"
          aria-valuemin={0}
          aria-valuemax={duration}
          aria-valuenow={currentTime}
          tabindex="0"
          onclick={handleProgressClick}
          onkeydown={(e) => { if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') { e.preventDefault(); const percent = (currentTime + (e.key === 'ArrowRight' ? 5 : -5)) / duration; seekTo(Math.max(0, Math.min(1, percent)) * duration); } }}
          data-testid="progress-bar"
        >
          <div class="progress-bar" style="width: {duration ? (currentTime / duration) * 100 : 0}%"></div>
        </div>
      {/if}

      <div class="time-display">
        {formatTime(currentTime)} / {formatTime(duration)}
      </div>
    </div>

    <div class="player-controls" data-testid="player-controls">
      <Button variant="ghost" onclick={prevTrack} disabled={playlistLength <= 1} data-testid="prev-track-btn" aria-label="Previous track">
        <Icon name="skip-back" />
      </Button>

      <Button variant="ghost" onclick={togglePlay} data-testid="play-pause-btn" aria-label={isPlaying ? 'Pause' : 'Play'}>
        <Icon name={isPlaying ? 'pause' : 'play'} />
      </Button>

      <Button variant="ghost" onclick={nextTrack} disabled={playlistLength <= 1} data-testid="next-track-btn" aria-label="Next track">
        <Icon name="skip-forward" />
      </Button>

      <Button variant="ghost" size="sm" onclick={toggleShuffle} data-testid="shuffle-btn" aria-label="Toggle shuffle">
        <Icon name="shuffle" />
      </Button>

      <div class="volume-control">
        <Button variant="ghost" size="sm" onclick={toggleMute} data-testid="mute-btn" aria-label={isMuted ? 'Unmute' : 'Mute'}>
          <Icon name={isMuted ? 'volume-x' : 'volume-2'} />
        </Button>
        <input
          type="range"
          min="0"
          max="1"
          step="0.1"
          bind:value={volume}
          oninput={(e) => setVolume(parseFloat((e.target as HTMLInputElement).value))}
          class="volume-slider"
          data-testid="volume-slider"
          aria-label="Volume"
        />
      </div>
    </div>

    {#if playlistLength > 1}
      <div class="playlist" role="listbox" aria-label="Playlist">
        {#each playlist as track, index}
          <div
            class="playlist-item"
            class:active={index === currentIndex}
            role="option"
            aria-selected={index === currentIndex}
            tabindex="0"
            onclick={() => selectTrack(index)}
            onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { selectTrack(index); e.preventDefault(); } }}
            data-testid="playlist-item-{index}"
          >
            <span class="track-number">{index + 1}</span>
            <div class="track-details">
              <span class="track-title" data-testid="track-title-{index}">{track.title}</span>
              {#if track.artist}
                <span class="track-artist" data-testid="track-artist-{index}">{track.artist}</span>
              {/if}
            </div>
            {#if track.duration}
              <span class="track-duration" data-testid="track-duration-{index}">{formatTime(track.duration)}</span>
            {/if}
          </div>
        {/each}
      </div>
    {/if}
  {:else}
    <p>No tracks in playlist</p>
  {/if}
</div>

<style>
  .audio-player {
    max-width: 600px;
    background: var(--bg-primary, white);
    border-radius: var(--border-radius, 8px);
    padding: 1.5rem;
    box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
  }

  .player-main {
    margin-bottom: 1.5rem;
  }

  .track-info {
    text-align: center;
    margin-bottom: 1rem;
  }

  .track-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
  }

  .track-artist {
    color: var(--text-secondary, #6b7280);
    margin: 0.25rem 0 0 0;
  }

  .waveform {
    width: 100%;
    height: 80px;
    cursor: pointer;
    border-radius: 4px;
    background: var(--bg-secondary, #f9fafb);
  }

  .progress-container {
    width: 100%;
    height: 6px;
    background: var(--bg-secondary, #f9fafb);
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

  .time-display {
    text-align: center;
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
  }

  .player-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .volume-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .volume-slider {
    width: 80px;
  }

  .playlist {
    max-height: 300px;
    overflow-y: auto;
  }

  .playlist-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    cursor: pointer;
    border-radius: 4px;
    transition: background 0.2s ease;
  }

  .playlist-item:hover {
    background: var(--bg-secondary, #f9fafb);
  }

  .playlist-item.active {
    background: var(--primary-50, #eff6ff);
    color: var(--primary-700, #1d4ed8);
  }

  .track-number {
    width: 2rem;
    text-align: center;
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
  }

  .track-details {
    flex: 1;
  }

  .track-details .track-title {
    font-size: 0.875rem;
    font-weight: 500;
  }

  .track-details .track-artist {
    font-size: 0.75rem;
    color: var(--text-secondary, #6b7280);
  }

  .track-duration {
    font-size: 0.75rem;
    color: var(--text-secondary, #6b7280);
  }

  @media (max-width: 480px) {
    .audio-player {
      padding: 1rem;
    }

    .player-controls {
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .volume-control {
      order: -1;
      flex-basis: 100%;
    }

    .volume-slider {
      flex: 1;
    }
  }
</style>