import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.community.app',
  appName: 'CommunityApp',
  webDir: 'build',
  server: {
    androidScheme: 'https',
    cleartext: true
  },
  ios: {
    scheme: 'CommunityApp',
    contentInset: 'automatic',
    scrollEnabled: true
  },
  android: {
    allowMixedContent: true,
    captureInput: true,
    webContentsDebuggingEnabled: true
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      backgroundColor: '#ffffff',
      showSpinner: false
    },
    StatusBar: {
      style: 'dark',
      backgroundColor: '#ffffff'
    },
    Keyboard: {
      resize: 'body',
      style: 'dark',
      resizeOnFullScreen: true
    },
    Camera: {
      permissions: ['camera', 'photos']
    },
    Geolocation: {
      permissions: ['location']
    },
    PushNotifications: {
      presentationOptions: ['badge', 'sound', 'alert']
    },
    LocalNotifications: {
      smallIcon: 'ic_stat_icon',
      iconColor: '#1976D2',
      sound: 'beep.wav'
    },
    Share: {
      //
    },
    Filesystem: {
      //
    },
    Preferences: {
      group: 'CommunityApp'
    }
  }
};

export default config;