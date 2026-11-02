<script>
  let restaurants = [
    { id: 1, name: 'Nhà hàng A', cuisine: 'Việt Nam', rating: 4.5, time: '30-45 min' },
    { id: 2, name: 'Quán B', cuisine: 'Hàn Quốc', rating: 4.2, time: '25-35 min' }
  ];
  let cart = [];
  
  function addToCart(item) {
    cart = [...cart, item];
  }
</script>

<div class="food-delivery">
  <h2>🍔 Giao đồ ăn</h2>
  <div class="restaurants">
    {#each restaurants as r}
      <div class="restaurant-card">
        <h3>{r.name}</h3>
        <p>{r.cuisine} ⭐ {r.rating} 🕒 {r.time}</p>
        <button on:click={() => addToCart(r)}>Thêm vào giỏ</button>
      </div>
    {/each}
  </div>
  {#if cart.length > 0}
    <div class="cart">Giỏ hàng: {cart.length} món</div>
  {/if}
</div>

<style>
  .food-delivery { padding: 20px; }
  .restaurant-card { border: 1px solid #ddd; padding: 16px; margin: 8px 0; border-radius: 8px; }
  button { background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
  .cart { position: fixed; bottom: 20px; right: 20px; background: #10b981; color: white; padding: 12px 24px; border-radius: 24px; }
</style>
