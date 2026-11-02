  <script lang="ts">
     import { theme } from '$lib/stores/global';
    import Button from './atoms/Button.svelte';
    import Typography from './atoms/Typography.svelte';
    import { slide } from 'svelte/transition';

    let currentTheme: Theme;
    let showDropdown = false;
    let dropdownElement: HTMLElement;

   // Subscribe to theme changes
   theme.subscribe((value: Theme) => {
     currentTheme = value;
   });

   function toggleDropdown() {
     showDropdown = !showDropdown;
   }

   function setTheme(newTheme: Theme) {
      theme.set(newTheme);
     localStorage.setItem('theme', newTheme);
     document.documentElement.setAttribute('data-theme', newTheme);
     showDropdown = false;
   }

   function getThemeIcon(themeName: string) {
     switch (themeName) {
       case 'light':
         return '☀️';
       case 'dark':
         return '🌙';
       case 'cyberpunk':
         return '⚡';
       default:
         return '🎨';
     }
   }

   function getThemeName(themeName: string) {
     switch (themeName) {
       case 'light':
         return 'Light';
       case 'dark':
         return 'Dark';
       case 'cyberpunk':
         return 'Cyberpunk';
       default:
         return 'Auto';
     }
   }

   // Close dropdown when clicking outside
   function handleClickOutside(event: MouseEvent) {
     if (dropdownElement && !dropdownElement.contains(event.target as Node)) {
       showDropdown = false;
     }
   }

  type Theme = 'light' | 'dark' | 'cyberpunk';

  // Initialize theme on mount
  import { onMount } from 'svelte';
  onMount(() => {
    // Set initial theme
    const savedTheme = (localStorage.getItem('theme') || 'light') as Theme;
    theme.set(savedTheme);
    document.documentElement.setAttribute('data-theme', savedTheme);

    // Add click outside listener
    document.addEventListener('click', handleClickOutside);

    return () => {
      document.removeEventListener('click', handleClickOutside);
    };
  });
</script>

 <div class="theme-selector" bind:this={dropdownElement}>
   <Button
     variant="ghost"
     size="sm"
     className="theme-toggle"
     onclick={toggleDropdown}
   >
     <span class="theme-icon">{getThemeIcon(currentTheme)}</span>
     <Typography variant="body" className="theme-name">{getThemeName(currentTheme)}</Typography>
     <svg
       class="dropdown-arrow"
       class:rotated={showDropdown}
       width="12"
       height="12"
       viewBox="0 0 24 24"
       fill="none"
       stroke="currentColor"
       stroke-width="2"
     >
       <path d="M6 9l6 6 6-6"/>
     </svg>
   </Button>

   {#if showDropdown}
     <div class="theme-dropdown" in:slide={{ duration: 200 }}>
       <Button
         variant="ghost"
         className="theme-option {currentTheme === 'light' ? 'active' : ''}"
         onclick={() => setTheme('light')}
       >
         <span class="theme-icon">☀️</span>
         <Typography variant="body">Light</Typography>
         <Typography variant="caption" className="theme-desc">Clean and bright</Typography>
       </Button>

       <Button
         variant="ghost"
         className="theme-option {currentTheme === 'dark' ? 'active' : ''}"
         onclick={() => setTheme('dark')}
       >
         <span class="theme-icon">🌙</span>
         <Typography variant="body">Dark</Typography>
         <Typography variant="caption" className="theme-desc">Easy on eyes</Typography>
       </Button>

       <Button
         variant="ghost"
         className="theme-option {currentTheme === 'cyberpunk' ? 'active' : ''}"
         onclick={() => setTheme('cyberpunk')}
       >
         <span class="theme-icon">⚡</span>
         <Typography variant="body">Cyberpunk</Typography>
         <Typography variant="caption" className="theme-desc">Futuristic vibe</Typography>
       </Button>
     </div>
   {/if}
</div>

<style>
  .theme-selector {
    position: relative;
    display: inline-block;
  }

  .theme-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 1px solid var(--border-primary);
    border-radius: 8px;
    background: var(--bg-card);
    color: var(--text-primary);
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    font-weight: 500;
    box-shadow: var(--shadow-sm);
  }

  .theme-toggle:hover {
    background: var(--bg-hover);
    border-color: var(--border-focus);
    box-shadow: var(--shadow-md);
  }

  .theme-icon {
    font-size: 1.1rem;
  }

  .theme-name {
    flex: 1;
    text-align: left;
  }

  .dropdown-arrow {
    transition: transform 0.3s ease;
    opacity: 0.7;
  }

  .dropdown-arrow.rotated {
    transform: rotate(180deg);
  }

  .theme-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background: var(--bg-card);
    border: 1px solid var(--border-primary);
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    min-width: 200px;
    z-index: 1000;
    overflow: hidden;
  }

  .theme-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.75rem 1rem;
    border: none;
    background: none;
    color: var(--text-primary);
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    border-bottom: 1px solid var(--border-primary);
  }

  .theme-option:last-child {
    border-bottom: none;
  }

  .theme-option:hover {
    background: var(--bg-hover);
  }

  .theme-option.active {
    background: var(--accent-primary);
    color: var(--text-inverse);
  }

  .theme-option.active:hover {
    background: var(--hover-primary);
  }

  .theme-desc {
    opacity: 0.7;
    font-size: 0.8rem;
    font-weight: 400;
    margin-left: auto;
  }

  /* Cyberpunk theme special effects */
  :global([data-theme="cyberpunk"]) .theme-toggle {
    border-color: var(--border-accent);
    background: linear-gradient(135deg, rgba(0, 255, 255, 0.1), rgba(255, 0, 255, 0.1));
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
  }

  :global([data-theme="cyberpunk"]) .theme-toggle:hover {
    box-shadow: 0 0 20px rgba(0, 255, 255, 0.5);
    border-color: var(--accent-cyber);
  }

  :global([data-theme="cyberpunk"]) .theme-dropdown {
    border-color: var(--border-accent);
    box-shadow: 0 0 25px rgba(0, 255, 255, 0.4);
  }

  :global([data-theme="cyberpunk"]) .theme-option.active {
    background: var(--gradient-cyber);
    box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    .theme-dropdown {
      min-width: 180px;
      right: -20px;
    }

    .theme-toggle {
      padding: 0.5rem 0.75rem;
      font-size: 0.85rem;
    }

    .theme-option {
      padding: 0.6rem 0.8rem;
      gap: 0.5rem;
    }
  }
</style>