<script lang="ts">
  import { onMount } from 'svelte';
  import { citizenService } from '$lib/services/citizenService';
  
  let citizens = [];
  let loading = false;
  let error = null;
  let total = 0;
  
  // Filters
  let filters = {
    province: '',
    district: '',
    ward: '',
    isActive: '',
    search: '',
    page: '1',
    perPage: '10'
  };
  
  // Load citizens on mount and when filters change
  async function loadCitizens() {
    loading = true;
    error = null;
    
    try {
      // Convert empty strings to undefined for optional filters
      const cleanFilters = {
        ...filters,
        province: filters.province || undefined,
        district: filters.district || undefined,
        ward: filters.ward || undefined,
        isActive: filters.isActive === 'true' ? true : filters.isActive === 'false' ? false : undefined,
        search: filters.search || undefined,
        page: Number(filters.page),
        perPage: Number(filters.perPage)
      };
      
      await citizenService.fetchCitizens(cleanFilters);
      
      // Subscribe to stores
      const unsubscribeCitizens = citizenService.citizens.subscribe((value) => {
        citizens = value;
      });
      
      const unsubscribeLoading = citizenService.loading.subscribe((value) => {
        loading = value;
      });
      
      const unsubscribeError = citizenService.error.subscribe((value) => {
        error = value;
      });
      
      const unsubscribeTotal = citizenService.total.subscribe((value) => {
        total = value;
      });
      
      // Clean up subscriptions (in a real app, you'd manage this better)
      // For simplicity, we'll just call them once and rely on the service stores
      unsubscribeCitizens();
      unsubscribeLoading();
      unsubscribeError();
      unsubscribeTotal();
    } catch (err) {
      error = err instanceof Error ? err.message : 'Unknown error';
      console.error('Failed to load citizens:', err);
    } finally {
      loading = false;
    }
  }
  
  onMount(() => {
    loadCitizens();
  });
  
  // Handle form submission
  function handleFilterChange() {
    filters.page = '1'; // Reset to first page when filters change
    loadCitizens();
  }
  
  // Handle pagination
  function goToPage(page: number) {
    if (page < 1 || page > Math.ceil(total / Number(filters.perPage))) return;
    filters.page = String(page);
    loadCitizens();
  }
  
  // Handle create citizen (placeholder for now)
  async function createCitizen() {
    // This would open a modal or navigate to a create form
    alert('Create citizen functionality would be implemented here');
  }
  
  // Handle edit citizen (placeholder)
  async function editCitizen(id: string) {
    alert(`Edit citizen ${id} would be implemented here`);
  }
  
  // Handle delete citizen
  async function deleteCitizen(id: string) {
    if (!confirm('Are you sure you want to delete this citizen?')) return;
    
    // This would call a delete service
    alert(`Delete citizen ${id} would be implemented here`);
    // After deletion, we would reload the list
    loadCitizens();
  }
</script>

<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-6">Quản lý Công dân</h1>
  
  {#if error}
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
      {error}
    </div>
  {/if}
  
  <div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Bộ lọc</h2>
    <form on:submit|preventDefault={handleFilterChange} class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
       <div>
         <label class="block text-sm font-medium mb-1" for="province-filter">Tỉnh/Thành phố</label>
         <input 
           id="province-filter"
           bind:value={filters.province}
           type="text"
           placeholder="Nhập tên tỉnh"
           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
         />
       </div>
      
      <div>
        <label class="block text-sm font-medium mb-1" for="district-filter">Quận/Huyện</label>
        <input 
          bind:value={filters.district}
          type="text"
          placeholder="Nhập tên quận"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      
      <div>
        <label class="block text-sm font-medium mb-1">Phường/Xã</label>
        <input 
          bind:value={filters.ward}
          type="text"
          placeholder="Nhập tên phường"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      
      <div>
        <label class="block text-sm font-medium mb-1">Trạng thái</label>
        <select 
          bind:value={filters.isActive}
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Tất cả</option>
          <option value="true">Hoạt động</option>
          <option value="false">Ngừng hoạt động</option>
        </select>
      </div>
      
      <div>
        <label class="block text-sm font-medium mb-1">Tìm kiếm</label>
        <input 
          bind:value={filters.search}
          type="text"
          placeholder="Nhập tên, CCCD..."
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
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
              province: '',
              district: '',
              ward: '',
              isActive: '',
              search: '',
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
  
  <div>
    <button 
      on:click={createCitizen}
      class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-md"
    >
      Thêm công dân mới
    </button>
  </div>
  
  <div class="text-sm text-gray-600">
    Hiển thị {citizens.length} trong tổng cộng {total} công dân
  </div>
  
  {#if loading}
    <div class="text-center py-8">
      <div class="animate-spin rounded-full border-4 border-b-2 border-blue-500 w-12 h-12 mx-auto"></div>
      <p class="mt-2 text-gray-600">Đang tải dữ liệu...</p>
    </div>
  {:else}
    {#if citizens.length > 0}
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
          <thead>
            <tr class="bg-gray-50">
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Họ và tên</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CCCD/CMND</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giới tính</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SĐT</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tình trạng</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
            </tr>
          </thead>
          <tbody>
            {#each citizens as citizen, index}
              <tr class="bg-white border-t hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {((Number(filters.page) - 1) * Number(filters.perPage)) + index + 1}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                  {citizen.fullName}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {citizen.identityNumber || 'Chưa có'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {citizen.gender === 'male' ? 'Nam' : citizen.gender === 'female' ? 'Nữ' : 'Khác'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {citizen.phone || 'Chưa có'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {citizen.email || 'Chưa có'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  {#if citizen.isActive}
                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Hoạt động</span>
                  {:else}
                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Ngừng hoạt động</span>
                  {/if}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div class="flex space-x-2">
                    <button 
                      on:click={() => editCitizen(citizen.id)}
                      class="bg-blue-500 hover:bg-blue-600 text-white font-medium text-xs px-3 py-1 rounded"
                    >
                      Sửa
                    </button>
                    <button 
                      on:click={() => deleteCitizen(citizen.id)}
                      class="bg-red-500 hover:bg-red-600 text-white font-medium text-xs px-3 py-1 rounded"
                    >
                      Xóa
                    </button>
                  </div>
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
        <p class="text-gray-500">Không có công dân nào phù hợp với bộ lọc hiện tại.</p>
      </div>
    {/if}
  {/if}
</div>