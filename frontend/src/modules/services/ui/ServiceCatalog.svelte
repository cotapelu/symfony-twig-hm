<script lang="ts">
  import { onMount } from 'svelte';
  import { servicesStore, loadServices, bookService, hasBooked } from '../state/servicesStore';
  import type { Service } from '../contracts/servicesTypes';

  let services: Service[] = [];
  let isLoading = false;
  let errorMessage: string | null = null;
  let pendingBookings = new Set<string>();

  servicesStore.subscribe(state => {
    services = state.services;
    isLoading = state.loading;
    errorMessage = state.error;
  });

  onMount(() => {
    loadServices();
  });

  async function handleBookService(service: Service) {
    if (pendingBookings.has(service.id)) return;
    
    pendingBookings.add(service.id);
    servicesStore.update(s => s);
    
    try {
      await bookService({ serviceId: service.id });
      alert(`Đã đặt dịch vụ: ${service.name}`);
} catch (e) {
  alert((e as Error).message);
}
  }

  function isBooked(serviceId: string): boolean {
    return hasBooked(serviceId);
  }
</script>

<div class="services">
  <h1>🛎️ Dịch vụ</h1>
  
  {#if isLoading}
    <div class="loading">Đang tải...</div>
  {/if}

  {#if errorMessage}
    <div class="error">{errorMessage}</div>
  {/if}

  <div class="services-grid">
    {#each services as service}
      <div class="service-card">
        <div class="service-icon">{service.image}</div>
        <h3>{service.name}</h3>
        <p class="price">{service.price.toLocaleString()} VND</p>
        <p class="duration">⏱️ {service.duration}</p>
        <p class="rating">⭐ {service.rating}</p>
        <button 
          on:click={() => handleBookService(service)}
          disabled={isBooked(service.id) || pendingBookings.has(service.id)}
        >
          {#if isBooked(service.id)}
            Đã đặt
          {:else if pendingBookings.has(service.id)}
            Đang xử lý...
          {:else}
            Đặt dịch vụ
          {/if}
        </button>
      </div>
    {/each}
  </div>

  {#if services.length === 0 && !isLoading}
    <div class="empty">Chưa có dịch vụ nào</div>
  {/if}
</div>

<style>
  .services { padding: 20px; }
  .services-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
  .service-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
  .service-icon { font-size: 48px; margin-bottom: 12px; }
  .price { font-size: 20px; font-weight: bold; color: #10b981; }
  button { width: 100%; margin-top: 12px; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  button:disabled { background: #9ca3af; cursor: not-allowed; }
  .loading, .error, .empty { text-align: center; padding: 20px; }
  .error { color: #ef4444; }
</style>