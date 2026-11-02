# Mobile App Development with Capacitor

## Overview
This project uses **Capacitor** to wrap the SvelteKit SPA as a native iOS and Android app.

## Structure
```
frontend/
├── ios/                    # iOS native project (Xcode)
│   ├── App/               # Xcode project
│   ├── fastlane/          # iOS CI/CD config
│   └── Podfile            # CocoaPods dependencies
├── android/               # Android native project (Gradle)
│   ├── app/               # Android app module
│   ├── fastlane/          # Android CI/CD config
│   └── build.gradle       # Gradle config
├── capacitor.config.ts    # Capacitor configuration
├── src/lib/services/native/  # Native services wrapper
│   ├── index.ts           # All Capacitor plugins wrapped
│   └── stores.ts          # Reactive Svelte 5 stores
├── src/lib/components/NativeDemo.svelte  # Demo component
├── src/routes/native-demo/  # Demo page route
└── package.json           # Mobile scripts added
```

## Prerequisites

### iOS Development
- macOS with Xcode 15+
- CocoaPods: `sudo gem install cocoapods`
- Apple Developer Account (for device testing/TestFlight)

### Android Development
- Android Studio (latest)
- Android SDK (API 34+)
- JDK 17+

## Quick Start

### 1. Build & Sync
```bash
cd frontend
npm run build:mobile   # Build web + sync to native platforms
```

### 2. Open in Native IDEs
```bash
# iOS - Opens Xcode
npm run cap:open:ios

# Android - Opens Android Studio
npm run cap:open:android
```

### 3. Run on Device/Simulator
```bash
# iOS Simulator
npm run cap:run:ios

# Android Emulator
npm run cap:run:android
```

## Available Scripts

| Script | Description |
|--------|-------------|
| `npm run build:mobile` | Build web app + sync to iOS/Android |
| `npm run cap:sync` | Sync web assets + update native plugins |
| `npm run cap:copy` | Copy web assets only |
| `npm run cap:update` | Update native platforms + plugins |
| `npm run cap:open:ios` | Open iOS project in Xcode |
| `npm run cap:open:android` | Open Android project in Android Studio |
| `npm run cap:run:ios` | Run on iOS simulator/device |
| `npm run cap:run:android` | Run on Android emulator/device |

## CI/CD with Fastlane

### iOS
```bash
cd frontend/ios/fastlane
fastlane beta      # Build & upload to TestFlight
fastlane firebase  # Build & upload to Firebase App Distribution
```

### Android
```bash
cd frontend/android/fastlane
fastlane internal  # Build & upload to Play Store (internal track)
fastlane firebase  # Build & upload to Firebase App Distribution
```

## Installed Capacitor Plugins (16 total)

| Plugin | Package | Description |
|--------|---------|-------------|
| **Camera** | `@capacitor/camera` | Take photos, pick from gallery |
| **Geolocation** | `@capacitor/geolocation` | GPS location, watch position |
| **Push Notifications** | `@capacitor/push-notifications` | FCM/APNs remote notifications |
| **Local Notifications** | `@capacitor/local-notifications` | Schedule local notifications |
| **Device** | `@capacitor/device` | Device info (model, OS, UUID, version) |
| **Network** | `@capacitor/network` | Online/offline status, connection type |
| **Filesystem** | `@capacitor/filesystem` | Read/write files, directories |
| **Share** | `@capacitor/share` | Native share sheet |
| **Haptics** | `@capacitor/haptics` | Vibration feedback (impact, notification) |
| **Clipboard** | `@capacitor/clipboard` | Copy/paste text |
| **App** | `@capacitor/app` | App lifecycle, deep links, back button |
| **Browser** | `@capacitor/browser` | In-app browser (SafariViewController/Chrome Custom Tab) |
| **Preferences** | `@capacitor/preferences` | Key-value storage (like localStorage but native) |
| **Splash Screen** | `@capacitor/splash-screen` | Launch screen |
| **Status Bar** | `@capacitor/status-bar` | Status bar styling |
| **Keyboard** | `@capacitor/keyboard` | Keyboard handling |

## Using Native Services (Recommended)

### Import Services
```typescript
// Direct plugin access (Promise-based)
import {
  camera,
  geolocation,
  pushNotifications,
  localNotifications,
  device,
  network,
  filesystem,
  share,
  haptics,
  clipboard,
  app,
  browser,
  preferences,
  native,
} from '$lib/services/native';

// Reactive Svelte 5 stores
import {
  networkStore,
  geolocationStore,
  deviceStore,
  appStateStore,
  preferencesStore,
  capabilitiesStore,
  isOnline,
  isNative,
  devicePlatform,
} from '$lib/services/native/stores';
```

### Camera Example
```typescript
// Take photo
const photo = await camera.takePhoto({ quality: 80 });
if (photo?.webPath) {
  imageSrc = photo.webPath; // Use directly in <img>
}

// Pick from gallery
const photo = await camera.pickFromGallery({ quality: 80 });

// Convert to base64 for upload
const base64 = await camera.photoToBase64(photo);

// Convert to blob for FormData
const blob = await camera.photoToBlob(photo);
```

### Geolocation Example
```typescript
// One-time location
const position = await geolocation.getCurrentPosition({
  enableHighAccuracy: true,
  timeout: 10000,
});

// Watch position (returns watch ID)
const watchId = await geolocation.watchPosition((pos, err) => {
  if (pos) console.log(pos.coords.latitude, pos.coords.longitude);
});

// Stop watching
await geolocation.clearWatch(watchId);
```

### Notifications Example
```typescript
// Local notifications
await localNotifications.requestPermissions();
await localNotifications.showNow('Title', 'Body', { id: Date.now() });
await localNotifications.schedule({
  title: 'Reminder',
  body: 'Don\'t forget!',
  id: 1,
  schedule: { at: new Date(Date.now() + 60000) }, // 1 min later
});

// Push notifications (FCM/APNs)
await pushNotifications.initialize();
pushNotifications.onRegistration((token) => {
  sendTokenToServer(token.value);
});
pushNotifications.onPushReceived((notification) => {
  showInAppAlert(notification);
});
```

### Reactive Stores (Svelte 5)
```svelte
<script>
  import { networkStore, geolocationStore, isOnline } from '$lib/services/native/stores';
</script>

<!-- Auto-updates when network changes -->
{#if $isOnline}
  <span class="online">🟢 Online</span>
{:else}
  <span class="offline">🔴 Offline</span>
{/}

<!-- Reactive geolocation -->
{#if $geolocationStore.position}
  <p>Lat: {$geolocationStore.position.coords.latitude}</p>
  <p>Lon: {$geolocationStore.position.coords.longitude}</p>
{/if}

<!-- Start/stop watching -->
<button onclick={() => $geolocationStore.watch()}>Start Watching</button>
<button onclick={() => $geolocationStore.stop()}>Stop</button>
```

### Haptics Example
```typescript
await haptics.light();      // Light tap
await haptics.medium();     // Medium tap
await haptics.heavy();      // Heavy tap
await haptics.success();    // Success notification
await haptics.warning();    // Warning notification
await haptics.error();      // Error notification
await haptics.selection();  // Selection change (pickers)
```

### Filesystem Example
```typescript
// Write file
await filesystem.writeFile({
  path: 'data.json',
  data: JSON.stringify({ key: 'value' }),
  directory: 'data',  // Data directory (app-specific)
});

// Read file
const content = await filesystem.readFile({ path: 'data.json', directory: 'data' });
const data = JSON.parse(content);

// Get file URI for sharing/upload
const uri = await filesystem.getUri({ path: 'data.json', directory: 'data' });
```

### App Lifecycle & Deep Links
```typescript
// App state changes
app.onStateChange(({ isActive, state }) => {
  if (!isActive) saveAppState();
});

// Deep links / Universal links
app.onUrlOpen(({ url, iosSourceApplication, androidIntent }) => {
  handleDeepLink(url);
});

// Back button (Android)
app.onBackButton(() => {
  if (canGoBack()) goBack();
  else app.exit();
});
```

### Preferences (Native Key-Value)
```typescript
// Simple values
await preferences.set('theme', 'dark');
const theme = await preferences.get('theme'); // 'dark'

// Objects (auto JSON)
await preferences.setObject('user', { name: 'John', id: 1 });
const user = await preferences.getObject<User>('user');

// Reactive store
preferencesStore.set('key', 'value');
preferencesStore.get('key');
```

## Configuration

### Capacitor Config (`capacitor.config.ts`)
```typescript
plugins: {
  Camera: { permissions: ['camera', 'photos'] },
  Geolocation: { permissions: ['location'] },
  PushNotifications: { presentationOptions: ['badge', 'sound', 'alert'] },
  LocalNotifications: { smallIcon: 'ic_stat_icon', iconColor: '#1976D2' },
  Preferences: { group: 'CommunityApp' },
  SplashScreen: { launchShowDuration: 2000, backgroundColor: '#ffffff' },
  StatusBar: { style: 'dark', backgroundColor: '#ffffff' },
  Keyboard: { resize: 'body', style: 'dark' },
}
```

### Bundle IDs
- **iOS**: `com.community.ios` (configured in Xcode)
- **Android**: `com.community.android` (configured in `android/app/build.gradle`)

## Platform-Specific Setup

### iOS Permissions (Info.plist)
Add to `ios/App/App/Info.plist`:
```xml
<key>NSCameraUsageDescription</key>
<string>App needs camera access to take photos</string>
<key>NSPhotoLibraryUsageDescription</key>
<string>App needs photo library access to select images</string>
<key>NSLocationWhenInUseUsageDescription</key>
<string>App needs location for nearby services</string>
<key>NSLocationAlwaysAndWhenInUseUsageDescription</key>
<string>App needs location for background tracking</string>
```

### Android Permissions (AndroidManifest.xml)
Add to `android/app/src/main/AndroidManifest.xml`:
```xml
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
<uses-permission android:name="android.permission.VIBRATE" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
```

### Android Network Security (res/xml/network_security_config.xml)
```xml
<?xml version="1.0" encoding="utf-8"?>
<network-security-config>
  <base-config cleartextTrafficPermitted="true">
    <trust-anchors>
      <certificates src="system" />
    </trust-anchors>
  </base-config>
  <domain-config cleartextTrafficPermitted="true">
    <domain includeSubdomains="true">localhost</domain>
    <domain includeSubdomains="true">10.0.2.2</domain>
    <domain includeSubdomains="true">192.168.x.x</domain>
  </domain-config>
</network-security-config>
```

Reference in `AndroidManifest.xml`:
```xml
<application
    android:networkSecurityConfig="@xml/network_security_config"
    ... >
```

## Demo Page
Visit `/native-demo` to test all native features:
```bash
npm run dev
# Open http://localhost:5173/native-demo
```

## Troubleshooting

### iOS Build Fails
```bash
cd frontend/ios/App
pod install --repo-update
# Then open in Xcode and build
```

### Android Build Fails
```bash
cd frontend/android
./gradlew clean
# Then open in Android Studio and sync
```

### Web Assets Not Updating
```bash
npm run build:mobile
# Or manually:
npm run build && npx cap copy
```

### Plugin Not Working on Web
All plugins check `Capacitor.isNativePlatform()` and throw informative errors on web.
Use `native.isNative()` to conditionally enable features.

### Push Notifications Not Received
1. Check `google-services.json` (Android) / `GoogleService-Info.plist` (iOS) in native projects
2. Verify FCM/APNs configuration in Firebase Console
3. Test with Firebase Console test message
4. Check `capacitor.config.ts` push notification config

## Adding More Plugins
```bash
# Search available plugins
npm search @capacitor/

# Install
npm install @capacitor/biometric-auth @capacitor/barcode-scanner

# Sync
npx cap sync
```

## App Store Deployment

### iOS (App Store Connect)
1. Open in Xcode: `npm run cap:open:ios`
2. Update version/build in Xcode
3. Archive → Distribute App → App Store Connect
4. Or use Fastlane: `cd ios/fastlane && fastlane beta`

### Android (Play Console)
1. Open in Android Studio: `npm run cap:open:android`
2. Generate signed AAB: Build → Generate Signed Bundle/APK
3. Or use Fastlane: `cd android/fastlane && fastlane internal`

## Resources
- [Capacitor Docs](https://capacitorjs.com/docs)
- [Capacitor Plugins](https://capacitorjs.com/docs/apis)
- [SvelteKit Adapter Static](https://kit.svelte.dev/docs/adapter-static)
- [Fastlane Docs](https://docs.fastlane.tools)
- [iOS Human Interface Guidelines](https://developer.apple.com/design/human-interface-guidelines/)
- [Android Material Design](https://m3.material.io/)