<script lang="ts">
  import { onMount } from 'svelte';

  interface FAQ {
    question: string;
    answer: string;
    category: string;
  }

  const faqs: FAQ[] = [
    // Booking
    { question: 'How do I make a booking?', answer: 'Search for your desired service, select dates/times, add to cart, and proceed to checkout. You can pay securely with credit card, PayPal, or bank transfer.', category: 'Booking' },
    { question: 'Can I modify my booking?', answer: 'Yes, you can modify most bookings up to 24 hours before the scheduled date. Go to My Bookings, select the booking, and choose Modify.', category: 'Booking' },
    { question: 'How do I cancel a booking?', answer: 'Navigate to My Bookings, select the booking you want to cancel, and click Cancel. Refunds are processed within 5-7 business days.', category: 'Booking' },
    { question: 'What is the cancellation policy?', answer: 'Free cancellation is available for most bookings up to 24 hours before the service date. Some bookings may have different policies - check the terms before confirming.', category: 'Booking' },
    
    // Payment
    { question: 'What payment methods are accepted?', answer: 'We accept Visa, MasterCard, American Express, PayPal, and bank transfers. All payments are securely processed.', category: 'Payment' },
    { question: 'Is my payment information secure?', answer: 'Yes, all payments are processed through secure SSL-encrypted connections. We never store your full card details on our servers.', category: 'Payment' },
    { question: 'How do I get a refund?', answer: 'Refunds are automatically processed to your original payment method within 5-7 business days after cancellation is confirmed.', category: 'Payment' },
    { question: 'Can I split payment between methods?', answer: 'Currently, we only support single-payment transactions. You can make separate bookings if needed.', category: 'Payment' },
    
    // Account
    { question: 'How do I create an account?', answer: 'Click Sign Up on the homepage, enter your email and password, and verify your email address. You can also sign up with Google or Facebook.', category: 'Account' },
    { question: 'How do I reset my password?', answer: 'Click Forgot Password on the login page, enter your email, and follow the reset link sent to your inbox.', category: 'Account' },
    { question: 'How can I change my profile information?', answer: 'Go to Profile, click Edit Profile, and update your information. Changes are saved automatically.', category: 'Account' },
    { question: 'How do I delete my account?', answer: 'Go to Settings > Delete My Account. Please note this action is irreversible and will delete all your data.', category: 'Account' },
    
    // Services
    { question: 'What services are available?', answer: 'We offer hotels, flights, car rentals, restaurants, travel packages, events, and local experiences. Browse our services from the dashboard.', category: 'Services' },
    { question: 'How do I add items to wishlist?', answer: 'Click the heart icon on any hotel, flight, or experience to save it to your wishlist. Access your wishlist from the main menu.', category: 'Services' },
    { question: 'Can I share bookings with others?', answer: 'Yes, you can share booking confirmations via email or download PDF confirmations from the booking details page.', category: 'Services' },
    { question: 'How do I leave a review?', answer: 'After completing a booking, you can leave a review from My Bookings or the service page. Reviews help other travelers!', category: 'Services' },
    
    // Technical
    { question: 'The app is not loading properly', answer: 'Try refreshing the page or clearing your browser cache. If the issue persists, try a different browser or device.', category: 'Technical' },
    { question: 'How do I enable push notifications?', answer: 'Go to Settings > Notifications and toggle Push Notifications. Make sure your browser has notification permissions enabled.', category: 'Technical' },
    { question: 'Is there a mobile app?', answer: 'Currently, we offer a responsive web app that works great on all devices. Mobile app coming soon!', category: 'Technical' },
    { question: 'How do I contact support?', answer: 'Use the contact form below or email support@travelapp.com. We typically respond within 24 hours.', category: 'Technical' }
  ];

  const categories = ['All', 'Booking', 'Payment', 'Account', 'Services', 'Technical'];
  let selectedCategory = 'All';
  let searchQuery = '';
  let expandedFaq: number | null = null;

  $: filteredFaqs = faqs.filter(faq => {
    const matchesCategory = selectedCategory === 'All' || faq.category === selectedCategory;
    const matchesSearch = searchQuery === '' || 
      faq.question.toLowerCase().includes(searchQuery.toLowerCase()) ||
      faq.answer.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  function toggleFaq(index: number) {
    expandedFaq = expandedFaq === index ? null : index;
  }

  let contactName = '';
  let contactEmail = '';
  let contactSubject = '';
  let contactMessage = '';
  let contactSubmitted = false;

  function submitContact() {
    if (!contactName || !contactEmail || !contactMessage) {
      alert('Please fill in all required fields');
      return;
    }
    // Simulate form submission
    contactSubmitted = true;
    contactName = '';
    contactEmail = '';
    contactSubject = '';
    contactMessage = '';
  }
</script>

<svelte:head>
  <title>Help & FAQ - Travel App</title>
</svelte:head>

<div class="help-page">
  <header class="header">
    <div class="header-content">
      <button class="back-btn" onclick={() => history.back()}>
        ← Back
      </button>
      <h1>❓ Help Center</h1>
    </div>
  </header>

  <main class="main-content">
    <!-- Search -->
    <div class="search-section">
      <h2>How can we help you?</h2>
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input 
          type="text" 
          placeholder="Search for help..."
          bind:value={searchQuery}
        />
      </div>
    </div>

    <!-- Categories -->
    <div class="category-tabs">
      {#each categories as category}
        <button 
          class="category-tab"
          class:active={selectedCategory === category}
          onclick={() => selectedCategory = category}
        >
          {category}
        </button>
      {/each}
    </div>

    <!-- FAQs -->
    <div class="faq-section">
      <h3>Frequently Asked Questions</h3>
      
      {#if filteredFaqs.length === 0}
        <div class="no-results">
          <p>No results found for "{searchQuery}"</p>
          <p>Try different keywords or browse by category</p>
        </div>
      {:else}
        <div class="faq-list">
          {#each filteredFaqs as faq, index}
            {@const globalIndex = faqs.indexOf(faq)}
            <div class="faq-item" class:expanded={expandedFaq === globalIndex}>
              <button class="faq-question" onclick={() => toggleFaq(globalIndex)}>
                <span class="category-tag">{faq.category}</span>
                <span class="question-text">{faq.question}</span>
                <span class="expand-icon">{expandedFaq === globalIndex ? '−' : '+'}</span>
              </button>
              {#if expandedFaq === globalIndex}
                <div class="faq-answer">
                  <p>{faq.answer}</p>
                </div>
              {/if}
            </div>
          {/each}
        </div>
      {/if}
    </div>

    <!-- Contact Form -->
    <div class="contact-section">
      <h3>📬 Contact Support</h3>
      
      {#if contactSubmitted}
        <div class="success-message">
          <span class="success-icon">✓</span>
          <h4>Message Sent!</h4>
          <p>We'll get back to you within 24 hours.</p>
          <button onclick={() => contactSubmitted = false}>Send Another Message</button>
        </div>
      {:else}
        <form onsubmit={submitContact}>
          <div class="form-row">
            <div class="form-group">
              <label for="contactName">Name *</label>
              <input 
                type="text" 
                id="contactName"
                placeholder="Your name"
                bind:value={contactName}
              />
            </div>
            <div class="form-group">
              <label for="contactEmail">Email *</label>
              <input 
                type="email" 
                id="contactEmail"
                placeholder="your@email.com"
                bind:value={contactEmail}
              />
            </div>
          </div>
          
          <div class="form-group">
            <label for="contactSubject">Subject</label>
            <select id="contactSubject" bind:value={contactSubject}>
              <option value="">Select a topic</option>
              <option value="booking">Booking Issue</option>
              <option value="payment">Payment Problem</option>
              <option value="account">Account Help</option>
              <option value="technical">Technical Support</option>
              <option value="feedback">Feedback</option>
              <option value="other">Other</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="contactMessage">Message *</label>
            <textarea 
              id="contactMessage"
              placeholder="Describe your issue..."
              rows="5"
              bind:value={contactMessage}
            ></textarea>
          </div>
          
          <button type="submit" class="submit-btn">Send Message</button>
        </form>
      {/if}
    </div>

    <!-- Quick Links -->
    <div class="quick-links">
      <h3>Quick Links</h3>
      <div class="links-grid">
        <a href="/bookings" class="quick-link">
          <span class="link-icon">📋</span>
          <span>My Bookings</span>
        </a>
        <a href="/wishlist" class="quick-link">
          <span class="link-icon">❤️</span>
          <span>My Wishlist</span>
        </a>
        <a href="/settings" class="quick-link">
          <span class="link-icon">⚙️</span>
          <span>Settings</span>
        </a>
        <a href="/deals" class="quick-link">
          <span class="link-icon">🏷️</span>
          <span>View Deals</span>
        </a>
      </div>
    </div>

    <!-- Contact Info -->
    <div class="contact-info">
      <div class="info-card">
        <span class="info-icon">📧</span>
        <h4>Email</h4>
        <p>support@travelapp.com</p>
      </div>
      <div class="info-card">
        <span class="info-icon">📞</span>
        <h4>Phone</h4>
        <p>1-800-TRAVEL (872-835)</p>
      </div>
      <div class="info-card">
        <span class="info-icon">⏰</span>
        <h4>Hours</h4>
        <p>24/7 Support Available</p>
      </div>
    </div>
  </main>
</div>

<style>
  .help-page {
    min-height: 100vh;
    background: #f8f9fa;
  }

  .header {
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }

  .header-content {
    max-width: 800px;
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
    font-size: 1.25rem;
    margin: 0;
  }

  .main-content {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
  }

  .search-section {
    text-align: center;
    margin-bottom: 2rem;
  }

  .search-section h2 {
    color: #1a1a2e;
    margin-bottom: 1rem;
  }

  .search-box {
    display: flex;
    align-items: center;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    max-width: 500px;
    margin: 0 auto;
  }

  .search-icon {
    font-size: 1.25rem;
    margin-right: 0.75rem;
  }

  .search-box input {
    flex: 1;
    border: none;
    font-size: 1rem;
    outline: none;
  }

  .category-tabs {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
    margin-bottom: 2rem;
  }

  .category-tab {
    padding: 0.75rem 1.25rem;
    border: 1px solid #e0e0e0;
    border-radius: 20px;
    background: white;
    font-size: 0.875rem;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
  }

  .category-tab:hover {
    border-color: #667eea;
  }

  .category-tab.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
  }

  .faq-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
  }

  .faq-section h3 {
    margin: 0 0 1rem;
    color: #1a1a2e;
  }

  .no-results {
    text-align: center;
    padding: 2rem;
    color: #666;
  }

  .faq-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .faq-item {
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
  }

  .faq-question {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
  }

  .category-tag {
    background: #f0f4ff;
    color: #667eea;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    flex-shrink: 0;
  }

  .question-text {
    flex: 1;
    font-weight: 500;
    color: #1a1a2e;
  }

  .expand-icon {
    font-size: 1.5rem;
    color: #999;
    flex-shrink: 0;
  }

  .faq-answer {
    padding: 0 1rem 1rem;
    border-top: 1px solid #f5f5f5;
    margin-top: -0.5rem;
    padding-top: 1rem;
  }

  .faq-answer p {
    color: #666;
    line-height: 1.6;
    margin: 0;
  }

  .contact-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
  }

  .contact-section h3 {
    margin: 0 0 1rem;
    color: #1a1a2e;
  }

  .success-message {
    text-align: center;
    padding: 2rem;
  }

  .success-icon {
    width: 60px;
    height: 60px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 1rem;
  }

  .success-message h4 {
    color: #1a1a2e;
    margin: 0 0 0.5rem;
  }

  .success-message p {
    color: #666;
    margin-bottom: 1.5rem;
  }

  .success-message button {
    background: #667eea;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
  }

  .form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
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
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 1rem;
    font-family: inherit;
  }

  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: #667eea;
  }

  .submit-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
  }

  .quick-links {
    margin-bottom: 2rem;
  }

  .quick-links h3 {
    color: #1a1a2e;
    margin: 0 0 1rem;
  }

  .links-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
  }

  .quick-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    text-decoration: none;
    color: #1a1a2e;
    transition: transform 0.2s;
  }

  .quick-link:hover {
    transform: translateY(-2px);
  }

  .link-icon {
    font-size: 1.5rem;
  }

  .quick-link span:last-child {
    font-size: 0.875rem;
    font-weight: 500;
  }

  .contact-info {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
  }

  .info-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
  }

  .info-icon {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.5rem;
  }

  .info-card h4 {
    margin: 0 0 0.25rem;
    color: #1a1a2e;
    font-size: 1rem;
  }

  .info-card p {
    color: #666;
    margin: 0;
    font-size: 0.875rem;
  }

  @media (max-width: 640px) {
    .form-row {
      grid-template-columns: 1fr;
    }
    
    .links-grid {
      grid-template-columns: repeat(2, 1fr);
    }
    
    .contact-info {
      grid-template-columns: 1fr;
    }
  }
</style>
