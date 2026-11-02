<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { jobsService, type JobPosting } from '$lib/services/jobs';

  interface Job {
    id: number;
    title: string;
    company: string;
    location: string;
    salary_min: number;
    salary_max: number;
    job_type: string;
    experience_level: string;
    status: string;
    description?: string;
    requirements?: string[];
    benefits?: string[];
    posted_date?: string;
    company_logo?: string;
    company_description?: string;
    company_size?: string;
    company_website?: string;
    skills?: string[];
    application_count?: number;
  }

  interface Application {
    jobId: number;
    appliedAt: string;
    status: string;
  }

  let jobs: Job[] = [];
  let loading = false;
  let error = '';
  let searchQuery = '';
  let selectedLocation = '';
  let selectedType = '';
  let selectedExperience = '';
  
  let activeTab: 'all' | 'saved' | 'applied' | 'recommendations' = 'all';
  let selectedJob: Job | null = null;
  let showApplyModal = false;
  let showJobDetails = false;
  
  let savedJobs: Job[] = [];
  let applications: Application[] = [];
  let recommendedJobs: Job[] = [];

  let applyForm = {
    fullName: '',
    email: '',
    phone: '',
    coverLetter: '',
    resume: null as File | null
  };
  let applying = false;
  let applySuccess = false;

  const locations = ['Hanoi', 'Ho Chi Minh', 'Da Nang', 'Remote', 'Hue'];
  const jobTypes = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote'];
  const experienceLevels = ['Entry Level', 'Mid Level', 'Senior', 'Lead', 'Manager'];

  onMount(() => {
    loadSavedJobs();
    loadApplications();
    loadJobs();
    loadRecommendations();
  });

  function loadSavedJobs() {
    if (typeof localStorage !== 'undefined') {
      const saved = localStorage.getItem('savedJobs');
      if (saved) {
        savedJobs = JSON.parse(saved);
      }
    }
  }

  function saveSavedJobs() {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('savedJobs', JSON.stringify(savedJobs));
    }
  }

  function loadApplications() {
    if (typeof localStorage !== 'undefined') {
      const apps = localStorage.getItem('jobApplications');
      if (apps) {
        applications = JSON.parse(apps);
      }
    }
  }

  function saveApplications() {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('jobApplications', JSON.stringify(applications));
    }
  }

  async function loadJobs() {
    loading = true;
    error = '';
    try {
      const jobData = await jobsService.getJobs(searchQuery || undefined);
      jobs = jobData.map((j: JobPosting) => ({
        id: parseInt(j.id),
        title: j.title,
        company: j.company,
        location: j.location,
        salary_min: 0,
        salary_max: 0,
        job_type: j.type,
        experience_level: '',
        status: 'active',
        description: j.description,
        requirements: j.requirements,
        posted_date: j.postedAt
      }));
    } catch (e) {
      error = 'Failed to load jobs';
      jobs = getMockJobs();
    }
    loading = false;
  }

  async function loadRecommendations() {
    try {
      const res = await fetch('/api/job-finding/recommendations');
      const data = await res.json();
      recommendedJobs = data.data || [];
    } catch (e) {
      // Use filtered jobs as recommendations
      recommendedJobs = jobs.slice(0, 5);
    }
  }

  function getMockJobs(): Job[] {
    return [
      {
        id: 1,
        title: 'Senior Frontend Developer',
        company: 'TechViet Solutions',
        location: 'Hanoi',
        salary_min: 30000000,
        salary_max: 50000000,
        job_type: 'Full-time',
        experience_level: 'Senior',
        status: 'active',
        description: 'We are looking for an experienced Frontend Developer to join our team. You will be responsible for building and maintaining web applications using modern technologies.',
        requirements: ['5+ years experience', 'React/Vue expertise', 'TypeScript proficiency', 'CSS/SCSS mastery'],
        benefits: ['Competitive salary', 'Health insurance', 'Remote work', 'Learning budget'],
        posted_date: '2026-02-10',
        company_description: 'TechViet Solutions is a leading software company specializing in web and mobile development.',
        company_size: '50-100',
        company_website: 'https://techviet.vn',
        skills: ['React', 'TypeScript', 'CSS', 'Git'],
        application_count: 12
      },
      {
        id: 2,
        title: 'UI/UX Designer',
        company: 'DesignHub Vietnam',
        location: 'Ho Chi Minh',
        salary_min: 20000000,
        salary_max: 35000000,
        job_type: 'Full-time',
        experience_level: 'Mid Level',
        status: 'active',
        description: 'Join our creative team to design beautiful and intuitive user interfaces.',
        requirements: ['3+ years UI/UX experience', 'Figma proficiency', 'User research skills', 'Portfolio required'],
        benefits: ['Flexible hours', 'Creative workspace', 'Team activities'],
        posted_date: '2026-02-08',
        company_description: 'DesignHub Vietnam creates stunning digital experiences for clients worldwide.',
        company_size: '20-50',
        company_website: 'https://designhub.vn',
        skills: ['Figma', 'UI Design', 'UX Research', 'Prototyping'],
        application_count: 8
      },
      {
        id: 3,
        title: 'Backend Engineer',
        company: 'CloudScale Asia',
        location: 'Remote',
        salary_min: 35000000,
        salary_max: 60000000,
        job_type: 'Full-time',
        experience_level: 'Senior',
        status: 'active',
        description: 'Build scalable backend systems for our cloud platform.',
        requirements: ['Node.js/Python expertise', 'Database design', 'API development', 'Cloud services'],
        benefits: ['Fully remote', 'Stock options', 'Unlimited PTO'],
        posted_date: '2026-02-12',
        company_description: 'CloudScale Asia provides cloud infrastructure solutions for enterprises.',
        company_size: '100-200',
        company_website: 'https://cloudscale.asia',
        skills: ['Node.js', 'Python', 'AWS', 'PostgreSQL'],
        application_count: 15
      },
      {
        id: 4,
        title: 'Product Manager',
        company: 'StartupX',
        location: 'Da Nang',
        salary_min: 25000000,
        salary_max: 40000000,
        job_type: 'Full-time',
        experience_level: 'Mid Level',
        status: 'active',
        description: 'Lead product development from ideation to launch.',
        requirements: ['2+ years PM experience', 'Agile methodology', 'Data analysis', 'Communication skills'],
        benefits: ['Equity', 'Fast-paced environment', 'Career growth'],
        posted_date: '2026-02-11',
        company_description: 'StartupX is a rapidly growing tech startup in central Vietnam.',
        company_size: '10-20',
        company_website: 'https://startupx.vn',
        skills: ['Product Strategy', 'Agile', 'Analytics', 'Roadmapping'],
        application_count: 6
      },
      {
        id: 5,
        title: 'Mobile Developer',
        company: 'AppNation',
        location: 'Ho Chi Minh',
        salary_min: 22000000,
        salary_max: 40000000,
        job_type: 'Full-time',
        experience_level: 'Mid Level',
        status: 'active',
        description: 'Develop cross-platform mobile applications.',
        requirements: ['React Native/Flutter experience', 'iOS/Android knowledge', 'REST APIs'],
        benefits: ['Latest tech', 'Gym membership', 'Free lunch'],
        posted_date: '2026-02-09',
        company_description: 'AppNation creates popular mobile apps with millions of downloads.',
        company_size: '50-100',
        company_website: 'https://appnation.vn',
        skills: ['React Native', 'Flutter', 'Firebase', 'REST APIs'],
        application_count: 20
      },
      {
        id: 6,
        title: 'DevOps Engineer',
        company: 'InfraCore',
        location: 'Remote',
        salary_min: 40000000,
        salary_max: 70000000,
        job_type: 'Full-time',
        experience_level: 'Senior',
        status: 'active',
        description: 'Manage CI/CD pipelines and cloud infrastructure.',
        requirements: ['Kubernetes/Docker', 'AWS/GCP', 'Terraform', 'CI/CD expertise'],
        benefits: ['Top-tier salary', 'Remote work', 'Conference budget'],
        posted_date: '2026-02-07',
        company_description: 'InfraCore provides infrastructure solutions for fast-growing companies.',
        company_size: '30-50',
        company_website: 'https://infracore.io',
        skills: ['Kubernetes', 'Docker', 'AWS', 'Terraform'],
        application_count: 9
      },
      {
        id: 7,
        title: 'Data Scientist',
        company: 'AI Dynamics',
        location: 'Hanoi',
        salary_min: 35000000,
        salary_max: 55000000,
        job_type: 'Full-time',
        experience_level: 'Senior',
        status: 'active',
        description: 'Build ML models and analyze complex datasets.',
        requirements: ['Python/R expertise', 'Machine Learning', 'Statistics', 'SQL'],
        benefits: ['Research budget', 'Publication support', 'Flexible hours'],
        posted_date: '2026-02-06',
        company_description: 'AI Dynamics leads in AI/ML solutions for Southeast Asia.',
        company_size: '40-60',
        company_website: 'https://aidynamics.ai',
        skills: ['Python', 'TensorFlow', 'SQL', 'Statistics'],
        application_count: 11
      },
      {
        id: 8,
        title: 'Marketing Intern',
        company: 'GrowthHack Vietnam',
        location: 'Ho Chi Minh',
        salary_min: 5000000,
        salary_max: 8000000,
        job_type: 'Internship',
        experience_level: 'Entry Level',
        status: 'active',
        description: 'Learn digital marketing with hands-on experience.',
        requirements: ['Marketing interest', 'Social media savvy', 'Basic design skills'],
        benefits: ['Mentorship', 'Certificate', 'Flexible schedule'],
        posted_date: '2026-02-12',
        company_description: 'GrowthHack Vietnam helps startups scale through innovative marketing.',
        company_size: '10-20',
        company_website: 'https://growthhack.vn',
        skills: ['Social Media', 'Content Creation', 'Analytics'],
        application_count: 25
      }
    ];
  }

  function formatSalary(min: number, max: number): string {
    const format = (n: number) => {
      if (n >= 1000000) return `₫${(n / 1000000).toFixed(0)}M`;
      return `₫${n.toLocaleString()}`;
    };
    return `${format(min)} - ${format(max)}`;
  }

  function formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diffTime = Math.abs(now.getTime() - date.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  }

  function isJobSaved(jobId: number): boolean {
    return savedJobs.some(j => j.id === jobId);
  }

  function hasApplied(jobId: number): boolean {
    return applications.some(a => a.jobId === jobId);
  }

  function toggleSaveJob(job: Job) {
    if (isJobSaved(job.id)) {
      savedJobs = savedJobs.filter(j => j.id !== job.id);
    } else {
      savedJobs = [...savedJobs, job];
    }
    saveSavedJobs();
  }

  function openJobDetails(job: Job) {
    selectedJob = job;
    showJobDetails = true;
  }

  function closeJobDetails() {
    showJobDetails = false;
    selectedJob = null;
  }

  function openApplyModal() {
    showApplyModal = true;
    showJobDetails = false;
    applySuccess = false;
    applyForm = {
      fullName: '',
      email: '',
      phone: '',
      coverLetter: '',
      resume: null
    };
  }

  function closeApplyModal() {
    showApplyModal = false;
  }

  async function submitApplication() {
    if (!selectedJob) return;
    
    applying = true;
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500));
    
    const application: Application = {
      jobId: selectedJob.id,
      appliedAt: new Date().toISOString(),
      status: 'pending'
    };
    
    applications = [...applications, application];
    saveApplications();
    
    applySuccess = true;
    applying = false;
  }

  function getFilteredJobs(): Job[] {
    let filtered = jobs;
    
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(job => 
        job.title.toLowerCase().includes(query) ||
        job.company.toLowerCase().includes(query) ||
        job.description?.toLowerCase().includes(query) ||
        job.skills?.some(s => s.toLowerCase().includes(query))
      );
    }
    
    if (selectedLocation) {
      filtered = filtered.filter(job => job.location === selectedLocation);
    }
    
    if (selectedType) {
      filtered = filtered.filter(job => job.job_type === selectedType);
    }
    
    if (selectedExperience) {
      filtered = filtered.filter(job => job.experience_level === selectedExperience);
    }
    
    return filtered;
  }

  function getDisplayedJobs(): Job[] {
    switch (activeTab) {
      case 'saved':
        return savedJobs;
      case 'applied': {
        const appliedJobs = jobs.filter(j => hasApplied(j.id));
        return appliedJobs;
      }
      case 'recommendations':
        return recommendedJobs;
      default:
        return getFilteredJobs();
    }
  }

  function clearFilters() {
    searchQuery = '';
    selectedLocation = '';
    selectedType = '';
    selectedExperience = '';
  }

  function getStatusBadgeClass(status: string): string {
    switch (status) {
      case 'active': return 'badge-active';
      case 'pending': return 'badge-pending';
      case 'closed': return 'badge-closed';
      default: return 'badge-default';
    }
  }
</script>

<svelte:head>
  <title>Job Finding - Find Your Dream Job</title>
</svelte:head>

<div class="jobs-page">
  <!-- Header -->
  <header class="header">
    <div class="header-content">
      <div class="header-left">
        <a href="/dashboard" class="back-link">← Dashboard</a>
        <h1>💼 Job Finding</h1>
      </div>
      <div class="header-right">
        <div class="stats-mini">
          <span class="stat-item">
            <span class="stat-number">{jobs.length}</span>
            <span class="stat-label">Open Jobs</span>
          </span>
          <span class="stat-item">
            <span class="stat-number">{savedJobs.length}</span>
            <span class="stat-label">Saved</span>
          </span>
          <span class="stat-item">
            <span class="stat-number">{applications.length}</span>
            <span class="stat-label">Applied</span>
          </span>
        </div>
      </div>
    </div>
  </header>

  <!-- Search Section -->
  <div class="search-section">
    <div class="search-bar">
      <div class="search-input-wrapper">
        <span class="search-icon">🔍</span>
        <input 
          type="text" 
          placeholder="Search jobs, companies, skills..." 
          bind:value={searchQuery}
          class="search-input"
        />
      </div>
      
      <select bind:value={selectedLocation} class="filter-select">
        <option value="">All Locations</option>
        {#each locations as loc}
          <option value={loc}>{loc}</option>
        {/each}
      </select>
      
      <select bind:value={selectedType} class="filter-select">
        <option value="">All Types</option>
        {#each jobTypes as type}
          <option value={type}>{type}</option>
        {/each}
      </select>
      
      <select bind:value={selectedExperience} class="filter-select">
        <option value="">All Levels</option>
        {#each experienceLevels as level}
          <option value={level}>{level}</option>
        {/each}
      </select>
      
      {#if searchQuery || selectedLocation || selectedType || selectedExperience}
        <button class="clear-filters-btn" on:click={clearFilters}>
          Clear ✕
        </button>
      {/if}
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <button 
      class="tab" 
      class:active={activeTab === 'all'}
      on:click={() => activeTab = 'all'}
    >
      All Jobs
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'recommendations'}
      on:click={() => activeTab = 'recommendations'}
    >
      Recommended
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'saved'}
      on:click={() => activeTab = 'saved'}
    >
      Saved ({savedJobs.length})
    </button>
    <button 
      class="tab" 
      class:active={activeTab === 'applied'}
      on:click={() => activeTab = 'applied'}
    >
      Applied ({applications.length})
    </button>
  </div>

  <!-- Main Content -->
  <main class="main-content">
    {#if loading}
      <div class="loading">
        <div class="spinner"></div>
        <p>Loading jobs...</p>
      </div>
    {:else if error}
      <div class="error-state">
        <p>{error}</p>
        <button class="retry-btn" on:click={loadJobs}>Try Again</button>
      </div>
    {:else}
      <div class="results-info">
        <span>{getDisplayedJobs().length} jobs found</span>
      </div>
      
      {#if getDisplayedJobs().length === 0}
        <div class="empty-state">
          <div class="empty-icon">💼</div>
          <h3>No jobs found</h3>
          <p>Try adjusting your search or filters</p>
          {#if activeTab === 'saved'}
            <p class="empty-hint">Save jobs to view them here</p>
          {/if}
        </div>
      {:else}
        <div class="jobs-grid">
          {#each getDisplayedJobs() as job (job.id)}
            <div class="job-card" on:click={() => openJobDetails(job)}>
              <div class="job-card-header">
                <div class="company-logo">
                  {job.company.charAt(0)}
                </div>
                <div class="job-title-section">
                  <h3>{job.title}</h3>
                  <span class="company-name">{job.company}</span>
                </div>
                <button 
                  class="save-btn" 
                  class:saved={isJobSaved(job.id)}
                  on:click|stopPropagation={() => toggleSaveJob(job)}
                >
                  {isJobSaved(job.id) ? '❤️' : '🤍'}
                </button>
              </div>
              
              <div class="job-meta">
                <span class="meta-item">📍 {job.location}</span>
                <span class="meta-item">💰 {formatSalary(job.salary_min, job.salary_max)}</span>
                <span class="meta-item">📋 {job.job_type}</span>
                <span class="meta-item">⭐ {job.experience_level}</span>
              </div>
              
              {#if job.skills && job.skills.length > 0}
                <div class="skills-list">
                  {#each job.skills.slice(0, 4) as skill}
                    <span class="skill-tag">{skill}</span>
                  {/each}
                  {#if job.skills.length > 4}
                    <span class="skill-more">+{job.skills.length - 4}</span>
                  {/if}
                </div>
              {/if}
              
              <div class="job-card-footer">
                <span class="status-badge {getStatusBadgeClass(job.status)}">
                  {job.status}
                </span>
                <span class="posted-date">
                  {job.posted_date ? formatDate(job.posted_date) : 'Recently posted'}
                </span>
              </div>
              
              {#if hasApplied(job.id)}
                <div class="applied-badge">
                  ✓ Applied
                </div>
              {/if}
            </div>
          {/each}
        </div>
      {/if}
    {/if}
  </main>
</div>

<!-- Job Details Modal -->
{#if showJobDetails && selectedJob}
  <div class="modal-overlay" on:click={closeJobDetails}>
    <div class="modal-content job-details-modal" on:click|stopPropagation>
      <button class="close-btn" on:click={closeJobDetails}>✕</button>
      
      <div class="job-details-header">
        <div class="company-logo-large">
          {selectedJob.company.charAt(0)}
        </div>
        <div class="job-details-title">
          <h2>{selectedJob.title}</h2>
          <p class="company-link">{selectedJob.company}</p>
        </div>
      </div>
      
      <div class="job-details-meta">
        <span>📍 {selectedJob.location}</span>
        <span>💰 {formatSalary(selectedJob.salary_min, selectedJob.salary_max)}</span>
        <span>📋 {selectedJob.job_type}</span>
        <span>⭐ {selectedJob.experience_level}</span>
      </div>
      
      {#if selectedJob.description}
        <div class="details-section">
          <h3>Job Description</h3>
          <p>{selectedJob.description}</p>
        </div>
      {/if}
      
      {#if selectedJob.requirements && selectedJob.requirements.length > 0}
        <div class="details-section">
          <h3>Requirements</h3>
          <ul>
            {#each selectedJob.requirements as req}
              <li>{req}</li>
            {/each}
          </ul>
        </div>
      {/if}
      
      {#if selectedJob.benefits && selectedJob.benefits.length > 0}
        <div class="details-section">
          <h3>Benefits</h3>
          <ul class="benefits-list">
            {#each selectedJob.benefits as benefit}
              <li>✓ {benefit}</li>
            {/each}
          </ul>
        </div>
      {/if}
      
      {#if selectedJob.skills && selectedJob.skills.length > 0}
        <div class="details-section">
          <h3>Required Skills</h3>
          <div class="skills-list">
            {#each selectedJob.skills as skill}
              <span class="skill-tag">{skill}</span>
            {/each}
          </div>
        </div>
      {/if}
      
      <!-- Company Info -->
      <div class="company-info-section">
        <h3>About the Company</h3>
        <div class="company-info-grid">
          <div class="company-info-item">
            <span class="info-label">Company</span>
            <span class="info-value">{selectedJob.company}</span>
          </div>
          <div class="company-info-item">
            <span class="info-label">Size</span>
            <span class="info-value">{selectedJob.company_size || 'Not specified'}</span>
          </div>
          {#if selectedJob.company_website}
            <div class="company-info-item">
              <span class="info-label">Website</span>
              <a href={selectedJob.company_website} target="_blank" class="info-link">
                {selectedJob.company_website.replace('https://', '')}
              </a>
            </div>
          {/if}
        </div>
        {#if selectedJob.company_description}
          <p class="company-description">{selectedJob.company_description}</p>
        {/if}
      </div>
      
      <div class="job-details-footer">
        {#if hasApplied(selectedJob.id)}
          <button class="btn-secondary" disabled>
            ✓ Already Applied
          </button>
        {:else}
          <button class="btn-primary" on:click={openApplyModal}>
            Apply Now
          </button>
        {/if}
        <button 
          class="btn-secondary"
          class:saved={isJobSaved(selectedJob.id)}
          on:click={() => toggleSaveJob(selectedJob)}
        >
          {isJobSaved(selectedJob.id) ? '❤️ Saved' : '🤍 Save Job'}
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Apply Modal -->
{#if showApplyModal && selectedJob}
  <div class="modal-overlay" on:click={closeApplyModal}>
    <div class="modal-content apply-modal" on:click|stopPropagation>
      <button class="close-btn" on:click={closeApplyModal}>✕</button>
      
      {#if applySuccess}
        <div class="success-state">
          <div class="success-icon">🎉</div>
          <h2>Application Submitted!</h2>
          <p>Your application for {selectedJob.title} at {selectedJob.company} has been sent successfully.</p>
          <button class="btn-primary" on:click={closeApplyModal}>
            Done
          </button>
        </div>
      {:else}
        <div class="apply-form-header">
          <h2>Apply for this Position</h2>
          <p>{selectedJob.title} at {selectedJob.company}</p>
        </div>
        
        <form class="apply-form" on:submit|preventDefault={submitApplication}>
          <div class="form-group">
            <label for="fullName">Full Name *</label>
            <input 
              id="fullName"
              type="text" 
              bind:value={applyForm.fullName}
              required
              placeholder="Enter your full name"
            />
          </div>
          
          <div class="form-group">
            <label for="email">Email *</label>
            <input 
              id="email"
              type="email" 
              bind:value={applyForm.email}
              required
              placeholder="your.email@example.com"
            />
          </div>
          
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input 
              id="phone"
              type="tel" 
              bind:value={applyForm.phone}
              placeholder="+84 xxx xxx xxx"
            />
          </div>
          
          <div class="form-group">
            <label for="coverLetter">Cover Letter</label>
            <textarea 
              id="coverLetter"
              bind:value={applyForm.coverLetter}
              rows="4"
              placeholder="Tell us why you're a great fit for this role..."
            ></textarea>
          </div>
          
          <div class="form-group">
            <label for="resume">Resume/CV</label>
            <div class="file-input-wrapper">
              <input 
                id="resume"
                type="file"
                accept=".pdf,.doc,.docx"
                on:change={(e) => applyForm.resume = e.currentTarget.files?.[0] || null}
              />
              <span class="file-hint">PDF, DOC, or DOCX (max 5MB)</span>
            </div>
          </div>
          
          <div class="form-actions">
            <button type="button" class="btn-secondary" on:click={closeApplyModal}>
              Cancel
            </button>
            <button type="submit" class="btn-primary" disabled={applying}>
              {#if applying}
                <span class="spinner-small"></span>
                Submitting...
              {:else}
                Submit Application
              {/if}
            </button>
          </div>
        </form>
      {/if}
    </div>
  </div>
{/if}

<style>
  .jobs-page {
    min-height: 100vh;
    background: #f8fafc;
  }

  /* Header */
  .header {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 2rem;
  }

  .header-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .back-link {
    color: #64748b;
    text-decoration: none;
    font-size: 0.875rem;
  }

  .back-link:hover {
    color: #334155;
  }

  .header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: #1e293b;
  }

  .stats-mini {
    display: flex;
    gap: 1.5rem;
  }

  .stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .stat-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: #667eea;
  }

  .stat-label {
    font-size: 0.75rem;
    color: #64748b;
  }

  /* Search Section */
  .search-section {
    background: white;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .search-bar {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .search-input-wrapper {
    flex: 1;
    min-width: 250px;
    position: relative;
  }

  .search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1rem;
  }

  .search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: border-color 0.2s;
  }

  .search-input:focus {
    outline: none;
    border-color: #667eea;
  }

  .filter-select {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    font-size: 0.95rem;
    cursor: pointer;
    min-width: 140px;
  }

  .filter-select:focus {
    outline: none;
    border-color: #667eea;
  }

  .clear-filters-btn {
    padding: 0.75rem 1rem;
    background: #f1f5f9;
    border: none;
    border-radius: 8px;
    color: #64748b;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .clear-filters-btn:hover {
    background: #e2e8f0;
  }

  /* Tabs */
  .tabs {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem 2rem;
    display: flex;
    gap: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .tab {
    padding: 0.75rem 1.5rem;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: #64748b;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;
  }

  .tab:hover {
    background: #f1f5f9;
    color: #334155;
  }

  .tab.active {
    background: #667eea;
    color: white;
  }

  /* Main Content */
  .main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  .results-info {
    margin-bottom: 1.5rem;
    color: #64748b;
    font-size: 0.95rem;
  }

  .loading, .error-state, .empty-state {
    text-align: center;
    padding: 4rem 2rem;
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    margin: 0 0 0.5rem;
    color: #1e293b;
  }

  .empty-state p {
    margin: 0;
    color: #64748b;
  }

  .empty-hint {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: #94a3b8;
  }

  .retry-btn {
    margin-top: 1rem;
    padding: 0.75rem 1.5rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.95rem;
  }

  /* Jobs Grid */
  .jobs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
  }

  .job-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
  }

  .job-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .job-card-header {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .company-logo {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 700;
    flex-shrink: 0;
  }

  .job-title-section {
    flex: 1;
    min-width: 0;
  }

  .job-title-section h3 {
    margin: 0 0 0.25rem;
    font-size: 1.1rem;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .company-name {
    color: #667eea;
    font-size: 0.9rem;
  }

  .save-btn {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.25rem;
    opacity: 0.6;
    transition: opacity 0.2s;
  }

  .save-btn:hover, .save-btn.saved {
    opacity: 1;
  }

  .job-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
    color: #64748b;
    font-size: 0.875rem;
  }

  .meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
  }

  .skills-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .skill-tag {
    padding: 0.25rem 0.75rem;
    background: #f1f5f9;
    border-radius: 9999px;
    font-size: 0.75rem;
    color: #475569;
  }

  .skill-more {
    padding: 0.25rem 0.5rem;
    color: #64748b;
    font-size: 0.75rem;
  }

  .job-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
  }

  .status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
  }

  .badge-active {
    background: #d1fae5;
    color: #065f46;
  }

  .badge-pending {
    background: #fef3c7;
    color: #92400e;
  }

  .badge-closed {
    background: #fee2e2;
    color: #991b1b;
  }

  .badge-default {
    background: #f1f5f9;
    color: #475569;
  }

  .posted-date {
    color: #94a3b8;
    font-size: 0.8rem;
  }

  .applied-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.25rem 0.75rem;
    background: #667eea;
    color: white;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
  }

  /* Modals */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
    animation: fadeIn 0.2s;
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  .modal-content {
    background: white;
    border-radius: 16px;
    max-width: 700px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    animation: slideUp 0.3s;
  }

  @keyframes slideUp {
    from { 
      opacity: 0;
      transform: translateY(20px);
    }
    to { 
      opacity: 1;
      transform: translateY(0);
    }
  }

  .close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: #f1f5f9;
    color: #64748b;
    cursor: pointer;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .close-btn:hover {
    background: #e2e8f0;
  }

  /* Job Details Modal */
  .job-details-modal {
    padding: 2rem;
  }

  .job-details-header {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .company-logo-large {
    width: 72px;
    height: 72px;
    border-radius: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    flex-shrink: 0;
  }

  .job-details-title h2 {
    margin: 0 0 0.5rem;
    color: #1e293b;
    font-size: 1.5rem;
  }

  .company-link {
    margin: 0;
    color: #667eea;
    font-size: 1rem;
  }

  .job-details-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    color: #475569;
  }

  .details-section {
    margin-bottom: 1.5rem;
  }

  .details-section h3 {
    margin: 0 0 0.75rem;
    color: #1e293b;
    font-size: 1.1rem;
  }

  .details-section p {
    margin: 0;
    color: #475569;
    line-height: 1.6;
  }

  .details-section ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #475569;
  }

  .details-section li {
    margin-bottom: 0.5rem;
  }

  .benefits-list li {
    color: #065f46;
  }

  .company-info-section {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 1.5rem;
  }

  .company-info-section h3 {
    margin: 0 0 1rem;
    color: #1e293b;
  }

  .company-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .company-info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .info-label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .info-value {
    color: #1e293b;
    font-weight: 500;
  }

  .info-link {
    color: #667eea;
    text-decoration: none;
  }

  .info-link:hover {
    text-decoration: underline;
  }

  .company-description {
    margin: 0;
    color: #475569;
    font-size: 0.95rem;
    line-height: 1.6;
  }

  .job-details-footer {
    display: flex;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
  }

  .btn-primary {
    flex: 1;
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: opacity 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }

  .btn-primary:hover:not(:disabled) {
    opacity: 0.9;
  }

  .btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .btn-secondary {
    padding: 0.875rem 1.5rem;
    background: #f1f5f9;
    color: #475569;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
  }

  .btn-secondary:hover:not(:disabled) {
    background: #e2e8f0;
  }

  .btn-secondary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .btn-secondary.saved {
    background: #fee2e2;
    color: #991b1b;
  }

  /* Apply Modal */
  .apply-modal {
    padding: 2rem;
    max-width: 550px;
  }

  .apply-form-header {
    margin-bottom: 1.5rem;
  }

  .apply-form-header h2 {
    margin: 0 0 0.5rem;
    color: #1e293b;
  }

  .apply-form-header p {
    margin: 0;
    color: #64748b;
  }

  .apply-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .form-group label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #374151;
  }

  .form-group input,
  .form-group textarea {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: border-color 0.2s;
  }

  .form-group input:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: #667eea;
  }

  .form-group textarea {
    resize: vertical;
    font-family: inherit;
  }

  .file-input-wrapper input {
    padding: 0.5rem 0;
  }

  .file-hint {
    font-size: 0.8rem;
    color: #94a3b8;
  }

  .form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 0.5rem;
  }

  .form-actions .btn-secondary {
    flex: 1;
  }

  .form-actions .btn-primary {
    flex: 2;
  }

  .spinner-small {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  /* Success State */
  .success-state {
    text-align: center;
    padding: 2rem;
  }

  .success-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .success-state h2 {
    margin: 0 0 0.5rem;
    color: #1e293b;
  }

  .success-state p {
    margin: 0 0 1.5rem;
    color: #64748b;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .header-content {
      flex-direction: column;
      gap: 1rem;
    }

    .search-bar {
      flex-direction: column;
    }

    .search-input-wrapper {
      min-width: 100%;
    }

    .filter-select {
      width: 100%;
    }

    .tabs {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .tab {
      white-space: nowrap;
    }

    .jobs-grid {
      grid-template-columns: 1fr;
    }

    .job-details-footer {
      flex-direction: column;
    }

    .form-actions {
      flex-direction: column;
    }
  }
</style>
