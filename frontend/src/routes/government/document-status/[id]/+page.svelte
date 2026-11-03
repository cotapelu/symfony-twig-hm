<script lang="ts">
  import { onMount } from 'svelte';
  import { documentWorkflowService } from '$lib/services';
  
  let workflows = [];
  let loading = false;
  let error = null;
  let total = 0;
  
  // Filters
  let filters = {
    status: '',
    page: '1',
    perPage: '10'
  };
  
  // Load workflows on mount and when filters change
  async function loadWorkflows() {
    loading = true;
    error = null;
    
    try {
      // Convert empty string to undefined for optional filters
      const cleanFilters = {
        ...filters,
        status: filters.status || undefined,
        page: Number(filters.page),
        perPage: Number(filters.perPage)
      };
      
      await documentWorkflowService.fetchWorkflows(cleanFilters);
      
      // Subscribe to stores
      const unsubscribeWorkflows = documentWorkflowService.workflows.subscribe((value) => {
        workflows = value;
      });
      
      const unsubscribeLoading = documentWorkflowService.loading.subscribe((value) => {
        loading = value;
      });
      
      const unsubscribeError = documentWorkflowService.error.subscribe((value) => {
        error = value;
      });
      
      const unsubscribeTotal = documentWorkflowService.total.subscribe((value) => {
        total = value;
      });
      
      // Clean up subscriptions
      unsubscribeWorkflows();
      unsubscribeLoading();
      unsubscribeError();
      unsubscribeTotal();
    } catch (err) {
      error = err instanceof Error ? err.message : 'Unknown error';
      console.error('Failed to load workflows:', err);
    } finally {
      loading = false;
    }
  }
  
  onMount(() => {
    loadWorkflows();
  });
  
  // Handle form submission
  function handleFilterChange() {
    filters.page = '1'; // Reset to first page when filters change
    loadWorkflows();
  }
  
  // Handle pagination
  function goToPage(page: number) {
    if (page < 1 || page > Math.ceil(total / Number(filters.perPage))) return;
    filters.page = String(page);
    loadWorkflows();
  }
  
  // Format date
  function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString();
  }
  
  // Get status badge class
  function getStatusBadgeClass(status: string): string {
    switch (status) {
      case 'pending': return 'bg-yellow-100 text-yellow-800';
      case 'approved': return 'bg-green-100 text-green-800';
      case 'rejected': return 'bg-red-100 text-red-800';
      case 'published': return 'bg-blue-100 text-blue-800';
      case 'archived': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  }
  
  // Get status label
  function getStatusLabel(status: string): string {
    switch (status) {
      case 'pending': return 'Pending';
      case 'approved': return 'Approved';
      case 'rejected': return 'Rejected';
      case 'published': return 'Published';
      case 'archived': return 'Archived';
      default: return 'Unknown';
    }
  }
</script>

<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-6">Document Workflow Status</h1>
  
    {#if error}
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
      {error}
    </div>
  {/if}
  
  <div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Bộ lọc</h2>
    <form on:submit|preventDefault={handleFilterChange} class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Trạng thái</label>
        <select 
          bind:value={filters.status}
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Tất cả</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="published">Published</option>
          <option value="archived">Archived</option>
        </select>
      </div>
      
      <div>
        <label class="block text-sm font-medium mb-1">Số lượng/Trang</label>
        <div class="flex gap-2">
           <input 
             bind:value={filters.perPage}
             type="number"
             min="1"
             max="100"
             class="w-24 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
           />
          <span class="align-middle">/ trang</span>
        </div>
      </div>
      
      <div class="flex items-end">
        <button 
          type="submit"
          class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md"
        >
          Áp dụng bộ lọc
        </button>
        <button 
          type="button"
          on:click={() => {
            filters = {
              status: '',
              page: '1',
              perPage: '10'
            };
            handleFilterChange();
          }}
          class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md ml-2"
        >
          Đặt lại
        </button>
       </div>
     </form>
   </div>
   
   <div class="flex justify-between items-center mb-4">
    <div class="text-sm text-gray-600">
      Hiển thị {workflows.length} trong tổng cộng {total} workflows
    </div>
  </div>
  
  {#if loading}
    <div class="text-center py-8">
      <div class="animate-spin rounded-full border-4 border-b-2 border-blue-500 w-12 h-12 mx-auto"></div>
      <p class="mt-2 text-gray-600">Đang tải dữ liệu...</p>
    </div>
  {:else}
    {#if workflows.length > 0}
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
          <thead>
            <tr class="bg-gray-50">
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người xử lý</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nhận xét</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian xử lý</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
            </tr>
          </thead>
          <tbody>
            {#each workflows as workflow, index}
              <tr class="bg-white border-t hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {((Number(filters.page) - 1) * Number(filters.perPage)) + index + 1}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                  {workflow.documentId}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span class={`px-2 py-1 text-xs rounded-full ${getStatusBadgeClass(workflow.status)}`}>
                    {getStatusLabel(workflow.status)}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {workflow.processedBy || 'Chưa có'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {workflow.comments || 'Chưa có'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  {formatDate(workflow.processedAt)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  {formatDate(workflow.createdAt)}
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
      
      {#if total > 0}
        <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
          <div class="text-sm text-gray-600">
            Trang {filters.page} của {Math.ceil(total / Number(filters.perPage))}
          </div>
          <div class="flex space-x-2">
            <button 
              on:click={() => goToPage(Number(filters.page) - 1)}
              disabled={Number(filters.page) <= 1}
              class="px-3 py-1.5 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Trước
            </button>
            <button 
              on:click={() => goToPage(Number(filters.page) + 1)}
              disabled={Number(filters.page) >= Math.ceil(total / Number(filters.perPage))}
              class="px-3 py-1.5 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Sau
            </button>
          </div>
        </div>
      {/if}
    {:else}
      <div class="text-center py-12">
        <p class="text-gray-500">Không có workflow nào phù hợp với bộ lọc hiện tại.</p>
      </div>
    {/if}
  {/if}
</div>