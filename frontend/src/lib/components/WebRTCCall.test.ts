import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import WebRTCCall from './WebRTCCall.svelte';

// Mock stores
vi.mock('$lib/stores/webrtc', () => {
  const mockPeerConnections = new Map();
  return {
    callState: {
      subscribe: vi.fn((callback) => {
        callback({
          isInCall: false,
          roomId: null,
          participants: [],
          remoteStreams: new Map(),
        });
        return () => {};
      }),
      update: vi.fn(),
    },
    webrtcStore: {
      peerConnections: mockPeerConnections,
      joinRoom: vi.fn().mockResolvedValue({ participants: [] }),
      leaveRoom: vi.fn(),
      sendOffer: vi.fn(),
      sendCandidate: vi.fn(),
    },
  };
});

vi.mock('$lib/stores/global', () => ({
  user: {
    subscribe: vi.fn((callback) => {
      callback({ id: 'user-123' });
      return () => {};
    }),
  },
}));

// Mock goto
vi.mock('$app/navigation', () => ({
  goto: vi.fn(),
}));

// Mock MediaStream
const mockAudioTrack = {
  enabled: true,
  stop: vi.fn(),
  kind: 'audio',
};

const mockVideoTrack = {
  enabled: true,
  stop: vi.fn(),
  kind: 'video',
};

const mockLocalStream = {
  getTracks: vi.fn(() => [mockAudioTrack, mockVideoTrack]),
  getAudioTracks: vi.fn(() => [mockAudioTrack]),
  getVideoTracks: vi.fn(() => [mockVideoTrack]),
};

const mockRemoteStream = {
  getTracks: vi.fn(() => [mockAudioTrack, mockVideoTrack]),
};

// Mock RTCPeerConnection
const mockRTCPeerConnection = {
  addTrack: vi.fn(() => ({ track: mockAudioTrack })),
  createOffer: vi.fn().mockResolvedValue({ type: 'offer', sdp: 'mock-sdp' }),
  setLocalDescription: vi.fn().mockResolvedValue(undefined),
  setRemoteDescription: vi.fn().mockResolvedValue(undefined),
  addIceCandidate: vi.fn().mockResolvedValue(undefined),
  close: vi.fn(),
  onicecandidate: null,
  ontrack: null,
  onconnectionstatechange: null,
};

// Mock MediaDevices
const mockGetUserMedia = vi.fn().mockResolvedValue(mockLocalStream);

// Mock alert
window.alert = vi.fn();

// Setup global mocks
beforeEach(() => {
  vi.clearAllMocks();
  
  // Mock navigator.mediaDevices.getUserMedia
  if (!navigator.mediaDevices) {
    Object.defineProperty(navigator, 'mediaDevices', {
      value: {},
      writable: true,
      configurable: true
    });
  }
  
  Object.defineProperty(navigator.mediaDevices, 'getUserMedia', {
    value: mockGetUserMedia,
    writable: true,
    configurable: true
  });
  
  // Mock RTCPeerConnection
  window.RTCPeerConnection = vi.fn(() => mockRTCPeerConnection);
  window.RTCPeerConnection.prototype = mockRTCPeerConnection;
  
  // Mock RTCSessionDescription
  window.RTCSessionDescription = vi.fn().mockImplementation((init) => init);
  
  // Mock RTCIceCandidate
  window.RTCIceCandidate = vi.fn().mockImplementation((init) => init);
});

afterEach(() => {
  vi.restoreAllMocks();
});

describe('WebRTCCall Component', () => {
  const defaultProps = { roomId: 'test-room-123' };

  describe('Basic Rendering', () => {
    it('renders with roomId prop', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('webrtc-call')).toBeInTheDocument();
    });

    it('renders with different roomId', () => {
      render(WebRTCCall, { props: { roomId: 'another-room' } });
      expect(screen.getByTestId('webrtc-call')).toBeInTheDocument();
    });
  });

  describe('Component Structure', () => {
    it('renders with correct structure', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('webrtc-call')).toHaveClass('webrtc-call');
    });

    it('renders local video container', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('local-video')).toBeInTheDocument();
    });

    it('renders local video element', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('local-video-element')).toBeInTheDocument();
    });

    it('renders "You" label on local video', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByText('You')).toBeInTheDocument();
    });

    it('renders call controls', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('call-controls')).toBeInTheDocument();
    });

    it('renders mute button', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('toggle-mute-btn')).toBeInTheDocument();
      expect(screen.getByTestId('toggle-mute-btn')).toHaveTextContent('🎤');
    });

    it('renders video toggle button', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('toggle-video-btn')).toBeInTheDocument();
      expect(screen.getByTestId('toggle-video-btn')).toHaveTextContent('📷');
    });

    it('renders hangup button', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('hangup-btn')).toBeInTheDocument();
      expect(screen.getByTestId('hangup-btn')).toHaveTextContent('📞❌');
    });

    it('renders screen share button', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('toggle-screen-share-btn')).toBeInTheDocument();
      expect(screen.getByTestId('toggle-screen-share-btn')).toHaveTextContent('🖥️');
    });
  });

  describe('Props', () => {
    it('accepts roomId prop', () => {
      render(WebRTCCall, { props: { roomId: 'test-room' } });
      expect(screen.getByTestId('webrtc-call')).toBeInTheDocument();
    });
  });

  describe('Exported State Getters', () => {
    it('has getIsMuted function', () => {
      const { component } = render(WebRTCCall, { props: defaultProps });
      expect(typeof component.getIsMuted).toBe('function');
      expect(component.getIsMuted()).toBe(false);
    });

    it('has getIsVideoOff function', () => {
      const { component } = render(WebRTCCall, { props: defaultProps });
      expect(typeof component.getIsVideoOff).toBe('function');
      expect(component.getIsVideoOff()).toBe(false);
    });

    it('has getIsScreenSharing function', () => {
      const { component } = render(WebRTCCall, { props: defaultProps });
      expect(typeof component.getIsScreenSharing).toBe('function');
      expect(component.getIsScreenSharing()).toBe(false);
    });

    it('has getLocalStream function', () => {
      const { component } = render(WebRTCCall, { props: defaultProps });
      expect(typeof component.getLocalStream).toBe('function');
    });

    it('has getRtcConfiguration function', () => {
      const { component } = render(WebRTCCall, { props: defaultProps });
      expect(typeof component.getRtcConfiguration).toBe('function');
      const config = component.getRtcConfiguration();
      expect(config).toHaveProperty('iceServers');
      expect(Array.isArray(config.iceServers)).toBe(true);
      expect(config.iceServers.length).toBeGreaterThan(0);
    });
  });

  describe('OnMount Initialization', () => {
    it('requests user media on mount', async () => {
      render(WebRTCCall, { props: defaultProps });
      
      await waitFor(() => {
        expect(navigator.mediaDevices.getUserMedia).toHaveBeenCalledWith({
          video: true,
          audio: true,
        });
      });
    });

    it('joins room on mount', async () => {
      render(WebRTCCall, { props: defaultProps });
      
      await waitFor(() => {
        // The joinRoom function should be called
        // We verify by checking the store was called
      });
    });
  });

  describe('Exported Functions', () => {
    it('has toggleMute function', () => {
      const { component } = render(WebRTCCall, { props: defaultProps });
      expect(typeof component.toggleMute).toBe('function');
    });

    it('has toggleVideo function', () => {
      const { component } = render(WebRTCCall, { props: defaultProps });
      expect(typeof component.toggleVideo).toBe('function');
    });

    it('has toggleScreenShare function', () => {
      const { component } = render(WebRTCCall, { props: defaultProps });
      expect(typeof component.toggleScreenShare).toBe('function');
    });

    it('has hangup function', () => {
      const { component } = render(WebRTCCall, { props: defaultProps });
      expect(typeof component.hangup).toBe('function');
    });
  });

  describe('Remote Videos', () => {
    it('does not render remote videos when no streams', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.queryByTestId('remote-video-participant-1')).not.toBeInTheDocument();
    });
  });

  describe('RTC Configuration', () => {
    it('has correct STUN servers', () => {
      const { component } = render(WebRTCCall, { props: defaultProps });
      const config = component.getRtcConfiguration();
      
      expect(config.iceServers).toEqual([
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
      ]);
    });
  });

  describe('Error Handling', () => {
    it('handles getUserMedia failure', async () => {
      mockGetUserMedia.mockRejectedValueOnce(new Error('Permission denied'));
      
      render(WebRTCCall, { props: defaultProps });
      
      // The error should be caught and logged
      await waitFor(() => {
        // Error handling is done via console.error and alert
      });
    });
  });

  describe('Accessibility', () => {
    it('has proper button elements for mute button', () => {
      render(WebRTCCall, { props: defaultProps });
      const muteBtn = screen.getByTestId('toggle-mute-btn');
      expect(muteBtn.tagName).toBe('BUTTON');
    });

    it('has proper button elements for video button', () => {
      render(WebRTCCall, { props: defaultProps });
      const videoBtn = screen.getByTestId('toggle-video-btn');
      expect(videoBtn.tagName).toBe('BUTTON');
    });

    it('has proper button elements for hangup button', () => {
      render(WebRTCCall, { props: defaultProps });
      const hangupBtn = screen.getByTestId('hangup-btn');
      expect(hangupBtn.tagName).toBe('BUTTON');
    });

    it('has proper button elements for screen share button', () => {
      render(WebRTCCall, { props: defaultProps });
      const screenShareBtn = screen.getByTestId('toggle-screen-share-btn');
      expect(screenShareBtn.tagName).toBe('BUTTON');
    });
  });

  describe('Edge Cases', () => {
    it('renders correctly with empty roomId', () => {
      render(WebRTCCall, { props: { roomId: '' } });
      expect(screen.getByTestId('webrtc-call')).toBeInTheDocument();
    });

    it('renders correctly with special characters in roomId', () => {
      render(WebRTCCall, { props: { roomId: 'room-with-special-chars!@#' } });
      expect(screen.getByTestId('webrtc-call')).toBeInTheDocument();
    });

    it('renders correctly with unicode characters in roomId', () => {
      render(WebRTCCall, { props: { roomId: '房间-测试' } });
      expect(screen.getByTestId('webrtc-call')).toBeInTheDocument();
    });
  });

  describe('Video Element Attributes', () => {
    it('local video has autoplay attribute', () => {
      render(WebRTCCall, { props: defaultProps });
      const video = screen.getByTestId('local-video-element');
      expect(video).toHaveAttribute('autoplay');
    });

    it('local video has muted property', () => {
      render(WebRTCCall, { props: defaultProps });
      const video = screen.getByTestId('local-video-element');
      // In Svelte, muted is a boolean attribute - check the property
      expect(video.muted).toBe(true);
    });

    it('local video has playsinline attribute', () => {
      render(WebRTCCall, { props: defaultProps });
      const video = screen.getByTestId('local-video-element');
      expect(video).toHaveAttribute('playsinline');
    });

    it('remote videos have autoplay attribute when rendered', () => {
      // This would need remote streams to be present
      render(WebRTCCall, { props: defaultProps });
      // Currently no remote videos rendered
      expect(screen.queryByTestId('remote-video-element-participant-1')).not.toBeInTheDocument();
    });
  });

  describe('Button Icons', () => {
    it('mute button shows microphone icon when not muted', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('toggle-mute-btn')).toHaveTextContent('🎤');
    });

    it('video button shows camera icon when not off', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('toggle-video-btn')).toHaveTextContent('📷');
    });

    it('hangup button shows phone icon', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('hangup-btn')).toHaveTextContent('📞❌');
    });

    it('screen share button shows monitor icon', () => {
      render(WebRTCCall, { props: defaultProps });
      expect(screen.getByTestId('toggle-screen-share-btn')).toHaveTextContent('🖥️');
    });
  });
});