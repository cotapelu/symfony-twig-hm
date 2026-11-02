<script>
  let bots = [
    { id: 1, name: 'Customer Support Bot', status: 'active', conversations: 1250, responseTime: '2s' },
    { id: 2, name: 'Sales Bot', status: 'active', conversations: 890, responseTime: '3s' }
  ];
  
  let workflows = [
    { id: 1, name: 'Welcome Flow', trigger: 'new_user', status: 'enabled' },
    { id: 2, name: 'Support Flow', trigger: 'help_command', status: 'enabled' }
  ];
</script>

<div class="bot-automation">
  <h1>🤖 Quản lý Chatbot</h1>
  
  <div class="bots-section">
    <h2>Chatbots</h2>
    {#each bots as bot}
      <div class="bot-card">
        <div class="bot-info">
          <h3>{bot.name}</h3>
          <span class="status {bot.status}">{bot.status}</span>
        </div>
        <div class="bot-stats">
          <p>💬 {bot.conversations.toLocaleString()} cuộc trò chuyện</p>
          <p>⏱️ {bot.responseTime} phản hồi</p>
        </div>
      </div>
    {/each}
  </div>
  
  <div class="workflows-section">
    <h2>Workflows</h2>
    {#each workflows as flow}
      <div class="workflow-item">
        <h4>{flow.name}</h4>
        <p>Trigger: {flow.trigger}</p>
        <span class="badge">{flow.status}</span>
      </div>
    {/each}
  </div>
</div>

<style>
  .bot-automation { padding: 20px; }
  .bot-card { background: white; padding: 16px; margin: 12px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  .bot-info { display: flex; justify-content: space-between; align-items: center; }
  .status { padding: 4px 12px; border-radius: 12px; font-size: 12px; }
  .status.active { background: #d1fae5; color: #065f46; }
  .workflow-item { background: #f9fafb; padding: 12px; margin: 8px 0; border-radius: 6px; }
  .badge { background: #3b82f6; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
</style>
