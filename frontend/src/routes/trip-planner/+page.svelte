<script lang="ts">
  interface TripDay {
    date: string;
    activities: Activity[];
  }

  interface Activity {
    id: string;
    time: string;
    title: string;
    type: 'hotel' | 'flight' | 'restaurant' | 'activity' | 'transport';
    location: string;
    notes: string;
    bookingRef?: string;
  }

  let tripName = '';
  let destination = '';
  let startDate = '';
  let endDate = '';
  let travelers = 1;
  let budget = '';
  let notes = '';

  let itinerary: TripDay[] = [];
  let selectedDay = 0;

  function generateItinerary() {
    if (!startDate || !endDate) {
      alert('Please select start and end dates');
      return;
    }

    const start = new Date(startDate);
    const end = new Date(endDate);
    const days = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;

    itinerary = [];
    for (let i = 0; i < days; i++) {
      const date = new Date(start);
      date.setDate(date.getDate() + i);
      
      itinerary.push({
        date: date.toISOString().split('T')[0],
        activities: []
      });
    }
  }

  function addActivity(dayIndex: number) {
    const activity: Activity = {
      id: Date.now().toString(),
      time: '09:00',
      title: '',
      type: 'activity',
      location: '',
      notes: ''
    };
    itinerary[dayIndex].activities.push(activity);
  }

  function removeActivity(dayIndex: number, activityId: string) {
    itinerary[dayIndex].activities = itinerary[dayIndex].activities.filter(a => a.id !== activityId);
  }

  function saveTrip() {
    const trip = {
      name: tripName || destination,
      destination,
      startDate,
      endDate,
      travelers,
      budget,
      notes,
      itinerary,
      createdAt: new Date().toISOString()
    };

    const saved = JSON.parse(localStorage.getItem('saved_trips') || '[]');
    saved.push(trip);
    localStorage.setItem('saved_trips', JSON.stringify(saved));
    
    alert('Trip saved successfully!');
  }

  function exportItinerary() {
    const content = `
TRIP ITINERARY
==============
Destination: ${destination}
Duration: ${startDate} to ${endDate}
Travelers: ${travelers}
${budget ? `Budget: ${budget}` : ''}
${notes ? `\nNotes:\n${notes}` : ''}

DETAILED ITINERARY
==================
${itinerary.map((day, i) => `
Day ${i + 1} - ${day.date}
${day.activities.map(a => `- ${a.time} - ${a.title} (${a.type}) - ${a.location}`).join('\n')}
`).join('\n')}
    `.trim();

    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${destination}-itinerary.txt`;
    a.click();
  }
</script>

<svelte:head>
  <title>Trip Planner - Travel App</title>
</svelte:head>

<div class="trip-planner-page">
  <header class="header">
    <div class="header-content">
      <button class="back-btn" onclick={() => history.back()}>
        ← Back
      </button>
      <h1>📅 Trip Planner</h1>
      <button class="save-btn" onclick={saveTrip}>Save Trip</button>
    </div>
  </header>

  <main class="main-content">
    <div class="planner-layout">
      <!-- Trip Details -->
      <div class="trip-details">
        <div class="section-card">
          <h2>Trip Details</h2>
          
          <div class="form-group">
            <label>Trip Name</label>
            <input type="text" placeholder="My Summer Vacation" bind:value={tripName} />
          </div>

          <div class="form-group">
            <label>Destination</label>
            <input type="text" placeholder="Paris, France" bind:value={destination} />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Start Date</label>
              <input type="date" bind:value={startDate} />
            </div>
            <div class="form-group">
              <label>End Date</label>
              <input type="date" bind:value={endDate} />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Travelers</label>
              <input type="number" min="1" max="20" bind:value={travelers} />
            </div>
            <div class="form-group">
              <label>Budget</label>
              <input type="text" placeholder="$5000 USD" bind:value={budget} />
            </div>
          </div>

          <div class="form-group">
            <label>Notes</label>
            <textarea placeholder="Any special requirements or notes..." rows="3" bind:value={notes}></textarea>
          </div>

          <button class="generate-btn" onclick={generateItinerary}>
            Generate Itinerary
          </button>
        </div>
      </div>

      <!-- Itinerary -->
      <div class="itinerary-section">
        {#if itinerary.length === 0}
          <div class="empty-state">
            <span class="empty-icon">🗺️</span>
            <h3>Start Planning Your Trip</h3>
            <p>Enter your trip details and click "Generate Itinerary" to create your travel plan</p>
          </div>
        {:else}
          <!-- Day Tabs -->
          <div class="day-tabs">
            {#each itinerary as day, index}
              <button 
                class="day-tab"
                class:active={selectedDay === index}
                onclick={() => selectedDay = index}
              >
                Day {index + 1}
                <span class="day-date">{day.date}</span>
              </button>
            {/each}
          </div>

          <!-- Day Content -->
          <div class="day-content">
            <div class="day-header">
              <h3>Day {selectedDay + 1}</h3>
              <span class="day-full-date">{itinerary[selectedDay].date}</span>
            </div>

            {#each itinerary[selectedDay].activities as activity, actIndex}
              <div class="activity-card">
                <div class="activity-time">
                  <input type="time" bind:value={activity.time} />
                </div>
                <div class="activity-content">
                  <input type="text" placeholder="Activity title" bind:value={activity.title} />
                  <select bind:value={activity.type}>
                    <option value="hotel">🏨 Hotel</option>
                    <option value="flight">✈️ Flight</option>
                    <option value="restaurant">🍽️ Restaurant</option>
                    <option value="activity">🎯 Activity</option>
                    <option value="transport">🚗 Transport</option>
                  </select>
                  <input type="text" placeholder="Location" bind:value={activity.location} />
                  <textarea placeholder="Notes..." rows="2" bind:value={activity.notes}></textarea>
                </div>
                <button class="remove-activity" onclick={() => removeActivity(selectedDay, activity.id)}>✕</button>
              </div>
            {/each}

            <button class="add-activity-btn" onclick={() => addActivity(selectedDay)}>
              + Add Activity
            </button>
          </div>

          <button class="export-btn" onclick={exportItinerary}>
            📥 Export Itinerary
          </button>
        {/if}
      </div>
    </div>
  </main>
</div>

<style>
  .trip-planner-page {
    min-height: 100vh;
    background: #f8f9fa;
  }

  .header {
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    position: sticky;
    top: 0;
    z-index: 100;
  }

  .header-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .back-btn {
    background: none;
    border: none;
    font-size: 1rem;
    color: #666;
    cursor: pointer;
  }

  h1 {
    flex: 1;
    font-size: 1.25rem;
    margin: 0;
  }

  .save-btn {
    padding: 0.625rem 1.25rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
  }

  .main-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
  }

  .planner-layout {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 2rem;
  }

  .section-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  }

  .section-card h2 {
    font-size: 1.125rem;
    margin: 0 0 1.5rem;
    color: #1a1a2e;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 0.5rem;
  }

  .form-group input,
  .form-group textarea,
  .form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 1rem;
  }

  .form-group textarea {
    resize: vertical;
    font-family: inherit;
  }

  .form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  .generate-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 1rem;
  }

  .empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
  }

  .empty-icon {
    font-size: 4rem;
    display: block;
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    color: #1a1a2e;
    margin-bottom: 0.5rem;
  }

  .empty-state p {
    color: #666;
    max-width: 400px;
    margin: 0 auto;
  }

  .day-tabs {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding: 1rem 0;
    margin-bottom: 1rem;
  }

  .day-tab {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem 1.5rem;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 100px;
  }

  .day-tab:hover {
    border-color: #667eea;
  }

  .day-tab.active {
    border-color: #667eea;
    background: #f0f4ff;
  }

  .day-tab span {
    font-size: 0.75rem;
    color: #999;
    margin-top: 0.25rem;
  }

  .day-content {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
  }

  .day-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
  }

  .day-header h3 {
    margin: 0;
    color: #1a1a2e;
  }

  .day-full-date {
    color: #666;
    font-size: 0.875rem;
  }

  .activity-card {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 0.75rem;
  }

  .activity-time input {
    width: 100px;
    padding: 0.5rem;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
  }

  .activity-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .activity-content input,
  .activity-content select,
  .activity-content textarea {
    padding: 0.5rem;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    font-size: 0.875rem;
  }

  .remove-activity {
    background: none;
    border: none;
    color: #ff4757;
    cursor: pointer;
    font-size: 1.25rem;
  }

  .add-activity-btn {
    width: 100%;
    padding: 1rem;
    background: none;
    border: 2px dashed #e0e0e0;
    border-radius: 8px;
    color: #666;
    cursor: pointer;
    margin-top: 1rem;
    transition: all 0.2s;
  }

  .add-activity-btn:hover {
    border-color: #667eea;
    color: #667eea;
  }

  .export-btn {
    display: block;
    width: 100%;
    padding: 1rem;
    background: #1a1a2e;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    margin-top: 1.5rem;
  }

  @media (max-width: 900px) {
    .planner-layout {
      grid-template-columns: 1fr;
    }
  }
</style>
