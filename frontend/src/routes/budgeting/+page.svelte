<script lang="ts">
  import { onMount } from 'svelte';

  interface BudgetCategory {
    id: number;
    name: string;
    type: string;
    color: string;
    description?: string;
  }

  interface Budget {
    id: number;
    category_id: number;
    amount: number;
    period: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
  }

  let categories: BudgetCategory[] = [];
  let budgets: Budget[] = [];
  let activeTab = 'budgets';
  let loading = false;
  let error = '';

  async function loadData() {
    loading = true;
    try {
      const [catRes, budRes] = await Promise.all([
        fetch('/api/budgeting/categories'),
        fetch('/api/budgeting/budgets')
      ]);
      const catData = await catRes.json();
      const budData = await budRes.json();
      categories = catData.data || [];
      budgets = budData.data || [];
    } catch (e) {
      error = 'Failed to load budget data';
    }
    loading = false;
  }

  onMount(loadData);

  $: totalBudget = budgets.reduce((sum, b) => sum + b.amount, 0);
  $: categoryNames = Object.fromEntries(categories.map(c => [c.id, c.name]));
</script>

<div class="budgeting-page">
  <header>
    <h1>💰 Budgeting</h1>
    <div class="tabs">
      <button class:active={activeTab === 'categories'} on:click={() => activeTab = 'categories'}>
        Categories
      </button>
      <button class:active={activeTab === 'budgets'} on:click={() => activeTab = 'budgets'}>
        Budgets
      </button>
    </div>
  </header>

  {#if loading}
    <div class="loading">Loading...</div>
  {:else if error}
    <div class="error">{error}</div>
  {:else if activeTab === 'categories'}
    <section class="categories">
      <div class="header-row">
        <h2>Categories</h2>
        <button class="primary">+ Add Category</button>
      </div>
      <div class="category-grid">
        {#each categories as category}
          <div class="category-card" style="border-left-color: {category.color}">
            <h3>{category.name}</h3>
            <span class="badge">{category.type}</span>
            {#if category.description}
              <p>{category.description}</p>
            {/if}
          </div>
        {/each}
      </div>
    </section>
  {:else}
    <section class="budgets">
      <div class="header-row">
        <h2>Budgets</h2>
        <button class="primary">+ Create Budget</button>
      </div>
      <div class="budget-summary">
        <div class="summary-card">
          <span class="label">Total Budgets</span>
          <span class="value">{budgets.length}</span>
        </div>
        <div class="summary-card">
          <span class="label">Total Amount</span>
          <span class="value">₫{totalBudget.toLocaleString()}</span>
        </div>
      </div>
      <div class="budget-list">
        {#each budgets as budget}
          <div class="budget-item">
            <div class="budget-info">
              <h4>{categoryNames[budget.category_id] || 'Category ' + budget.category_id}</h4>
              <span class="period">{budget.period}</span>
            </div>
            <div class="budget-amount">
              <span class="amount">₫{budget.amount.toLocaleString()}</span>
              <span class="status" class:active={budget.is_active}>
                {budget.is_active ? 'Active' : 'Inactive'}
              </span>
            </div>
          </div>
        {/each}
      </div>
    </section>
  {/if}
</div>

<style>
  .budgeting-page { padding: 20px; max-width: 800px; margin: 0 auto; }
  header { margin-bottom: 20px; }
  h1 { font-size: 1.8rem; margin-bottom: 15px; }
  .tabs { display: flex; gap: 10px; }
  .tabs button {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    cursor: pointer;
  }
  .tabs button.active { background: #007bff; color: white; border-color: #007bff; }
  .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
  .primary { background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
  .category-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
  .category-card {
    padding: 15px;
    background: white;
    border-radius: 8px;
    border-left: 4px solid;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  .badge { background: #e9ecef; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; }
  .budget-summary { display: flex; gap: 15px; margin-bottom: 20px; }
  .summary-card {
    flex: 1;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    text-align: center;
  }
  .summary-card .label { display: block; font-size: 0.9rem; opacity: 0.9; }
  .summary-card .value { font-size: 1.5rem; font-weight: bold; }
  .budget-list { display: flex; flex-direction: column; gap: 10px; }
  .budget-item {
    display: flex;
    justify-content: space-between;
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  .budget-info h4 { margin: 0 0 5px; }
  .period { color: #666; font-size: 0.9rem; }
  .budget-amount { text-align: right; }
  .amount { display: block; font-weight: bold; font-size: 1.1rem; }
  .status { font-size: 0.8rem; padding: 2px 8px; border-radius: 4px; background: #e9ecef; }
  .status.active { background: #d4edda; color: #155724; }
</style>
