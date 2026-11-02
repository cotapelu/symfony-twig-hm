<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { onMount } from 'svelte';

  interface EmojiCategory {
    name: string;
    icon: string;
    emojis: string[];
  }

  interface EmojiCategories {
    [key: string]: EmojiCategory;
  }

  export let recentEmojis: string[] = [];
	export const onEmojiSelect: ((emoji: string) => void) | null = null;
  export let position: 'bottom' | 'top' | 'left' | 'right' = 'bottom';

  const dispatch = createEventDispatcher();

  const emojiCategories: EmojiCategories = {
    recent: {
      name: 'Recent',
      icon: '🕒',
      emojis: recentEmojis.slice(0, 20)
    },
    smileys: {
      name: 'Smileys & People',
      icon: '😀',
      emojis: [
        '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇',
        '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚',
        '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩',
        '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣',
        '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬',
        '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗',
        '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯',
        '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐',
        '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈',
        '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽', '👾'
      ]
    },
    animals: {
      name: 'Animals & Nature',
      icon: '🐶',
      emojis: [
        '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯',
        '🦁', '🐮', '🐷', '🐽', '🐸', '🐵', '🙈', '🙉', '🙊', '🐒',
        '🐔', '🐧', '🐦', '🐤', '🐣', '🐥', '🦆', '🦅', '🦉', '🦇',
        '🐺', '🐗', '🐴', '🦄', '🐝', '🐛', '🦋', '🐌', '🐞', '🐜',
        '🦗', '🕷️', '🦂', '🐢', '🐍', '🦎', '🦖', '🦕', '🐙', '🦑',
        '🦐', '🦞', '🦀', '🐡', '🐠', '🐟', '🐬', '🐳', '🐋', '🦈',
        '🐊', '🐅', '🐆', '🦓', '🦍', '🦧', '🐘', '🦛', '🦏', '🐪',
        '🐫', '🦒', '🦘', '🐃', '🐂', '🐄', '🐎', '🐖', '🐏', '🐑',
        '🦙', '🐐', '🦌', '🐕', '🐩', '🦮', '🐕‍🦺', '🐈', '🐈‍⬛', '🐓'
      ]
    },
    food: {
      name: 'Food & Drink',
      icon: '🍎',
      emojis: [
        '🍎', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🫐', '🍈', '🍒',
        '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦', '🥬',
        '🥒', '🌶️', '🫑', '🌽', '🥕', '🫒', '🧄', '🧅', '🥔', '🍠',
        '🥐', '🥖', '🍞', '🥨', '🥯', '🧀', '🥚', '🍳', '🧈', '🥞',
        '🧇', '🥓', '🥩', '🍗', '🍖', '🦴', '🌭', '🍔', '🍟', '🍕',
        '🫓', '🥙', '🌮', '🌯', '🫔', '🥗', '🥘', '🫕', '🍝', '🍜',
        '🍲', '🍛', '🍣', '🍱', '🥟', '🦪', '🍤', '🍙', '🍚', '🍘',
        '🍥', '🥠', '🥮', '🍢', '🍡', '🍧', '🍨', '🍦', '🥧', '🧁',
        '🍰', '🎂', '🍮', '🍭', '🍬', '🍫', '🍿', '🍩', '🍪', '🌰'
      ]
    },
    activities: {
      name: 'Activities',
      icon: '⚽',
      emojis: [
        '⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱',
        '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳',
        '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛷', '⛸️',
        '🥌', '🎿', '⛷️', '🏂', '🪂', '🏋️', '🤸', '⛹️', '🤺', '🤾',
        '🏌️', '🧘', '🏃', '🚶', '🧎', '🧍', '🤳', '💃', '🕺', '👯',
        '👨‍🦽', '👩‍🦽', '👨‍🦼', '👩‍🦼', '🧎‍♂️', '🧎‍♀️', '🏃‍♂️', '🏃‍♀️', '🚶‍♂️', '🚶‍♀️',
        '💃', '🕺', '👯‍♂️', '👯‍♀️', '🕴️', '👩‍💼', '👨‍💼', '👩‍🔬', '👨‍🔬', '👩‍🎓',
        '👨‍🎓', '👩‍🏫', '👨‍🏫', '👩‍⚖️', '👨‍⚖️', '👩‍🌾', '👨‍🌾', '👩‍🍳', '👨‍🍳', '👩‍🔧'
      ]
    },
    objects: {
      name: 'Objects',
      icon: '💡',
      emojis: [
        '💡', '🔦', '🕯️', '🪔', '🧯', '🛢️', '💸', '💵', '💴', '💶',
        '💷', '🪙', '💰', '💳', '💎', '⚖️', '🔧', '🔨', '⚒️', '🛠️',
        '⛏️', '🔩', '⚙️', '🪜', '🧰', '🔗', '⛓️', '🪝', '🧲', '🔮',
        '🪄', '🧿', '🕹️', '🖲️', '🪣', '🧺', '🗂️', '📅', '📇', '🗳️',
        '📋', '📌', '📍', '📎', '🖇️', '📏', '✂️', '🖊️', '🖌️', '🖍️',
        '📝', '✏️', '🔍', '🔎', '🔒', '🔓', '🔏', '🔐', '🔑', '🗝️',
        '🔨', '🪓', '⛏️', '⚒️', '🛠️', '🗡️', '⚔️', '🔫', '🪃', '🏹'
      ]
    },
    symbols: {
      name: 'Symbols',
      icon: '❤️',
      emojis: [
        '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔',
        '❤️‍🔥', '❤️‍🩹', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝',
        '💟', '☮️', '✝️', '☪️', '🕉️', '☸️', '✡️', '🔯', '🕎', '☯️',
        '☦️', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌', '♍', '♎',
        '♏', '♐', '♑', '♒', '♓', '🆔', '⚛️', '🉑', '☢️', '☣️',
        '📴', '📳', '🈶', '🈚', '🈸', '🈺', '🈷️', '✴️', '🆚', '💮',
        '🉐', '㊙️', '㊗️', '🈴', '🈵', '🈹', '🈲', '🅰️', '🅱️', '🆎'
      ]
    }
  };

  let activeCategory = 'smileys';
  let searchQuery = '';
  let filteredEmojis: string[] = [];

  $: {
    if (recentEmojis.length > 0) {
      emojiCategories.recent.emojis = recentEmojis.slice(0, 20);
    }

    if (searchQuery) {
      filteredEmojis = Object.values(emojiCategories)
        .flatMap(category => category.emojis)
        .filter(emoji => emoji.includes(searchQuery));
    } else {
      filteredEmojis = emojiCategories[activeCategory].emojis;
    }
  }

  function selectEmoji(emoji: string) {
    dispatch('select', { emoji });
    if (!recentEmojis.includes(emoji)) {
      recentEmojis = [emoji, ...recentEmojis.slice(0, 19)];
    }
  }

  function handleKeyDown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
      dispatch('close');
    }
  }
</script>

<svelte:window onkeydown={handleKeyDown} />

<div class="emoji-picker position-{position}">
  <!-- Search bar -->
  <div class="emoji-search">
    <input
      type="text"
      placeholder="Search emojis..."
      bind:value={searchQuery}
      class="search-input"
      aria-label="Search emojis"
    />
  </div>

  <!-- Category tabs -->
  {#if !searchQuery}
    <div class="emoji-categories" role="tablist">
      {#each Object.entries(emojiCategories) as [key, category]}
        <button
          class="category-tab {activeCategory === key ? 'active' : ''}"
          onclick={() => activeCategory = key}
          role="tab"
          aria-selected={activeCategory === key}
          aria-label={category.name}
        >
          <span class="category-icon">{category.icon}</span>
        </button>
      {/each}
    </div>
  {/if}

  <!-- Emoji grid -->
  <div class="emoji-grid" role="grid">
    {#each filteredEmojis as emoji, index}
      <button
        class="emoji-btn"
        onclick={() => selectEmoji(emoji)}
        aria-label={`Select ${emoji}`}
        title={emoji}
      >
        {emoji}
      </button>
    {/each}
  </div>

  {#if filteredEmojis.length === 0}
    <div class="no-results">
      No emojis found for "{searchQuery}"
    </div>
  {/if}
</div>

<style>
  .emoji-picker {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    width: 320px;
    max-height: 400px;
    display: flex;
    flex-direction: column;
    z-index: 1000;
  }

  .emoji-search {
    padding: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
  }

  .search-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    outline: none;
    transition: border-color 0.15s ease;
  }

  .search-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .emoji-categories {
    display: flex;
    padding: 0.5rem;
    gap: 0.25rem;
    border-bottom: 1px solid #f3f4f6;
  }

  .category-tab {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 0.375rem;
    background: transparent;
    cursor: pointer;
    transition: all 0.15s ease;
    font-size: 1.25rem;
  }

  .category-tab:hover {
    background: #f3f4f6;
  }

  .category-tab.active {
    background: #3b82f6;
    color: white;
  }

  .emoji-grid {
    padding: 0.75rem;
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
  }

  .emoji-btn {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 0.375rem;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transition: all 0.15s ease;
  }

  .emoji-btn:hover {
    background: #f3f4f6;
    transform: scale(1.1);
  }

  .no-results {
    padding: 2rem;
    text-align: center;
    color: #6b7280;
    font-size: 0.875rem;
  }

  /* Position variants */
  .position-top {
    position: absolute;
    bottom: 100%;
    left: 0;
  }

  .position-bottom {
    position: absolute;
    top: 100%;
    left: 0;
  }

  .position-left {
    position: absolute;
    right: 100%;
    top: 0;
  }

  .position-right {
    position: absolute;
    left: 100%;
    top: 0;
  }

  /* Dark theme support */
  @media (prefers-color-scheme: dark) {
    .emoji-picker {
      background: #1f2937;
      border-color: #374151;
    }

    .emoji-search {
      border-color: #374151;
    }

    .search-input {
      background: #1f2937;
      border-color: #374151;
      color: #f9fafb;
    }

    .search-input:focus {
      border-color: #3b82f6;
    }

    .emoji-categories {
      border-color: #374151;
    }

    .category-tab:hover {
      background: #374151;
    }

    .emoji-btn:hover {
      background: #374151;
    }

    .no-results {
      color: #9ca3af;
    }
  }

  /* Mobile responsive */
  @media (max-width: 640px) {
    .emoji-picker {
      width: 280px;
      max-height: 350px;
    }

    .emoji-grid {
      grid-template-columns: repeat(6, 1fr);
      padding: 0.5rem;
    }

    .emoji-btn {
      width: 32px;
      height: 32px;
      font-size: 1.125rem;
    }
  }
</style>
