<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount } from 'svelte';

  const dispatch = createEventDispatcher();

  let currentTheme = 'auto';
  let themes = [
    { id: 'light', name: 'Light', icon: '☀️' },
    { id: 'dark', name: 'Dark', icon: '🌙' },
    { id: 'auto', name: 'Auto', icon: '🖥️' },
    { id: 'cyberpunk', name: 'Cyberpunk', icon: '🤖' }
  ];

  onMount(() => {
    // Load saved theme
    const saved = localStorage.getItem('theme');
    if (saved && themes.some(t => t.id === saved)) {
      currentTheme = saved;
    }
    applyTheme(currentTheme);
  });

  /**
   * @param {string} themeId
   */
  function setTheme(themeId) {
    currentTheme = themeId;
    localStorage.setItem('theme', themeId);
    applyTheme(themeId);
    dispatch('themeChange', { theme: themeId });
  }

  /**
   * @param {string} themeId
   */
  function applyTheme(themeId) {
    const root = document.documentElement;

    // Remove all theme classes
    root.classList.remove('theme-light', 'theme-dark', 'theme-auto', 'theme-cyberpunk');

    // Add current theme class
    root.classList.add(`theme-${themeId}`);

    // Handle auto theme
    if (themeId === 'auto') {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      root.classList.add(prefersDark ? 'theme-dark' : 'theme-light');

      // Listen for changes
      const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
      mediaQuery.addEventListener('change', (e) => {
        root.classList.remove('theme-light', 'theme-dark');
        root.classList.add(e.matches ? 'theme-dark' : 'theme-light');
      });
    }

    // Apply CSS custom properties for themes
    applyThemeStyles(themeId);
  }

  /**
   * @param {string} themeId
   */
  function applyThemeStyles(themeId) {
    const root = document.documentElement;

    const themeStyles = {
      light: {
        '--bg-primary': '#ffffff',
        '--bg-secondary': '#f8fafc',
        '--text-primary': '#1f2937',
        '--text-secondary': '#6b7280',
        '--border-color': '#e5e7eb',
        '--accent-color': '#3b82f6',
        '--success-color': '#10b981',
        '--error-color': '#ef4444'
      },
      dark: {
        '--bg-primary': '#1f2937',
        '--bg-secondary': '#111827',
        '--text-primary': '#f9fafb',
        '--text-secondary': '#9ca3af',
        '--border-color': '#374151',
        '--accent-color': '#60a5fa',
        '--success-color': '#34d399',
        '--error-color': '#f87171'
      },
      cyberpunk: {
        '--bg-primary': '#0a0a0a',
        '--bg-secondary': '#1a1a1a',
        '--text-primary': '#00ff88',
        '--text-secondary': '#00ccaa',
        '--border-color': '#00ff88',
        '--accent-color': '#ff0080',
        '--success-color': '#00ff88',
        '--error-color': '#ff4444'
      }
    };

    const styles = themeStyles[/** @type {keyof typeof themeStyles} */ (themeId)] || themeStyles.light;
    Object.entries(styles).forEach(([property, value]) => {
      root.style.setProperty(property, value as string);
    });
  }
</script>

<div class="theme-switcher">
  <div class="theme-grid">
    {#each themes as theme}
      <button
        class="theme-option"
        class:active={currentTheme === theme.id}
        onclick={() => setTheme(theme.id)}
        aria-label="Switch to {theme.name} theme"
      >
        <div class="theme-icon">{theme.icon}</div>
        <div class="theme-name">{theme.name}</div>
      </button>
    {/each}
  </div>
</div>

<style>
  .theme-switcher {
    padding: 1rem;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  .theme-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
  }

  .theme-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 2px solid transparent;
    border-radius: 0.5rem;
    background: var(--bg-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
  }

  .theme-option:hover {
    border-color: var(--accent-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
  }

  .theme-option.active {
    border-color: var(--accent-color);
    background: var(--accent-color);
    color: white;
  }

  .theme-option.active .theme-icon {
    transform: scale(1.1);
  }

  .theme-icon {
    font-size: 2rem;
    transition: transform 0.2s ease;
  }

  .theme-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
  }

  .theme-option.active .theme-name {
    color: white;
  }

  /* Cyberpunk special effects */
  :global(.theme-cyberpunk) .theme-option.active {
    box-shadow: 0 0 20px var(--accent-color);
    animation: cyberpunk-glow 2s ease-in-out infinite alternate;
  }

  @keyframes cyberpunk-glow {
    from {
      box-shadow: 0 0 20px var(--accent-color);
    }
    to {
      box-shadow: 0 0 30px var(--accent-color), 0 0 40px var(--accent-color);
    }
  }

  /* Mobile responsive */
  @media (max-width: 640px) {
    .theme-grid {
      grid-template-columns: 1fr;
    }

    .theme-option {
      flex-direction: row;
      justify-content: flex-start;
      text-align: left;
    }

    .theme-icon {
      font-size: 1.5rem;
    }
  }
</style>