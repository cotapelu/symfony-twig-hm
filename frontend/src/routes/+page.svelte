<script lang="ts">
  import { goto } from '$app/navigation';
  import { userStore } from '$lib/stores';

  function navigateTo(path: string) {
    goto(path);
  }
</script>

<svelte:head>
  <title>Phường/Xã Super App - Your All-in-One Platform</title>
</svelte:head>

<div class="home-page">
  <header class="hero">
    <div class="hero-content">
      <h1>Phường/Xã Super App</h1>
      <p class="subtitle">Hệ thống quản lý hành chính thông minh - Quản lý toàn diện từ Smart City đến Dịch vụ công</p>
      
      <div class="cta-buttons">
        {#if $userStore}
          <button class="btn-primary" on:click={() => navigateTo('/dashboard')}>
            Go to Dashboard
          </button>
        {:else}
          <button class="btn-primary" on:click={() => navigateTo('/auth')}>
            Get Started
          </button>
        {/if}
      </div>
    </div>
  </header>

  <section class="features">
    <h2>🏛️ Government Services</h2>
    <div class="features-grid">
      <div class="feature-card" on:click={() => navigateTo('/government')}>
        <div class="feature-icon">🏛️</div>
        <h3>Government</h3>
        <p>Administrative services, citizen management, documents</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/smartcity')}>
        <div class="feature-icon">🌆</div>
        <h3>Smart City</h3>
        <p>IoT monitoring, environment, GIS map</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/doctor')}>
        <div class="feature-icon">👨‍⚕️</div>
        <h3>Healthcare</h3>
        <p>Doctor booking, telemedicine, health records</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/insurance')}>
        <div class="feature-icon">🛡️</div>
        <h3>Insurance</h3>
        <p>Health, car, life, travel insurance</p>
      </div>
      
      <div class="feature-card" on:click={() => navigateTo('/jobs')}>
        <div class="feature-icon">💼</div>
        <h3>Jobs</h3>
        <p>Job matching, employment services</p>
      </div>
      
      <div class="feature-card" on:click={() => navigateTo('/education')}>
        <div class="feature-icon">🎓</div>
        <h3>Education</h3>
        <p>Schools, courses, training</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/complaints')}>
        <div class="feature-icon">📝</div>
        <h3>Complaints</h3>
        <p>Feedback, complaints handling</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/elderly')}>
        <div class="feature-icon">👴</div>
        <h3>Elderly Care</h3>
        <p>Elderly management, support</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/traffic')}>
        <div class="feature-icon">🚦</div>
        <h3>Traffic & Infra</h3>
        <p>Roads, infrastructure management</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/citizen-services')}>
        <div class="feature-icon">💳</div>
        <h3>Citizen Services</h3>
        <p>Pay bills, appointments</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/news')}>
        <div class="feature-icon">📰</div>
        <h3>News</h3>
        <p>News, announcements</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/emergency')}>
        <div class="feature-icon">🆘</div>
        <h3>Emergency</h3>
        <p>Quick emergency contacts</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/my-applications')}>
        <div class="feature-icon">📋</div>
        <h3>My Applications</h3>
        <p>Track your applications</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/map')}>
        <div class="feature-icon">🗺️</div>
        <h3>Map</h3>
        <p>Digital map GIS</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/chat-official')}>
        <div class="feature-icon">💬</div>
        <h3>Chat Official</h3>
        <p>Chat with officers</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/notifications')}>
        <div class="feature-icon">🔔</div>
        <h3>Notifications</h3>
        <p>Push notifications</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/directory')}>
        <div class="feature-icon">🏪</div>
        <h3>Business</h3>
        <p>Business directory</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/household')}>
        <div class="feature-icon">👨‍👩‍👧</div>
        <h3>Household</h3>
        <p>Household management</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/civil')}>
        <div class="feature-icon">📋</div>
        <h3>Civil Registry</h3>
        <p>Birth, death, marriage</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/environment')}>
        <div class="feature-icon">🌿</div>
        <h3>Environment</h3>
        <p>Air quality, monitoring</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/campaigns')}>
        <div class="feature-icon">📢</div>
        <h3>Campaigns</h3>
        <p>Community campaigns</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/population')}>
        <div class="feature-icon">👥</div>
        <h3>Population</h3>
        <p>Population management</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/land-construction')}>
        <div class="feature-icon">🏗️</div>
        <h3>Land & Construction</h3>
        <p>Land & building permits</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/culture-sports')}>
        <div class="feature-icon">🎭</div>
        <h3>Culture & Sports</h3>
        <p>Events, facilities</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/security-order')}>
        <div class="feature-icon">🛡️</div>
        <h3>Security</h3>
        <p>Safety & order</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/reports')}>
        <div class="feature-icon">📊</div>
        <h3>Reports</h3>
        <p>Analytics & reports</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/finance')}>
        <div class="feature-icon">💰</div>
        <h3>Finance</h3>
        <p>Budget & expenses</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/education-gov')}>
        <div class="feature-icon">🎓</div>
        <h3>Education</h3>
        <p>Schools & training</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/volunteer')}>
        <div class="feature-icon">🤝</div>
        <h3>Volunteer</h3>
        <p>Social programs</p>
      </div>
    </div>
  </section>

  <section class="features">
    <h2>🛒 Commerce & Services</h2>
    <div class="features-grid">
      <div class="feature-card" on:click={() => navigateTo('/hotels')}>
        <div class="feature-icon">🏨</div>
        <h3>Hotels</h3>
        <p>Find and book accommodation</p>
      </div>
      
      <div class="feature-card" on:click={() => navigateTo('/flights')}>
        <div class="feature-icon">✈️</div>
        <h3>Flights</h3>
        <p>Search and book flights</p>
      </div>
      
      <div class="feature-card" on:click={() => navigateTo('/wallet')}>
        <div class="feature-icon">💰</div>
        <h3>Wallet</h3>
        <p>Digital wallet, payments</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/banking')}>
        <div class="feature-icon">🏦</div>
        <h3>Banking</h3>
        <p>Bank accounts, transfers</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/marketplace')}>
        <div class="feature-icon">🛍️</div>
        <h3>Marketplace</h3>
        <p>Buy and sell products</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/delivery')}>
        <div class="feature-icon">📦</div>
        <h3>Delivery</h3>
        <p>Package tracking</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/cars')}>
        <div class="feature-icon">🚗</div>
        <h3>Car Rental</h3>
        <p>Rent a car</p>
      </div>
      
      <div class="feature-card" on:click={() => navigateTo('/restaurants')}>
        <div class="feature-icon">🍽️</div>
        <h3>Restaurants</h3>
        <p>Book tables</p>
      </div>
    </div>
  </section>

  <section class="features">
    <h2>💬 Communication</h2>
    <div class="features-grid">
      <div class="feature-card" on:click={() => navigateTo('/videoconference')}>
        <div class="feature-icon">📹</div>
        <h3>Video Conference</h3>
        <p>Host meetings</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/chatbot')}>
        <div class="feature-icon">🤖</div>
        <h3>AI Assistant</h3>
        <p>AI chatbot support</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/messages')}>
        <div class="feature-icon">💬</div>
        <h3>Messages</h3>
        <p>Chat with others</p>
      </div>

      <div class="feature-card" on:click={() => navigateTo('/call')}>
        <div class="feature-icon">📞</div>
        <h3>Calls</h3>
        <p>Voice calls</p>
      </div>
    </div>
  </section>

  <footer class="footer">
    <p>&copy; 2026 Phường/Xã Super App. All rights reserved.</p>
  </footer>
</div>

<style>
  .home-page {
    min-height: 100vh;
  }

  .hero {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    padding: 6rem 2rem;
    text-align: center;
  }

  .hero-content {
    max-width: 800px;
    margin: 0 auto;
  }

  .hero h1 {
    font-size: 3rem;
    margin: 0 0 1rem 0;
    font-weight: 700;
  }

  .subtitle {
    font-size: 1.25rem;
    margin: 0 0 2rem 0;
    opacity: 0.9;
  }

  .cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
  }

  .btn-primary {
    padding: 1rem 2rem;
    background: white;
    color: #1e3a8a;
    border: none;
    border-radius: 8px;
    font-size: 1.125rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
  }

  .features {
    padding: 4rem 2rem;
    max-width: 1200px;
    margin: 0 auto;
  }

  .features h2 {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 3rem;
    color: #1e293b;
  }

  .features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
  }

  .feature-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .feature-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
  }

  .feature-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
  }

  .feature-card h3 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }

  .feature-card p {
    margin: 0;
    color: #64748b;
  }

  .footer {
    background: #1e293b;
    color: white;
    text-align: center;
    padding: 2rem;
  }

  .footer p {
    margin: 0;
  }

  @media (max-width: 768px) {
    .hero h1 {
      font-size: 2rem;
    }

    .subtitle {
      font-size: 1rem;
    }
  }
</style>
