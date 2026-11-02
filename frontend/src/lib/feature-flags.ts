/**
 * Feature Flag client
 * Fetches flags from the backend and caches in memory.
 */
export class FeatureFlagClient {
  private cache: Map<string, boolean> = new Map();
  private baseUrl: string;

  constructor(baseUrl = '/api/feature-flags') {
    this.baseUrl = baseUrl;
  }

  async isEnabled(name: string): Promise<boolean> {
    if (this.cache.has(name)) {
      return this.cache.get(name)!;
    }
    try {
      const res = await fetch(`${this.baseUrl}/${name}`);
      if (!res.ok) {
        console.warn(`Feature flag ${name} not found`);
        return false;
      }
      const data = await res.json();
      const enabled = !!data.enabled;
      this.cache.set(name, enabled);
      return enabled;
    } catch (e) {
      console.error('Error fetching feature flag', e);
      return false;
    }
  }

  // Preload multiple flags
  async preload(names: string[]): Promise<void> {
    const uncached = names.filter(n => !this.cache.has(n));
    if (uncached.length === 0) return;
    // fetch in parallel
    await Promise.all(uncached.map(async name => {
      try {
        const res = await fetch(`${this.baseUrl}/${name}`);
        if (res.ok) {
          const data = await res.json();
          this.cache.set(name, !!data.enabled);
        }
      } catch (e) {
        console.warn(`Failed to preload flag ${name}`, e);
      }
    }));
  }

  clearCache(): void {
    this.cache.clear();
  }
}

export const featureFlags = new FeatureFlagClient();
