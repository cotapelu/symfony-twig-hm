<script lang="ts">
  import { locale, type Locale, SUPPORTED_LOCALES } from '$lib/i18n';

  interface Language {
    code: Locale;
    name: string;
    flag: string;
  }

  const languages: Language[] = [
    { code: 'en', name: 'English', flag: '🇺🇸' },
    { code: 'vi', name: 'Tiếng Việt', flag: '🇻🇳' },
    { code: 'es', name: 'Español', flag: '🇪🇸' },
    { code: 'fr', name: 'Français', flag: '🇫🇷' }
  ];

  let showDropdown = false;
  let currentLanguage: Language;

  function selectLanguage(langCode: Locale) {
    locale.set(langCode);
    localStorage.setItem('preferred-language', langCode);
    showDropdown = false;
  }

  $: if (typeof window !== 'undefined') {
    const savedLang = localStorage.getItem('preferred-language') as Locale | null;
    if (savedLang && savedLang !== $locale && SUPPORTED_LOCALES[savedLang]) {
      locale.set(savedLang);
    }
  }

  $: currentLanguage = languages.find(lang => lang.code === $locale) || languages[0];
</script>

<div class="language-switcher">
  <button
    class="lang-btn"
    onclick={() => showDropdown = !showDropdown}
    aria-expanded={showDropdown}
    aria-label="Change language"
  >
    <span class="lang-flag">{currentLanguage.flag}</span>
    <span class="lang-name">{currentLanguage.name}</span>
    <span class="dropdown-arrow" class:open={showDropdown}>▼</span>
  </button>

  {#if showDropdown}
    <div class="lang-dropdown" role="menu">
      {#each languages as lang}
        <button
          class="lang-option {lang.code === $locale ? 'active' : ''}"
          onclick={() => selectLanguage(lang.code)}
          role="menuitem"
          aria-current={lang.code === $locale}
        >
          <span class="lang-flag">{lang.flag}</span>
          <span class="lang-name">{lang.name}</span>
          {#if lang.code === $locale}
            <span class="checkmark">✓</span>
          {/if}
        </button>
      {/each}
    </div>
  {/if}
</div>

<style>
  .language-switcher {
    position: relative;
    display: inline-block;
  }

  .lang-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: white;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.15s ease;
  }

  .lang-btn:hover {
    border-color: #9ca3af;
    background: #f9fafb;
  }

  .lang-btn:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .lang-flag {
    font-size: 1.25rem;
  }

  .lang-name {
    font-weight: 500;
  }

  .dropdown-arrow {
    font-size: 0.75rem;
    transition: transform 0.2s ease;
  }

  .dropdown-arrow.open {
    transform: rotate(180deg);
  }

  .lang-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    z-index: 1000;
    min-width: 180px;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    margin-top: 0.25rem;
    animation: slideDown 0.2s ease-out;
  }

  .lang-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.75rem 1rem;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.15s ease;
    text-align: left;
  }

  .lang-option:hover {
    background: #f3f4f6;
  }

  .lang-option.active {
    background: #eff6ff;
    color: #1d4ed8;
  }

  .lang-option:first-child {
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
  }

  .lang-option:last-child {
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
  }

  .checkmark {
    color: #10b981;
    font-weight: bold;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Dark theme support */
  @media (prefers-color-scheme: dark) {
    .lang-btn {
      background: #1f2937;
      border-color: #374151;
      color: #f9fafb;
    }

    .lang-btn:hover {
      background: #374151;
      border-color: #4b5563;
    }

    .lang-dropdown {
      background: #1f2937;
      border-color: #374151;
    }

    .lang-option:hover {
      background: #374151;
    }

    .lang-option.active {
      background: #1e3a8a;
      color: #dbeafe;
    }
  }

  /* Mobile responsive */
  @media (max-width: 640px) {
    .lang-dropdown {
      right: -1rem;
      min-width: 160px;
    }
  }
</style>
