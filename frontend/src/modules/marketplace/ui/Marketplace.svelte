<script>
  let products = [
    { id: 1, name: 'iPhone 15 Pro', price: 25000000, seller: 'TechStore', rating: 4.8, image: '📱' },
    { id: 2, name: 'MacBook Air M3', price: 28000000, seller: 'AppleVN', rating: 4.9, image: '💻' },
    { id: 3, name: 'AirPods Pro', price: 4500000, seller: 'AudioWorld', rating: 4.7, image: '🎧' }
  ];
  
  let cart = [];
  
  function addToCart(product) {
    cart = [...cart, product];
  }
</script>

<div class="marketplace">
  <h1>🛒 Chợ trực tuyến</h1>
  
  <div class="products-grid">
    {#each products as product}
      <div class="product-card">
        <div class="product-image">{product.image}</div>
        <h3>{product.name}</h3>
        <p class="seller">🏪 {product.seller}</p>
        <p class="rating">⭐ {product.rating}</p>
        <p class="price">{product.price.toLocaleString()} VND</p>
        <button on:click={() => addToCart(product)}>Thêm vào giỏ</button>
      </div>
    {/each}
  </div>
  
  {#if cart.length > 0}
    <div class="cart-float">
      🛒 {cart.length} sản phẩm
    </div>
  {/if}
</div>

<style>
  .marketplace { padding: 20px; }
  .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
  .product-card { background: white; padding: 16px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  .product-image { font-size: 64px; text-align: center; margin-bottom: 12px; }
  .price { font-size: 20px; font-weight: bold; color: #ef4444; }
  button { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
  .cart-float { position: fixed; bottom: 20px; right: 20px; background: #10b981; color: white; padding: 16px 24px; border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
</style>
