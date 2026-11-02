<script lang="ts">
    import { onMount } from 'svelte';
    import { connectToMercure, fetchCalls, subscribeToTopic } from '$lib/api';
    import { user, showToast, type ToastType } from '$lib/stores/global';

    let localVideo: HTMLVideoElement;
    let remoteVideo: HTMLVideoElement;
    let roomId = '';
    let isConnected = false;
    let isInCall = false;
    let participants: Array<{ id: string; name?: string; audioEnabled?: boolean; videoEnabled?: boolean; screenSharing?: boolean; isHost?: boolean; speaking?: boolean }> = [];
    let localStream: MediaStream | null = null;
    let peerConnection: RTCPeerConnection | null = null;
    let dataChannel: RTCDataChannel | null = null;
    let isAudioEnabled = true;
    let isVideoEnabled = true;
    let isScreenSharing = false;
    let isRecording = false;
    let callDuration = 0;
    let callQuality: 'good' | 'fair' | 'poor' = 'good';
    let breakoutRooms: Array<{ id: string; name: string; participants: string[] }> = [];
    let currentRoomId: string | null = null;

   const configuration: RTCConfiguration = {
     iceServers: [
       { urls: 'stun:stun.l.google.com:19302' },
       { urls: 'stun:stun1.l.google.com:19302' }
     ]
   };

   onMount(() => {
     connectToMercure();

     subscribeToTopic('calls', (data: unknown) => {
       handleCallEvent(data);
     });
   });

   async function startCall() {
     if (!roomId.trim()) {
       alert('Please enter a room ID');
       return;
     }

     try {
       localStream = await navigator.mediaDevices.getUserMedia({
         video: true,
         audio: true
       });

       if (localVideo) {
         localVideo.srcObject = localStream;
       }

       peerConnection = new RTCPeerConnection(configuration);

       if (localStream) {
         localStream.getTracks().forEach(track => {
           if (peerConnection && localStream) {
             peerConnection.addTrack(track, localStream);
           }
         });
       }

       if (peerConnection) {
         peerConnection.ontrack = (event) => {
           if (remoteVideo && event.streams[0]) {
             remoteVideo.srcObject = event.streams[0];
           }
         };

          peerConnection.onicecandidate = (event: any) => {
           if (event.candidate) {
             sendSignalingMessage('candidate', event.candidate);
           }
         };

         const offer = await peerConnection.createOffer();
         await peerConnection.setLocalDescription(offer);

         await sendSignalingMessage('offer', offer);
       }

       isInCall = true;
     } catch (error) {
       console.error('Error starting call:', error);
       alert('Failed to start call: ' + (error as Error).message);
     }
   }

     async function joinCall() {
      if (!roomId.trim()) {
        alert('Please enter a room ID');
        return;
      }

      try {
        localStream = await navigator.mediaDevices.getUserMedia({
         video: true,
         audio: true
       });

       if (localVideo) {
         localVideo.srcObject = localStream;
       }

       peerConnection = new RTCPeerConnection(configuration);

       if (localStream && peerConnection) {
         localStream.getTracks().forEach(track => {
           peerConnection?.addTrack(track, localStream);
         });

         peerConnection.ontrack = (event) => {
           if (remoteVideo && event.streams[0]) {
             remoteVideo.srcObject = event.streams[0];
           }
         };

          peerConnection.onicecandidate = (event: any) => {
           if (event.candidate) {
             sendSignalingMessage('candidate', event.candidate);
           }
         };

         const token = localStorage.getItem('auth_token');
         const currentUser = $user;
         const userId = currentUser?.id || 'anonymous';

         const response = await fetch(`/api/webrtc/room/${roomId}/join`, {
           method: 'POST',
           headers: {
             'Content-Type': 'application/json',
             'Authorization': token ? `Bearer ${token}` : ''
           },
           body: JSON.stringify({ userId: userId })
         });

          const roomData = await response.json() as { offers?: Record<string, { offer: RTCSessionDescriptionInit }>; participants?: typeof participants };

          if (roomData.offers) {
              for (const [offerUserId, offerData] of Object.entries(roomData.offers) as [string, { offer: RTCSessionDescriptionInit }][]) {
                const offer = new RTCSessionDescription(offerData.offer);
                await peerConnection.setRemoteDescription(offer);

               const answer = await peerConnection.createAnswer();
               await peerConnection.setLocalDescription(answer);

               await sendSignalingMessage('answer', answer);
             }
           }

         isInCall = true;
         isConnected = true;
         participants = roomData.participants || [];
       }
     } catch (error) {
       console.error('Error joining call:', error);
       alert('Failed to join call: ' + (error as Error).message);
     }
    }

    async function sendSignalingMessage(type: string, data: unknown): Promise<void> {
      const token = localStorage.getItem('auth_token');
      const currentUser = $user;
      const userId = currentUser?.id || 'anonymous';

      const endpoint = `/api/webrtc/room/${roomId}/${type}`;
      await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': token ? `Bearer ${token}` : ''
        },
        body: JSON.stringify({
          userId: userId,
          [type]: data
        })
      });
    }

   function handleCallEvent(data: unknown) {
     if (data && typeof data === 'object' && 'type' in data) {
       const eventData = data as { type: string; offer?: RTCSessionDescriptionInit; answer?: RTCSessionDescriptionInit; candidate?: RTCIceCandidateInit };
       if (eventData.type === 'offer' && peerConnection) {
         handleOffer(eventData.offer);
       } else if (eventData.type === 'answer' && peerConnection) {
         handleAnswer(eventData.answer);
       } else if (eventData.type === 'candidate' && peerConnection) {
         handleCandidate(eventData.candidate);
       }
     }
   }

   async function handleOffer(offer: RTCSessionDescriptionInit | undefined) {
     try {
       if (peerConnection && offer) {
         await peerConnection.setRemoteDescription(new RTCSessionDescription(offer));
         const answer = await peerConnection.createAnswer();
         await peerConnection.setLocalDescription(answer);
         await sendSignalingMessage('answer', answer);
       }
     } catch (error) {
       console.error('Error handling offer:', error);
     }
   }

   async function handleAnswer(answer: RTCSessionDescriptionInit | undefined) {
     try {
       if (peerConnection && answer) {
         await peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
       }
     } catch (error) {
       console.error('Error handling answer:', error);
     }
   }

   async function handleCandidate(candidate: RTCIceCandidateInit | undefined) {
     try {
       if (peerConnection && candidate) {
         await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
       }
     } catch (error) {
       console.error('Error handling candidate:', error);
     }
   }

    function endCall(): void {
      if (localStream) {
        for (const track of localStream.getTracks()) {
          track.stop();
        }
      }
     if (peerConnection) {
       peerConnection.close();
     }
     if (localVideo) {
       localVideo.srcObject = null;
     }
     if (remoteVideo) {
       remoteVideo.srcObject = null;
     }

     isInCall = false;
     isConnected = false;
     participants = [];

     const token = localStorage.getItem('auth_token');
     const currentUser = $user;
     const userId = currentUser?.id || 'anonymous';

     fetch(`/api/webrtc/room/${roomId}/leave`, {
       method: 'POST',
       headers: {
         'Content-Type': 'application/json',
         'Authorization': token ? `Bearer ${token}` : ''
       },
       body: JSON.stringify({ userId: userId })
     });
   }

    function toggleAudio(): void {
      if (localStream) {
        const audioTrack = localStream.getAudioTracks()[0];
        if (audioTrack) {
          audioTrack.enabled = !audioTrack.enabled;
        }
      }
    }

    function toggleVideo(): void {
      if (localStream) {
        const videoTrack = localStream.getVideoTracks()[0];
        if (videoTrack) {
          videoTrack.enabled = !videoTrack.enabled;
          isVideoEnabled = videoTrack.enabled;
        }
      }
    }

    async function toggleScreenShare(): Promise<void> {
     try {
       if (isScreenSharing) {
         if (localStream) {
           const screenTrack = localStream.getVideoTracks().find(track => track.label.includes('screen'));
           if (screenTrack) {
             screenTrack.stop();
             localStream.removeTrack(screenTrack);
           }
         }
          const cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
          const cameraTrack = cameraStream.getVideoTracks()[0];
          if (localStream && peerConnection) {
            const sender = peerConnection.getTransceivers().find(t => t.sender.track && t.sender.track.kind === 'video')?.sender;
            if (sender) {
              await sender.replaceTrack(cameraTrack);
            }
            localStream.addTrack(cameraTrack);
          }
         isScreenSharing = false;
       } else {
          const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
          const screenTrack = screenStream.getVideoTracks()[0];
          if (localStream && peerConnection) {
            const sender = peerConnection.getTransceivers().find(t => t.sender.track && t.sender.track.kind === 'video')?.sender;
            if (sender) {
              await sender.replaceTrack(screenTrack);
            }
            localStream.addTrack(screenTrack);
          }
         isScreenSharing = true;

         screenTrack.onended = () => {
           toggleScreenShare();
         };
       }
     } catch (error) {
       console.error('Error toggling screen share:', error);
       alert('Failed to toggle screen sharing: ' + (error as Error).message);
     }
   }

    function toggleRecording(): void {
      isRecording = !isRecording;
      if (isRecording) {
        console.log('Started recording');
      } else {
        console.log('Stopped recording');
      }
    }

    function createBreakoutRoom(): void {
      const roomName = prompt('Enter breakout room name:');
      if (roomName) {
        const room = {
          id: `room_${Date.now()}`,
          name: roomName,
          participants: []
        };
        breakoutRooms = [...breakoutRooms, room];
      }
    }

    function joinBreakoutRoom(roomId: string): void {
      currentRoomId = roomId;
      console.log('Joined breakout room:', roomId);
    }

    function leaveBreakoutRoom(): void {
      currentRoomId = null;
      console.log('Left breakout room');
    }

   let durationInterval: ReturnType<typeof setInterval> | undefined;
   $: if (isInCall) {
     durationInterval = setInterval(() => {
       callDuration += 1;
     }, 1000);
   } else {
     if (durationInterval) {
       clearInterval(durationInterval);
       callDuration = 0;
     }
   }

   function formatDuration(seconds: number) {
     const mins = Math.floor(seconds / 60);
     const secs = seconds % 60;
     return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
   }
</script>

 <main class="calls-container">
   <div class="call-controls">
     <input
       type="text"
       bind:value={roomId}
       placeholder="Enter room ID"
       disabled={isInCall}
     />

     {#if !isInCall}
       <button onclick={startCall}>Start Call</button>
       <button onclick={joinCall}>Join Call</button>
     {:else}
       <div class="call-info">
         <div class="call-duration">Duration: {formatDuration(callDuration)}</div>
         <div class="call-quality quality-{callQuality}">Quality: {callQuality}</div>
         {#if isRecording}
           <div class="recording-indicator">🔴 Recording</div>
         {/if}
       </div>

       <div class="call-actions">
         <button onclick={toggleAudio} class={isAudioEnabled ? 'active' : ''}>
           {isAudioEnabled ? '🔊' : '🔇'} Audio
         </button>
         <button onclick={toggleVideo} class={isVideoEnabled ? 'active' : ''}>
           {isVideoEnabled ? '📹' : '📷'} Video
         </button>
         <button onclick={toggleScreenShare} class={isScreenSharing ? 'active' : ''}>
           🖥️ Share
         </button>
         <button onclick={toggleRecording} class={isRecording ? 'recording' : ''}>
           {isRecording ? '⏹️' : '⏺️'} Record
         </button>
         <button onclick={endCall} class="end-call">📞 End Call</button>
       </div>

       <!-- Breakout Rooms -->
       <div class="breakout-controls">
         <button onclick={createBreakoutRoom}>➕ Create Room</button>
         {#if currentRoomId}
           <button onclick={leaveBreakoutRoom}>⬅️ Main Room</button>
         {:else}
           <div class="breakout-rooms">
             {#each breakoutRooms as room}
               <button onclick={() => joinBreakoutRoom(room.id)}>
                 {room.name} ({room.participants.length})
               </button>
             {/each}
           </div>
         {/if}
       </div>
     {/if}
   </div>

  {#if isInCall}
    <div class="video-container">
      <div class="video-wrapper">
        <video bind:this={localVideo} autoplay muted class="local-video"></video>
        <div class="video-label">You</div>
      </div>

      <div class="video-wrapper">
        <video bind:this={remoteVideo} autoplay class="remote-video"></video>
        <div class="video-label">Remote</div>
      </div>
    </div>

     {#if participants.length > 0}
       <div class="participants-section">
         <h3>Participants ({participants.length})</h3>
         <div class="participants-grid">
           {#each participants as participant}
             <div class="participant-card">
               <div class="participant-avatar">
                 {participant.name ? participant.name.charAt(0).toUpperCase() : '?'}
               </div>
               <div class="participant-info">
                 <div class="participant-name">{participant.name || 'Unknown'}</div>
                 <div class="participant-status">
                   {#if participant.audioEnabled !== false}
                     🔊 {#if participant.speaking}🎤{/if}
                   {:else}
                     🔇
                   {/if}
                   {#if participant.videoEnabled !== false}
                     📹
                   {:else}
                     📷
                   {/if}
                   {#if participant.screenSharing}
                     🖥️
                   {/if}
                 </div>
               </div>
               {#if participant.isHost}
                 <div class="host-badge">👑</div>
               {/if}
             </div>
           {/each}
         </div>
       </div>
     {/if}
  {/if}
</main>

<style>
  .calls-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  .call-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 2rem;
  }

  .call-controls input {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    flex: 1;
  }

   .call-info {
     display: flex;
     gap: 1rem;
     align-items: center;
     margin-bottom: 1rem;
     padding: 0.5rem;
     background: rgba(0, 0, 0, 0.5);
     border-radius: 8px;
   }

   .call-duration {
     color: #00ffff;
     font-family: 'Courier New', monospace;
     font-weight: bold;
   }

   .call-quality {
     padding: 0.25rem 0.5rem;
     border-radius: 4px;
     font-size: 0.9rem;
     font-weight: bold;
   }

   .quality-good {
     background: #28a745;
     color: white;
   }

   .quality-fair {
     background: #ffc107;
     color: black;
   }

   .quality-poor {
     background: #dc3545;
     color: white;
   }

   .recording-indicator {
     color: #dc3545;
     font-weight: bold;
     animation: pulse 1s infinite;
   }

   @keyframes pulse {
     0%, 100% { opacity: 1; }
     50% { opacity: 0.5; }
   }

   .call-actions {
     display: flex;
     gap: 0.5rem;
     flex-wrap: wrap;
     margin-bottom: 1rem;
   }

   .call-controls button {
     padding: 0.75rem 1rem;
     border: 1px solid #00ffff;
     border-radius: 8px;
     background: rgba(0, 255, 255, 0.1);
     color: #00ffff;
     cursor: pointer;
     transition: all 0.3s ease;
     font-weight: bold;
     font-family: 'Courier New', monospace;
     display: flex;
     align-items: center;
     gap: 0.5rem;
   }

   .call-controls button:hover {
     background: rgba(0, 255, 255, 0.2);
     box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
   }

   .call-controls button.active {
     background: linear-gradient(45deg, #00ffff, #ff00ff);
     color: #000000;
   }

   .call-controls button.recording {
     background: #dc3545;
     color: white;
     animation: pulse 1s infinite;
   }

  .call-controls .end-call {
    background: #dc3545;
  }

  .call-controls .end-call:hover {
    background: #c82333;
  }

  .video-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .video-wrapper {
    position: relative;
    background: #000;
    border-radius: 8px;
    overflow: hidden;
  }

  .local-video, .remote-video {
    width: 100%;
    height: 300px;
    object-fit: cover;
  }

  .video-label {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
  }

  .participants {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
  }

  .participants h3 {
    margin-top: 0;
    margin-bottom: 1rem;
  }

  .participants ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .participants li {
    padding: 0.25rem 0;
    border-bottom: 1px solid #eee;
  }

  .participants li:last-child {
    border-bottom: none;
  }

  .participants-section h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: #00ffff;
  }

  .participants-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }

  .participant-card {
    background: rgba(30, 30, 30, 0.8);
    border: 1px solid #ff00ff;
    border-radius: 10px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    position: relative;
  }

  .participant-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 15px rgba(255, 0, 255, 0.4);
    border-color: #00ffff;
  }

  .participant-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(45deg, #00ffff, #ff00ff);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #000000;
    font-weight: bold;
    font-size: 1.2rem;
  }

  .participant-info {
    flex: 1;
  }

  .participant-name {
    color: #ffffff;
    font-weight: 500;
    margin-bottom: 0.25rem;
  }

  .participant-status {
    display: flex;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #00ffff;
  }

  .host-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 1.2rem;
  }

  .call-info {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1rem;
    padding: 0.5rem;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 8px;
  }

  .call-duration {
    color: #00ffff;
    font-family: 'Courier New', monospace;
    font-weight: bold;
  }

  .call-quality {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.9rem;
    font-weight: bold;
  }

  .quality-good {
    background: #28a745;
    color: white;
  }

  .quality-fair {
    background: #ffc107;
    color: black;
  }

  .quality-poor {
    background: #dc3545;
    color: white;
  }

  .recording-indicator {
    color: #dc3545;
    font-weight: bold;
    animation: pulse 1s infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }

  .call-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
  }

  .call-controls button {
    padding: 0.75rem 1rem;
    border: 1px solid #00ffff;
    border-radius: 8px;
    background: rgba(0, 255, 255, 0.1);
    color: #00ffff;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: bold;
    font-family: 'Courier New', monospace;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .call-controls button:hover {
    background: rgba(0, 255, 255, 0.2);
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
  }

  .call-controls button.active {
    background: linear-gradient(45deg, #00ffff, #ff00ff);
    color: #000000;
  }

  .call-controls button.recording {
    background: #dc3545;
    color: white;
    animation: pulse 1s infinite;
  }

  .end-call {
    background: #dc3545 !important;
    border-color: #dc3545 !important;
  }

  .end-call:hover {
    background: #c82333 !important;
    box-shadow: 0 0 10px rgba(220, 53, 69, 0.5) !important;
  }

  .breakout-controls {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(0, 255, 255, 0.2);
  }

  .breakout-rooms {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 0.5rem;
  }

  .breakout-rooms button {
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
  }
</style>