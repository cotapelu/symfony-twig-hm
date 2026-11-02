<script lang="ts">
  import { onMount } from 'svelte';

  interface Doctor {
    id: number;
    name: string;
    specialty: string;
    hospital: string;
    rating: number;
    experience: number;
    consultation_fee: number;
    available: boolean;
  }

  let doctors: Doctor[] = [];
  let loading = false;
  let selectedSpecialty = 'all';
  let searchQuery = '';

  const specialties = ['all', 'General', 'Cardiology', 'Dermatology', 'Pediatrics', 'Orthopedics', 'Neurology'];

  async function loadDoctors() {
    loading = true;
    try {
      const url = selectedSpecialty === 'all' 
        ? '/api/doctor-booking/doctors'
        : `/api/doctor-booking/doctors?specialty=${selectedSpecialty}`;
      const res = await fetch(url);
      const data = await res.json();
      doctors = data.data || [];
    } catch (e) {
      console.error('Failed to load doctors');
    }
    loading = false;
  }

  onMount(loadDoctors);

  $: filteredDoctors = searchQuery 
    ? doctors.filter(d => d.name.toLowerCase().includes(searchQuery.toLowerCase()))
    : doctors;
</script>

<div class="telemedicine-page">
  <header>
    <h1>👨‍⚕️ Doctor Booking</h1>
    <button class="primary">📞 Emergency</button>
  </header>

  <div class="search-section">
    <input type="text" bind:value={searchQuery} placeholder="Search doctors..." class="search-input" />
  </div>

  <div class="specialty-tabs">
    {#each specialties as specialty}
      <button 
        class:active={selectedSpecialty === specialty}
        on:click={() => selectedSpecialty = specialty}
      >
        {specialty}
      </button>
    {/each}
  </div>

  {#if loading}
    <div class="loading">Loading doctors...</div>
  {:else}
    <div class="doctor-list">
      {#each filteredDoctors as doctor}
        <div class="doctor-card">
          <div class="doctor-avatar">
            {doctor.name.charAt(0)}
          </div>
          <div class="doctor-info">
            <h3>{doctor.name}</h3>
            <p class="specialty">{doctor.specialty}</p>
            <p class="hospital">🏥 {doctor.hospital}</p>
            <div class="doctor-meta">
              <span>⭐ {doctor.rating}</span>
              <span>👨‍⚕️ {doctor.experience} years</span>
            </div>
          </div>
          <div class="doctor-action">
            <p class="fee">₫{doctor.consultation_fee.toLocaleString()}</p>
            <button class="book-btn" disabled={!doctor.available}>
              {doctor.available ? 'Book Now' : 'Unavailable'}
            </button>
          </div>
        </div>
      {/each}
    </div>
  {/if}

  <div class="quick-features">
    <h3>Quick Access</h3>
    <div class="feature-grid">
      <button class="feature-card">
        <span class="icon">💊</span>
        <span>Pharmacy</span>
      </button>
      <button class="feature-card">
        <span class="icon">📋</span>
        <span>My Records</span>
      </button>
      <button class="feature-card">
        <span class="icon">💉</span>
        <span>Vaccination</span>
      </button>
      <button class="feature-card">
        <span class="icon">🩺</span>
        <span>Health Check</span>
      </button>
    </div>
  </div>
</div>

<style>
  .telemedicine-page { padding: 20px; max-width: 900px; margin: 0 auto; }
  header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
  h1 { font-size: 1.8rem; }
  .primary { background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
  .search-section { margin-bottom: 20px; }
  .search-input { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
  .specialty-tabs { display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 10px; }
  .specialty-tabs button { padding: 8px 16px; border: 1px solid #ddd; background: white; border-radius: 20px; cursor: pointer; white-space: nowrap; }
  .specialty-tabs button.active { background: #007bff; color: white; border-color: #007bff; }
  .doctor-list { display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px; }
  .doctor-card { display: flex; gap: 15px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  .doctor-avatar { width: 60px; height: 60px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
  .doctor-info { flex: 1; }
  .doctor-info h3 { margin: 0 0 5px; }
  .specialty { color: #007bff; font-weight: 500; margin: 0 0 5px; }
  .hospital { color: #666; font-size: 0.9rem; margin: 0 0 10px; }
  .doctor-meta { display: flex; gap: 15px; font-size: 0.9rem; color: #666; }
  .doctor-action { text-align: right; }
  .fee { font-size: 1.2rem; font-weight: bold; color: #28a745; margin: 0 0 10px; }
  .book-btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
  .book-btn:disabled { background: #ccc; cursor: not-allowed; }
  .quick-features h3 { font-size: 1.1rem; margin-bottom: 15px; }
  .feature-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 15px; }
  .feature-card { background: white; border: none; padding: 20px 15px; border-radius: 12px; cursor: pointer; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  .feature-card:hover { transform: translateY(-2px); }
  .feature-card .icon { display: block; font-size: 2rem; margin-bottom: 8px; }
</style>
