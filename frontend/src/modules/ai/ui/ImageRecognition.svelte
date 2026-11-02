<script>
  let selectedFile = null;
  let result = null;
  let loading = false;
  
  function handleFileSelect(e) {
    selectedFile = e.target.files[0];
  }
  
  async function analyze() {
    if (!selectedFile) return;
    loading = true;
    // Mock API call
    setTimeout(() => {
      result = {
        objects: [{label: 'person', confidence: 0.95}, {label: 'car', confidence: 0.88}],
        text: 'Sample text from image'
      };
      loading = false;
    }, 1500);
  }
</script>

<div class="image-recognition">
  <h2>🔍 Nhận diện hình ảnh</h2>
  <input type="file" accept="image/*" on:change={handleFileSelect} />
  {#if selectedFile}
    <button on:click={analyze} disabled={loading}>
      {loading ? 'Đang phân tích...' : 'Phân tích'}
    </button>
  {/if}
  {#if result}
    <div class="results">
      <h3>Kết quả:</h3>
      <p><b>Đối tượng:</b> {result.objects.map(o => o.label).join(', ')}</p>
      <p><b>Văn bản:</b> {result.text}</p>
    </div>
  {/if}
</div>

<style>
  .image-recognition { padding: 20px; max-width: 600px; margin: 0 auto; }
  input { margin: 16px 0; }
  button { background: #3b82f6; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; }
  button:disabled { opacity: 0.5; }
  .results { margin-top: 20px; padding: 16px; background: #f3f4f6; border-radius: 8px; }
</style>
