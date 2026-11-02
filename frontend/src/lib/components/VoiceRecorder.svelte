<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount, onDestroy } from 'svelte';

  export let maxDuration = 300;
  export let onRecordingComplete: ((data: { blob: Blob; url: string; duration: number }) => void) | null = null;

  const dispatch = createEventDispatcher();

  let mediaRecorder: MediaRecorder | null = null;
  let audioChunks: Blob[] = [];
  let isRecording = false;
  let isPaused = false;
  let recordingTime = 0;
  let timerInterval: ReturnType<typeof setInterval> | null = null;
  let stream: MediaStream | null = null;
  let audioContext: AudioContext | null = null;
  let analyser: AnalyserNode | null = null;
  let dataArray: Uint8Array | null = null;
  let canvas: HTMLCanvasElement | null = null;
  let canvasContext: CanvasRenderingContext2D | null = null;
  let animationFrame: number | null = null;

  let waveformBars = Array(20).fill(0);

  onMount(() => {
    // Initialize canvas for waveform
    if (canvas) {
      canvasContext = canvas.getContext('2d');
    }
  });

  onDestroy(() => {
    stopRecording();
    if (audioContext) {
      audioContext.close();
    }
  });

  async function startRecording() {
    try {
      // Request microphone access
      stream = await navigator.mediaDevices.getUserMedia({
        audio: {
          echoCancellation: true,
          noiseSuppression: true,
          autoGainControl: true
        }
      });

      // Create audio context for visualization
      const AudioContextClass = window.AudioContext || (window as any).webkitAudioContext;
      audioContext = new AudioContextClass();
      analyser = audioContext.createAnalyser();
      analyser.fftSize = 256;
      dataArray = new Uint8Array(analyser.frequencyBinCount);

      const source = audioContext.createMediaStreamSource(stream);
      source.connect(analyser);

      // Start visualization
      drawWaveform();

      // Create MediaRecorder
      mediaRecorder = new MediaRecorder(stream, {
        mimeType: 'audio/webm;codecs=opus'
      });

      audioChunks = [];

      mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
          audioChunks.push(event.data);
        }
      };

      mediaRecorder.onstop = () => {
        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
        const audioUrl = URL.createObjectURL(audioBlob);

        dispatch('recordingComplete', {
          blob: audioBlob,
          url: audioUrl,
          duration: recordingTime
        });

        if (onRecordingComplete) {
          onRecordingComplete({ blob: audioBlob, url: audioUrl, duration: recordingTime });
        }

        // Cleanup
        if (stream) {
          stream.getTracks().forEach((track) => { track.stop(); return; });
        }
        stopTimer();
        resetVisualization();
      };

      // Start recording
      mediaRecorder.start(100); // Collect data every 100ms
      isRecording = true;
      startTimer();

    } catch (error) {
      console.error('Error starting recording:', error);
      dispatch('error', { message: 'Could not access microphone' });
    }
  }

  function pauseRecording() {
    if (mediaRecorder && isRecording) {
      if (isPaused) {
        mediaRecorder.resume();
        startTimer();
        isPaused = false;
      } else {
        mediaRecorder.pause();
        stopTimer();
        isPaused = true;
      }
    }
  }

  function stopRecording() {
    if (mediaRecorder && isRecording) {
      mediaRecorder.stop();
      isRecording = false;
      isPaused = false;
      stopTimer();
      resetVisualization();
    }
  }

  function startTimer() {
    timerInterval = setInterval(() => {
      recordingTime++;
      if (recordingTime >= maxDuration) {
        stopRecording();
      }
    }, 1000);
  }

  function stopTimer() {
    if (timerInterval) {
      clearInterval(timerInterval);
      timerInterval = null;
    }
  }

  function drawWaveform() {
    if (!canvasContext || !analyser || !dataArray) return;

    analyser.getByteFrequencyData(dataArray as any);

    // Clear canvas
    canvasContext.fillStyle = '#1f2937';
    canvasContext.fillRect(0, 0, canvas.width, canvas.height);

    // Draw waveform bars
    const barWidth = canvas.width / waveformBars.length;
    let barHeight: number;
    let x = 0;

    for (let i = 0; i < waveformBars.length; i++) {
      // Average frequency data for smoother visualization
      const startIdx = Math.floor(i * dataArray.length / waveformBars.length);
      const endIdx = Math.floor((i + 1) * dataArray.length / waveformBars.length);
      let sum = 0;
      for (let j = startIdx; j < endIdx; j++) {
        sum += dataArray[j];
      }
      barHeight = (sum / (endIdx - startIdx)) / 255 * canvas.height;

      // Smooth animation
      waveformBars[i] = waveformBars[i] * 0.8 + barHeight * 0.2;

      // Draw bar
      const gradient = canvasContext.createLinearGradient(0, canvas.height - waveformBars[i], 0, canvas.height);
      gradient.addColorStop(0, '#3b82f6');
      gradient.addColorStop(1, '#1d4ed8');

      canvasContext.fillStyle = gradient;
      canvasContext.fillRect(x, canvas.height - waveformBars[i], barWidth - 2, waveformBars[i]);

      x += barWidth;
    }

    animationFrame = requestAnimationFrame(drawWaveform);
  }

  function resetVisualization() {
    if (animationFrame) {
      cancelAnimationFrame(animationFrame);
    }
    waveformBars = Array(20).fill(0);
    if (canvasContext) {
      canvasContext.fillStyle = '#1f2937';
      canvasContext.fillRect(0, 0, canvas.width, canvas.height);
    }
  }

  export function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  }

  // Exported state getters for testing
  export const getIsRecording = () => isRecording;
  export const getIsPaused = () => isPaused;
  export const getRecordingTime = () => recordingTime;
  export const getMaxDuration = () => maxDuration;
  export const getMediaRecorder = () => mediaRecorder;
  export const getStream = () => stream;
  export const getAudioContext = () => audioContext;
</script>

<div class="voice-recorder" data-testid="voice-recorder">
  <div class="recorder-header">
    <h3 class="recorder-title">🎤 Voice Message</h3>
    {#if isRecording}
      <div class="recording-indicator" data-testid="recording-indicator">
        <div class="recording-dot"></div>
        <span class="recording-text" data-testid="recording-text">
          {isPaused ? 'Paused' : 'Recording'} - {formatTime(recordingTime)}
        </span>
      </div>
    {/if}
  </div>

  <!-- Waveform visualization -->
  {#if isRecording}
    <div class="waveform-container" data-testid="waveform-container">
      <canvas
        bind:this={canvas}
        width="300"
        height="80"
        class="waveform-canvas"
        data-testid="waveform-canvas"
      ></canvas>
    </div>
  {/if}

  <!-- Controls -->
  <div class="recorder-controls" data-testid="recorder-controls">
    {#if !isRecording}
      <button
        class="record-btn start"
        onclick={startRecording}
        title="Start recording"
        data-testid="record-btn-start"
      >
        <div class="record-icon">🎤</div>
        <span>Record</span>
      </button>
    {:else}
      <button
        class="record-btn pause"
        onclick={pauseRecording}
        title={isPaused ? 'Resume recording' : 'Pause recording'}
        data-testid="record-btn-pause"
      >
        <div class="record-icon">{isPaused ? '▶️' : '⏸️'}</div>
        <span>{isPaused ? 'Resume' : 'Pause'}</span>
      </button>

      <button
        class="record-btn stop"
        onclick={stopRecording}
        title="Stop recording"
        data-testid="record-btn-stop"
      >
        <div class="record-icon">⏹️</div>
        <span>Stop</span>
      </button>
    {/if}
  </div>

  <!-- Recording limits -->
  {#if isRecording}
    <div class="recording-limits" data-testid="recording-limits">
      <div class="time-limit" data-testid="time-limit">
        Time remaining: {formatTime(maxDuration - recordingTime)}
      </div>
      <div class="progress-bar" data-testid="progress-bar">
        <div
          class="progress-fill"
          style="width: {(recordingTime / maxDuration) * 100}%"
          data-testid="progress-fill"></div>
      </div>
    </div>
  {/if}
</div>

<style>
  .voice-recorder {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    max-width: 400px;
  }

  .recorder-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .recorder-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
  }

  .recording-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .recording-dot {
    width: 12px;
    height: 12px;
    background: #ef4444;
    border-radius: 50%;
    animation: pulse 1s infinite;
  }

  .recording-text {
    font-size: 0.875rem;
    color: #ef4444;
    font-weight: 500;
  }

  .waveform-container {
    margin-bottom: 1rem;
    display: flex;
    justify-content: center;
  }

  .waveform-canvas {
    border-radius: 0.375rem;
    background: #1f2937;
    border: 1px solid #374151;
  }

  .recorder-controls {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .record-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 80px;
  }

  .record-btn:hover {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .record-btn.start {
    border-color: #ef4444;
    color: #ef4444;
  }

  .record-btn.start:hover {
    background: #fef2f2;
    border-color: #dc2626;
  }

  .record-btn.pause {
    border-color: #f59e0b;
    color: #f59e0b;
  }

  .record-btn.pause:hover {
    background: #fffbeb;
    border-color: #d97706;
  }

  .record-btn.stop {
    border-color: #6b7280;
    color: #6b7280;
  }

  .record-btn.stop:hover {
    background: #f9fafb;
    border-color: #374151;
  }

  .record-icon {
    font-size: 1.5rem;
  }

  .recording-limits {
    margin-top: 1rem;
  }

  .time-limit {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
    text-align: center;
  }

  .progress-bar {
    width: 100%;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
  }

  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    border-radius: 2px;
    transition: width 0.3s ease;
  }

  @keyframes pulse {
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: 0.5;
    }
  }

  /* Dark theme support */
  @media (prefers-color-scheme: dark) {
    .voice-recorder {
      background: #1f2937;
      border-color: #374151;
    }

    .recorder-title {
      color: #f9fafb;
    }

    .recording-text {
      color: #fca5a5;
    }

    .record-btn {
      background: #1f2937;
      border-color: #374151;
    }

    .record-btn:hover {
      border-color: #3b82f6;
    }

    .time-limit {
      color: #9ca3af;
    }
  }

  /* Mobile responsive */
  @media (max-width: 640px) {
    .voice-recorder {
      padding: 1rem;
    }

    .recorder-controls {
      flex-direction: row;
      flex-wrap: wrap;
    }

    .record-btn {
      min-width: 70px;
      padding: 0.75rem;
    }

    .waveform-canvas {
      width: 250px;
      height: 60px;
    }
  }
</style>
