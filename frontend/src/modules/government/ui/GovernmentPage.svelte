<script lang="ts">
  import { onMount } from 'svelte';
  import CitizenManagement from './CitizenManagement.svelte';
  import DocumentManagement from './DocumentManagement.svelte';
  import ProcedureManagement from './ProcedureManagement.svelte';
  import AIChatbot from './AIChatbot.svelte';
  import GISMap from './GISMap.svelte';
  import IoTDashboard from './IoTDashboard.svelte';
  import NotificationCenter from './NotificationCenter.svelte';
  import FeedbackSystem from './FeedbackSystem.svelte';
  import EmergencyAlert from './EmergencyAlert.svelte';
  import VehicleRegistration from './VehicleRegistration.svelte';
  import SmartLighting from './SmartLighting.svelte';
  import WasteManagement from './WasteManagement.svelte';
  import EnvironmentalMonitoring from './EnvironmentalMonitoring.svelte';
  import CensusManagement from './CensusManagement.svelte';
  import BusinessLicense from './BusinessLicense.svelte';
  import EducationManagement from './EducationManagement.svelte';
  import HealthcareManagement from './HealthcareManagement.svelte';
  import LandUseManagement from './LandUseManagement.svelte';
  import ConstructionPermits from './ConstructionPermits.svelte';
  import RevenueManagement from './RevenueManagement.svelte';
  import AssetManagement from './AssetManagement.svelte';
  import MeetingManagement from './MeetingManagement.svelte';
  import PublicServices from './PublicServices.svelte';
  import BudgetManagement from './BudgetManagement.svelte';
  import InfrastructureManagement from './InfrastructureManagement.svelte';
  import PopulationRegistry from './PopulationRegistry.svelte';
  import CivilRegistry from './CivilRegistry.svelte';
  import { 
    citizenStore, 
    documentStore, 
    procedureStore,
    citizenStatistics,
    activeCitizens,
    pendingApplications,
    type Citizen,
    type GovernmentDocument,
    type AdministrativeProcedure
  } from '../index';
  
  // Active tab
  let activeTab: 'citizens' | 'documents' | 'procedures' | 'statistics' | 'chatbot' | 'gis' | 'iot' | 'notifications' | 'feedback' | 'emergency' | 'vehicles' | 'lighting' | 'waste' | 'environment' | 'census' | 'business' | 'education' | 'healthcare' | 'landuse' | 'construction' | 'revenue' | 'asset' | 'meeting' | 'public' | 'budget' | 'infrastructure' | 'population' | 'civil' = 'citizens';
  
  // Stats
  let citizenStats = { total: 0, active: 0 };
  let documentStats = { total: 0, published: 0 };
  let procedureStats = { total: 0, pending: 0 };
  
  const tabs = [
    { id: 'citizens' as const, label: 'Công dân', icon: '👥', color: '#3b82f6' },
    { id: 'documents' as const, label: 'Văn bản', icon: '📄', color: '#10b981' },
    { id: 'procedures' as const, label: 'TTHC', icon: '⚙️', color: '#f59e0b' },
    { id: 'statistics' as const, label: 'Thống kê', icon: '📊', color: '#8b5cf6' },
    { id: 'chatbot' as const, label: 'AI Hỗ trợ', icon: '🤖', color: '#ec4899' },
    { id: 'gis' as const, label: 'Bản đồ GIS', icon: '🗺️', color: '#06b6d4' },
    { id: 'iot' as const, label: 'IoT', icon: '📡', color: '#1e3a8a' },
    { id: 'notifications' as const, label: 'Thông báo', icon: '🔔', color: '#f97316' },
    { id: 'feedback' as const, label: 'Phản hồi', icon: '💬', color: '#14b8a6' },
    { id: 'emergency' as const, label: 'Khẩn cấp', icon: '🚨', color: '#dc2626' },
    { id: 'vehicles' as const, label: 'Phương tiện', icon: '🚗', color: '#64748b' },
    { id: 'lighting' as const, label: 'Đèn đường', icon: '💡', color: '#eab308' },
    { id: 'waste' as const, label: 'Rác thải', icon: '🗑️', color: '#059669' },
    { id: 'environment' as const, label: 'Môi trường', icon: '🌿', color: '#10b981' },
    { id: 'census' as const, label: 'Tổng điều tra', icon: '👨‍👩‍👧‍👦', color: '#6366f1' },
    { id: 'business' as const, label: 'Kinh doanh', icon: '🏢', color: '#8b5cf6' },
    { id: 'education' as const, label: 'Giáo dục', icon: '🏫', color: '#f59e0b' },
    { id: 'healthcare' as const, label: 'Y tế', icon: '🏥', color: '#ef4444' },
    { id: 'landuse' as const, label: 'Đất đai', icon: '🗺️', color: '#059669' },
    { id: 'construction' as const, label: 'Xây dựng', icon: '🏗️', color: '#7c3aed' },
    { id: 'revenue' as const, label: 'Thu ngân sách', icon: '💰', color: '#0891b2' },
    { id: 'asset' as const, label: 'Tài sản', icon: '🏢', color: '#dc2626' },
    { id: 'meeting' as const, label: 'Họp phường', icon: '📅', color: '#2563eb' },
    { id: 'public' as const, label: 'Dịch vụ công', icon: '🌐', color: '#0d9488' },
    { id: 'budget' as const, label: 'Ngân sách', icon: '💵', color: '#ea580c' },
    { id: 'infrastructure' as const, label: 'Hạ tầng', icon: '🏗️', color: '#4f46e5' },
    { id: 'population' as const, label: 'Hộ khẩu', icon: '🏠', color: '#0891b2' },
    { id: 'civil' as const, label: 'Hộ tịch', icon: '📜', color: '#9333ea' },
  ];
  
  onMount(async () => {
    // Load initial data
    await Promise.all([
      citizenStore.loadCitizens(true),
      citizenStore.loadStatistics(),
      documentStore.loadDocuments(true),
      documentStore.loadStatistics(),
      procedureStore.loadProcedures(true),
      procedureStore.loadPendingApplications(),
    ]);
  });
  
  // Subscribe to store changes
  $: {
    if ($citizenStatistics) {
      citizenStats = {
        total: $citizenStatistics.total,
        active: $citizenStatistics.active,
      };
    }
  }
  
  $: {
    if ($documentStore.statistics) {
      documentStats = {
        total: $documentStore.statistics.total,
        published: $documentStore.statistics.byStatus.published || 0,
      };
    }
  }
  
  $: {
    procedureStats = {
      total: $procedureStore.procedures.length,
      pending: $pendingApplications.length,
    };
  }
</script>

<div class="government-page">
  <!-- Header -->
  <header class="page-header">
    <div class="header-content">
      <h1>🏛️ Hệ thống Quản lý Phường/Xã</h1>
      <p class="subtitle">Quản lý công dân, văn bản và thủ tục hành chính</p>
    </div>
  </header>
  
  <!-- Stats Overview -->
  <div class="stats-overview">
    <div class="stat-card" style="--accent-color: #3b82f6">
      <div class="stat-icon">👥</div>
      <div class="stat-info">
        <div class="stat-value">{citizenStats.total}</div>
        <div class="stat-label">Công dân</div>
      </div>
      <div class="stat-trend active">
        {citizenStats.active} hoạt động
      </div>
    </div>
    
    <div class="stat-card" style="--accent-color: #10b981">
      <div class="stat-icon">📄</div>
      <div class="stat-info">
        <div class="stat-value">{documentStats.total}</div>
        <div class="stat-label">Văn bản</div>
      </div>
      <div class="stat-trend published">
        {documentStats.published} đã ban hành
      </div>
    </div>
    
    <div class="stat-card" style="--accent-color: #f59e0b">
      <div class="stat-icon">⚙️</div>
      <div class="stat-info">
        <div class="stat-value">{procedureStats.total}</div>
        <div class="stat-label">TTHC</div>
      </div>
      <div class="stat-trend pending">
        {procedureStats.pending} đang xử lý
      </div>
    </div>
    
    <div class="stat-card" style="--accent-color: #8b5cf6">
      <div class="stat-icon">✅</div>
      <div class="stat-info">
        <div class="stat-value">156</div>
        <div class="stat-label">Đã xử lý</div>
      </div>
      <div class="stat-trend completed">
        Tháng này
      </div>
    </div>
  </div>
  
  <!-- Navigation Tabs -->
  <nav class="tab-nav">
    {#each tabs as tab}
      <button 
        class="tab-button" 
        class:active={activeTab === tab.id}
        style="--tab-color: {tab.color}"
        on:click={() => activeTab = tab.id}
      >
        <span class="tab-icon">{tab.icon}</span>
        <span class="tab-label">{tab.label}</span>
      </button>
    {/each}
  </nav>
  
  <!-- Content Area -->
  <main class="content-area">
    {#if activeTab === 'citizens'}
      <CitizenManagement />
    {:else if activeTab === 'documents'}
      <DocumentManagement />
    {:else if activeTab === 'procedures'}
      <ProcedureManagement />
    {:else if activeTab === 'statistics'}
      <div class="statistics-dashboard">
        <div class="stats-section">
          <h3>📊 Thống kê tổng quan</h3>
          <div class="stats-cards">
            <div class="stat-box">
              <span class="stat-number">{citizenStats.total}</span>
              <span class="stat-desc">Tổng công dân</span>
            </div>
            <div class="stat-box">
              <span class="stat-number">{documentStats.total}</span>
              <span class="stat-desc">Văn bản</span>
            </div>
            <div class="stat-box">
              <span class="stat-number">{procedureStats.total}</span>
              <span class="stat-desc">Thủ tục hành chính</span>
            </div>
            <div class="stat-box">
              <span class="stat-number">{procedureStats.pending}</span>
              <span class="stat-desc">Hồ sơ đang xử lý</span>
            </div>
          </div>
        </div>
        
        <div class="stats-row">
          <div class="stats-section half">
            <h3>👥 Thống kê công dân</h3>
            {#if $citizenStatistics}
              <div class="stat-detail">
                <div class="detail-row">
                  <span>Nam:</span>
                  <strong>{$citizenStatistics.byGender.male}</strong>
                </div>
                <div class="detail-row">
                  <span>Nữ:</span>
                  <strong>{$citizenStatistics.byGender.female}</strong>
                </div>
                <div class="detail-row">
                  <span>Khác:</span>
                  <strong>{$citizenStatistics.byGender.other}</strong>
                </div>
                <div class="detail-row">
                  <span>Dưới 18 tuổi:</span>
                  <strong>{$citizenStatistics.byAgeGroup.under18}</strong>
                </div>
                <div class="detail-row">
                  <span>18-60 tuổi:</span>
                  <strong>{$citizenStatistics.byAgeGroup.age18to60}</strong>
                </div>
                <div class="detail-row">
                  <span>Trên 60 tuổi:</span>
                  <strong>{$citizenStatistics.byAgeGroup.over60}</strong>
                </div>
              </div>
            {:else}
              <p class="no-data">Chưa có dữ liệu</p>
            {/if}
          </div>
          
          <div class="stats-section half">
            <h3>📄 Thống kê văn bản</h3>
            {#if $documentStore.statistics}
              <div class="stat-detail">
                <div class="detail-row">
                  <span>Quyết định:</span>
                  <strong>{$documentStore.statistics.byType.decision || 0}</strong>
                </div>
                <div class="detail-row">
                  <span>Thông báo:</span>
                  <strong>{$documentStore.statistics.byType.notice || 0}</strong>
                </div>
                <div class="detail-row">
                  <span>Công văn:</span>
                  <strong>{$documentStore.statistics.byType.letter || 0}</strong>
                </div>
                <div class="detail-row">
                  <span>Báo cáo:</span>
                  <strong>{$documentStore.statistics.byType.report || 0}</strong>
                </div>
                <div class="detail-row">
                  <span>Đã ban hành:</span>
                  <strong>{$documentStore.statistics.byStatus.published || 0}</strong>
                </div>
                <div class="detail-row">
                  <span>Bản nháp:</span>
                  <strong>{$documentStore.statistics.byStatus.draft || 0}</strong>
                </div>
              </div>
            {:else}
              <p class="no-data">Chưa có dữ liệu</p>
            {/if}
          </div>
        </div>
      </div>
    {:else if activeTab === 'chatbot'}
      <div class="smart-feature">
        <AIChatbot />
      </div>
    {:else if activeTab === 'gis'}
      <div class="smart-feature">
        <GISMap />
      </div>
    {:else if activeTab === 'iot'}
      <div class="smart-feature">
        <IoTDashboard />
      </div>
    {:else if activeTab === 'notifications'}
      <div class="smart-feature">
        <NotificationCenter />
      </div>
    {:else if activeTab === 'feedback'}
      <div class="smart-feature">
        <FeedbackSystem />
      </div>
    {:else if activeTab === 'emergency'}
      <div class="smart-feature">
        <EmergencyAlert />
      </div>
    {:else if activeTab === 'vehicles'}
      <div class="smart-feature">
        <VehicleRegistration />
      </div>
    {:else if activeTab === 'lighting'}
      <div class="smart-feature">
        <SmartLighting />
      </div>
    {:else if activeTab === 'waste'}
      <div class="smart-feature">
        <WasteManagement />
      </div>
    {:else if activeTab === 'environment'}
      <div class="smart-feature">
        <EnvironmentalMonitoring />
      </div>
    {:else if activeTab === 'census'}
      <div class="smart-feature">
        <CensusManagement />
      </div>
    {:else if activeTab === 'business'}
      <div class="smart-feature">
        <BusinessLicense />
      </div>
    {:else if activeTab === 'education'}
      <div class="smart-feature">
        <EducationManagement />
      </div>
    {:else if activeTab === 'healthcare'}
      <div class="smart-feature">
        <HealthcareManagement />
      </div>
    {:else if activeTab === 'landuse'}
      <div class="smart-feature">
        <LandUseManagement />
      </div>
    {:else if activeTab === 'construction'}
      <div class="smart-feature">
        <ConstructionPermits />
      </div>
    {:else if activeTab === 'revenue'}
      <div class="smart-feature">
        <RevenueManagement />
      </div>
    {:else if activeTab === 'asset'}
      <div class="smart-feature">
        <AssetManagement />
      </div>
    {:else if activeTab === 'meeting'}
      <div class="smart-feature">
        <MeetingManagement />
      </div>
    {:else if activeTab === 'public'}
      <div class="smart-feature">
        <PublicServices />
      </div>
    {:else if activeTab === 'budget'}
      <div class="smart-feature">
        <BudgetManagement />
      </div>
    {:else if activeTab === 'infrastructure'}
      <div class="smart-feature">
        <InfrastructureManagement />
      </div>
    {:else if activeTab === 'population'}
      <div class="smart-feature">
        <PopulationRegistry />
      </div>
    {:else if activeTab === 'civil'}
      <div class="smart-feature">
        <CivilRegistry />
      </div>
    {/if}
  </main>
</div>

<style>
  .government-page {
    min-height: 100vh;
    background: #f1f5f9;
  }
  
  /* Header */
  .page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 32px;
  }
  
  .header-content h1 {
    margin: 0 0 8px;
    font-size: 1.75rem;
    font-weight: 700;
  }
  
  .subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 1rem;
  }
  
  /* Stats Overview */
  .stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    padding: 24px 32px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .stat-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
    border-left: 4px solid var(--accent-color);
  }
  
  .stat-icon {
    font-size: 2rem;
  }
  
  .stat-info {
    flex: 1;
  }
  
  .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
  }
  
  .stat-label {
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 4px;
  }
  
  .stat-trend {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    background: var(--accent-color);
    color: white;
  }
  
  /* Tab Navigation */
  .tab-nav {
    display: flex;
    gap: 4px;
    padding: 16px 32px 0;
    background: white;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .tab-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
  }
  
  .tab-button:hover {
    color: var(--tab-color);
    background: #f8fafc;
  }
  
  .tab-button.active {
    color: var(--tab-color);
    border-bottom-color: var(--tab-color);
  }
  
  .tab-icon {
    font-size: 1.25rem;
  }
  
  /* Content Area */
  .content-area {
    padding: 24px 32px;
  }
  
  /* Statistics Dashboard */
  .statistics-dashboard {
    display: flex;
    flex-direction: column;
    gap: 24px;
  }
  
  .stats-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  
  .stats-section.half {
    flex: 1;
  }
  
  .stats-section h3 {
    margin: 0 0 20px;
    color: #1e293b;
    font-size: 1.125rem;
  }
  
  .stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
  }
  
  .stat-box {
    text-align: center;
    padding: 20px;
    background: #f8fafc;
    border-radius: 8px;
  }
  
  .stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
  }
  
  .stat-desc {
    display: block;
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 4px;
  }
  
  .stats-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
  }
  
  .stat-detail {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  
  .detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
  }
  
  .detail-row:last-child {
    border-bottom: none;
  }
  
  .detail-row span {
    color: #64748b;
  }
  
  .detail-row strong {
    color: #1e293b;
    font-size: 1.125rem;
  }
  
  .no-data {
    text-align: center;
    color: #64748b;
    padding: 40px;
  }
  
  .smart-feature {
    height: calc(100vh - 300px);
    min-height: 500px;
  }
  
  @media (max-width: 768px) {
    .stats-row {
      grid-template-columns: 1fr;
    }
    
    .tab-nav {
      overflow-x: auto;
      padding: 16px;
    }
    
    .tab-button {
      white-space: nowrap;
    }
  }
</style>
