<script>
  let userPoints = 2500;
  let badges = [
    { id: 1, name: 'Ngườí dùng mới', icon: '🌟', earned: true },
    { id: 2, name: 'VIP Member', icon: '👑', earned: true },
    { id: 3, name: 'Super Active', icon: '🔥', earned: false }
  ];
  
  let rewards = [
    { id: 1, name: 'Voucher 100k', points: 1000, available: true },
    { id: 2, name: 'Premium 1 tháng', points: 2000, available: true },
    { id: 3, name: 'Quà tặng đặc biệt', points: 5000, available: false }
  ];
</script>

<div class="rewards">
  <h1>🎁 Trung tâm phần thưởng</h1>
  
  <div class="points-card">
    <h2>Điểm của bạn</h2>
    <p class="points">{userPoints.toLocaleString()} ⭐</p>
  </div>
  
  <div class="badges-section">
    <h3>Huy hiệu</h3>
    <div class="badges-grid">
      {#each badges as badge}
        <div class="badge-item {badge.earned ? 'earned' : 'locked'}">
          <span class="icon">{badge.icon}</span>
          <p>{badge.name}</p>
        </div>
      {/each}
    </div>
  </div>
  
  <div class="redeem-section">
    <h3>Đổi quà</h3>
    {#each rewards as reward}
      <div class="reward-item">
        <span>{reward.name}</span>
        <button disabled={!reward.available || userPoints < reward.points}>
          {reward.points.toLocaleString()} điểm
        </button>
      </div>
    {/each}
  </div>
</div>

<style>
  .rewards { padding: 20px; max-width: 600px; margin: 0 auto; }
  .points-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px; border-radius: 12px; text-align: center; margin-bottom: 24px; }
  .points { font-size: 48px; font-weight: bold; margin: 0; }
  .badges-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
  .badge-item { text-align: center; padding: 16px; border-radius: 12px; }
  .badge-item.earned { background: #fef3c7; }
  .badge-item.locked { background: #f3f4f6; opacity: 0.5; }
  .icon { font-size: 40px; }
  .reward-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: white; margin: 8px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  button { padding: 8px 16px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; }
  button:disabled { background: #d1d5db; cursor: not-allowed; }
</style>
