<script lang="ts">
  import { onMount } from 'svelte';
  import { fly, scale, fade } from 'svelte/transition';

  // Types
  interface Course {
    id: number;
    title: string;
    description: string;
    category: string;
    level: string;
    price: number;
    duration: number;
    instructor_id: number;
    instructor_name: string;
    instructor_avatar: string;
    status: string;
    thumbnail: string;
    lessons_count: number;
    students_count: number;
    rating: number;
    reviews_count: number;
    requirements: string[];
    objectives: string[];
    tags: string[];
  }

  interface Enrollment {
    courseId: number;
    enrolledAt: string;
    progress: number;
    completedLessons: number[];
    lastAccessedAt: string;
  }

  // Sample data
  const categories = [
    { id: 'all', name: 'All Courses', icon: '📚' },
    { id: 'programming', name: 'Programming', icon: '💻' },
    { id: 'design', name: 'Design', icon: '🎨' },
    { id: 'business', name: 'Business', icon: '💼' },
    { id: 'marketing', name: 'Marketing', icon: '📈' },
    { id: 'languages', name: 'Languages', icon: '🌍' },
    { id: 'data-science', name: 'Data Science', icon: '📊' },
    { id: 'photography', name: 'Photography', icon: '📷' }
  ];

  const levels = ['all', 'beginner', 'intermediate', 'advanced'];

  const courses: Course[] = [
    {
      id: 1,
      title: 'Complete Web Development Bootcamp',
      description: 'Learn HTML, CSS, JavaScript, React, Node.js and more. Build real-world projects from scratch.',
      category: 'programming',
      level: 'beginner',
      price: 1999000,
      duration: 7200,
      instructor_id: 1,
      instructor_name: 'Nguyen Van A',
      instructor_avatar: 'A',
      status: 'active',
      thumbnail: 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=400',
      lessons_count: 156,
      students_count: 12453,
      rating: 4.8,
      reviews_count: 3421,
      requirements: ['Basic computer skills', 'No programming experience needed'],
      objectives: ['Build responsive websites', 'Create full-stack applications', 'Deploy to production'],
      tags: ['web development', 'javascript', 'react', 'node.js']
    },
    {
      id: 2,
      title: 'UI/UX Design Masterclass',
      description: 'Master Figma, design systems, user research, and create stunning interfaces.',
      category: 'design',
      level: 'intermediate',
      price: 1499000,
      duration: 4800,
      instructor_id: 2,
      instructor_name: 'Tran Thi B',
      instructor_avatar: 'B',
      status: 'active',
      thumbnail: 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=400',
      lessons_count: 89,
      students_count: 8732,
      rating: 4.9,
      reviews_count: 2156,
      requirements: ['Basic design knowledge', 'Figma installed'],
      objectives: ['Create professional designs', 'Build design systems', 'Conduct user research'],
      tags: ['ui', 'ux', 'figma', 'design']
    },
    {
      id: 3,
      title: 'Python for Data Science',
      description: 'Learn Python, Pandas, NumPy, and machine learning fundamentals.',
      category: 'data-science',
      level: 'intermediate',
      price: 2499000,
      duration: 5400,
      instructor_id: 3,
      instructor_name: 'Le Van C',
      instructor_avatar: 'C',
      status: 'active',
      thumbnail: 'https://images.unsplash.com/photo-1526379095098-d400fd0bf935?w=400',
      lessons_count: 112,
      students_count: 15678,
      rating: 4.7,
      reviews_count: 4521,
      requirements: ['Basic Python knowledge', 'Math background helpful'],
      objectives: ['Analyze data with Pandas', 'Build ML models', 'Visualize insights'],
      tags: ['python', 'data science', 'machine learning', 'pandas']
    },
    {
      id: 4,
      title: 'Digital Marketing Strategy',
      description: 'SEO, social media marketing, content marketing, and paid advertising.',
      category: 'marketing',
      level: 'beginner',
      price: 999000,
      duration: 3600,
      instructor_id: 4,
      instructor_name: 'Pham Thi D',
      instructor_avatar: 'D',
      status: 'active',
      thumbnail: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400',
      lessons_count: 67,
      students_count: 9234,
      rating: 4.6,
      reviews_count: 1876,
      requirements: ['No prior experience needed', 'Basic computer skills'],
      objectives: ['Create marketing campaigns', 'Optimize for SEO', 'Manage social media'],
      tags: ['marketing', 'seo', 'social media', 'advertising']
    },
    {
      id: 5,
      title: 'Business English Communication',
      description: 'Professional English for meetings, presentations, and negotiations.',
      category: 'languages',
      level: 'intermediate',
      price: 799000,
      duration: 2400,
      instructor_id: 5,
      instructor_name: 'John Smith',
      instructor_avatar: 'J',
      status: 'active',
      thumbnail: 'https://images.unsplash.com/photo-1543269865-cbf427effbad?w=400',
      lessons_count: 45,
      students_count: 6543,
      rating: 4.8,
      reviews_count: 1234,
      requirements: ['Intermediate English level', 'Desire to improve professional skills'],
      objectives: ['Speak confidently in meetings', 'Write professional emails', 'Give presentations'],
      tags: ['english', 'business', 'communication', 'professional']
    },
    {
      id: 6,
      title: 'Advanced React Patterns',
      description: 'Master advanced React concepts, hooks, context, and performance optimization.',
      category: 'programming',
      level: 'advanced',
      price: 2999000,
      duration: 4200,
      instructor_id: 1,
      instructor_name: 'Nguyen Van A',
      instructor_avatar: 'A',
      status: 'active',
      thumbnail: 'https://images.unsplash.com/photo-1633356122544-f134324a6cee?w=400',
      lessons_count: 78,
      students_count: 4532,
      rating: 4.9,
      reviews_count: 987,
      requirements: ['Strong React fundamentals', 'JavaScript ES6+ knowledge'],
      objectives: ['Build scalable React apps', 'Master hooks and context', 'Optimize performance'],
      tags: ['react', 'javascript', 'frontend', 'advanced']
    },
    {
      id: 7,
      title: 'Photography Fundamentals',
      description: 'Learn camera settings, composition, lighting, and photo editing.',
      category: 'photography',
      level: 'beginner',
      price: 899000,
      duration: 3000,
      instructor_id: 6,
      instructor_name: 'Hoang Van E',
      instructor_avatar: 'E',
      status: 'active',
      thumbnail: 'https://images.unsplash.com/photo-1542038784456-1ea8e935640e?w=400',
      lessons_count: 52,
      students_count: 7821,
      rating: 4.7,
      reviews_count: 1654,
      requirements: ['Any camera (DSLR, mirrorless, or smartphone)'],
      objectives: ['Understand camera settings', 'Master composition', 'Edit photos professionally'],
      tags: ['photography', 'camera', 'editing', 'composition']
    },
    {
      id: 8,
      title: 'Startup Business Planning',
      description: 'Create a winning business plan, pitch deck, and go-to-market strategy.',
      category: 'business',
      level: 'intermediate',
      price: 1799000,
      duration: 3900,
      instructor_id: 7,
      instructor_name: 'Vu Thi F',
      instructor_avatar: 'F',
      status: 'active',
      thumbnail: 'https://images.unsplash.com/photo-1553484771-371a605b060b?w=400',
      lessons_count: 68,
      students_count: 3456,
      rating: 4.6,
      reviews_count: 765,
      requirements: ['Basic business knowledge', 'Entrepreneurial mindset'],
      objectives: ['Write business plans', 'Create pitch decks', 'Build MVP strategies'],
      tags: ['startup', 'business', 'entrepreneurship', 'strategy']
    }
  ];

  // State
  let activeTab: 'browse' | 'enrolled' | 'progress' = 'browse';
  let selectedCategory = 'all';
  let selectedLevel = 'all';
  let searchQuery = '';
  let sortBy = 'popular';
  
  // Data from localStorage
  let enrollments: Enrollment[] = [];
  let enrolledCourses: Course[] = [];
  
  // Modal states
  let showCourseDetail = false;
  let selectedCourse: Course | null = null;
  let showEnrollSuccess = false;
  let enrolling = false;

  // Load from localStorage
  onMount(() => {
    loadEnrollments();
    loadEnrolledCourses();
  });

  function loadEnrollments() {
    if (typeof localStorage !== 'undefined') {
      const saved = localStorage.getItem('courseEnrollments');
      if (saved) {
        enrollments = JSON.parse(saved);
      }
    }
  }

  function saveEnrollments() {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('courseEnrollments', JSON.stringify(enrollments));
    }
  }

  function loadEnrolledCourses() {
    enrolledCourses = courses.filter(c => enrollments.some(e => e.courseId === c.id));
  }

  function isEnrolled(courseId: number): boolean {
    return enrollments.some(e => e.courseId === courseId);
  }

  function getEnrollment(courseId: number): Enrollment | undefined {
    return enrollments.find(e => e.courseId === courseId);
  }

  // Filter functions
  $: filteredCourses = courses
    .filter(c => {
      const matchesSearch = c.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
        c.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
        c.tags.some(t => t.toLowerCase().includes(searchQuery.toLowerCase()));
      const matchesCategory = selectedCategory === 'all' || c.category === selectedCategory;
      const matchesLevel = selectedLevel === 'all' || c.level === selectedLevel;
      return matchesSearch && matchesCategory && matchesLevel;
    })
    .sort((a, b) => {
      switch (sortBy) {
        case 'popular': return b.students_count - a.students_count;
        case 'rating': return b.rating - a.rating;
        case 'newest': return b.id - a.id;
        case 'price-low': return a.price - b.price;
        case 'price-high': return b.price - a.price;
        default: return 0;
      }
    });

  $: progressCourses = courses
    .filter(c => isEnrolled(c.id))
    .map(c => ({ course: c, enrollment: getEnrollment(c.id)! }))
    .sort((a, b) => new Date(b.enrollment.lastAccessedAt).getTime() - new Date(a.enrollment.lastAccessedAt).getTime());

  // Actions
  function openCourseDetail(course: Course) {
    selectedCourse = course;
    showCourseDetail = true;
  }

  function closeCourseDetail() {
    showCourseDetail = false;
    selectedCourse = null;
  }

  async function enrollInCourse(course: Course) {
    if (isEnrolled(course.id)) return;
    
    enrolling = true;
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    const enrollment: Enrollment = {
      courseId: course.id,
      enrolledAt: new Date().toISOString(),
      progress: 0,
      completedLessons: [],
      lastAccessedAt: new Date().toISOString()
    };
    
    enrollments = [...enrollments, enrollment];
    saveEnrollments();
    loadEnrolledCourses();
    
    enrolling = false;
    showEnrollSuccess = true;
  }

  function continueCourse(course: Course) {
    // Update last accessed
    const enrollment = getEnrollment(course.id);
    if (enrollment) {
      enrollment.lastAccessedAt = new Date().toISOString();
      enrollments = [...enrollments];
      saveEnrollments();
    }
    // In a real app, this would navigate to the course player
    alert(`Continuing course: ${course.title}`);
  }

  function clearFilters() {
    searchQuery = '';
    selectedCategory = 'all';
    selectedLevel = 'all';
    sortBy = 'popular';
  }

  // Helpers
  function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
  }

  function formatPrice(price: number): string {
    return new Intl.NumberFormat('vi-VN').format(price) + ' ₫';
  }

  function getLevelColor(level: string): string {
    switch (level) {
      case 'beginner': return '#22c55e';
      case 'intermediate': return '#f59e0b';
      case 'advanced': return '#ef4444';
      default: return '#6b7280';
    }
  }

  function getProgressWidth(progress: number): string {
    return `${Math.min(progress, 100)}%`;
  }
</script>

<svelte:head>
  <title>📚 Education - Learn New Skills</title>
</svelte:head>

<div class="education-page">
  <!-- Header -->
  <header class="header">
    <div class="header-content">
      <div class="header-left">
        <a href="/dashboard" class="back-link">← Dashboard</a>
        <h1>📚 Education</h1>
      </div>
      
      <div class="search-bar">
        <span class="search-icon">🔍</span>
        <input 
          type="text" 
          placeholder="Search courses..." 
          bind:value={searchQuery}
        />
        {#if searchQuery}
          <button class="clear-search" on:click={() => searchQuery = ''}>✕</button>
        {/if}
      </div>
      
      <div class="header-stats">
        <span class="stat">
          <span class="stat-value">{courses.length}</span>
          <span class="stat-label">Courses</span>
        </span>
        <span class="stat">
          <span class="stat-value">{enrollments.length}</span>
          <span class="stat-label">Enrolled</span>
        </span>
      </div>
    </div>
  </header>

  <!-- Main Layout -->
  <main class="main-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <!-- Categories -->
      <div class="sidebar-section">
        <h3>Categories</h3>
        <div class="category-list">
          {#each categories as category}
            <button 
              class="category-btn"
              class:active={selectedCategory === category.id}
              on:click={() => selectedCategory = category.id}
            >
              <span class="cat-icon">{category.icon}</span>
              <span class="cat-name">{category.name}</span>
              <span class="cat-count">
                {category.id === 'all' 
                  ? courses.length 
                  : courses.filter(c => c.category === category.id).length}
              </span>
            </button>
          {/each}
        </div>
      </div>

      <!-- Level Filter -->
      <div class="sidebar-section">
        <h3>Level</h3>
        <div class="level-filters">
          {#each levels as level}
            <button 
              class="level-btn"
              class:active={selectedLevel === level}
              on:click={() => selectedLevel = level}
            >
              {level === 'all' ? 'All Levels' : level}
            </button>
          {/each}
        </div>
      </div>

      <!-- Stats Card -->
      <div class="stats-card">
        <h3>Your Learning Stats</h3>
        <div class="stat-item">
          <span class="stat-icon">📖</span>
          <span class="stat-text">{enrollments.length} courses enrolled</span>
        </div>
        <div class="stat-item">
          <span class="stat-icon">✅</span>
          <span class="stat-text">
            {enrollments.reduce((sum, e) => sum + e.completedLessons.length, 0)} lessons completed
          </span>
        </div>
        <div class="stat-item">
          <span class="stat-icon">📊</span>
          <span class="stat-text">
            {Math.round(enrollments.reduce((sum, e) => sum + e.progress, 0) / Math.max(enrollments.length, 1))}% avg progress
          </span>
        </div>
      </div>
    </aside>

    <!-- Content Area -->
    <div class="content-area">
      <!-- Tabs -->
      <div class="tabs">
        <button 
          class="tab" 
          class:active={activeTab === 'browse'}
          on:click={() => activeTab = 'browse'}
        >
          📚 Browse Courses
        </button>
        <button 
          class="tab" 
          class:active={activeTab === 'enrolled'}
          on:click={() => activeTab = 'enrolled'}
        >
          🎓 My Enrolled ({enrolledCourses.length})
        </button>
        <button 
          class="tab" 
          class:active={activeTab === 'progress'}
          on:click={() => activeTab = 'progress'}
        >
          📈 In Progress ({progressCourses.filter(p => p.enrollment.progress > 0 && p.enrollment.progress < 100).length})
        </button>
      </div>

      <!-- Toolbar -->
      <div class="toolbar">
        <div class="results-info">
          {#if activeTab === 'browse'}
            <span>{filteredCourses.length} courses found</span>
            {#if selectedCategory !== 'all' || selectedLevel !== 'all' || searchQuery}
              <button class="clear-btn" on:click={clearFilters}>Clear filters</button>
            {/if}
          {:else if activeTab === 'enrolled'}
            <span>{enrolledCourses.length} enrolled courses</span>
          {:else}
            <span>{progressCourses.filter(p => p.enrollment.progress > 0).length} courses in progress</span>
          {/if}
        </div>
        
        {#if activeTab === 'browse'}
          <select bind:value={sortBy} class="sort-select">
            <option value="popular">Most Popular</option>
            <option value="rating">Highest Rated</option>
            <option value="newest">Newest</option>
            <option value="price-low">Price: Low to High</option>
            <option value="price-high">Price: High to Low</option>
          </select>
        {/if}
      </div>

      <!-- Course Grid -->
      {#if activeTab === 'browse'}
        {#if filteredCourses.length === 0}
          <div class="empty-state">
            <div class="empty-icon">🔍</div>
            <h3>No courses found</h3>
            <p>Try adjusting your search or filters</p>
            <button class="reset-btn" on:click={clearFilters}>Clear Filters</button>
          </div>
        {:else}
          <div class="course-grid">
            {#each filteredCourses as course (course.id)}
              <div class="course-card" on:click={() => openCourseDetail(course)} in:fly={{ y: 20, duration: 300 }}>
                <div class="card-image">
                  <img src={course.thumbnail} alt={course.title} />
                  <span class="level-badge" style="background: {getLevelColor(course.level)}">
                    {course.level}
                  </span>
                  {#if isEnrolled(course.id)}
                    <span class="enrolled-badge">✓ Enrolled</span>
                  {/if}
                </div>
                
                <div class="card-content">
                  <div class="category-tag">
                    {categories.find(c => c.id === course.category)?.icon} {course.category}
                  </div>
                  
                  <h3 class="course-title">{course.title}</h3>
                  
                  <div class="instructor">
                    <span class="avatar">{course.instructor_avatar}</span>
                    <span>{course.instructor_name}</span>
                  </div>
                  
                  <div class="course-meta">
                    <span>⏱ {formatDuration(course.duration)}</span>
                    <span>📖 {course.lessons_count} lessons</span>
                    <span>👥 {course.students_count.toLocaleString()}</span>
                  </div>
                  
                  <div class="course-rating">
                    <span class="stars">★</span>
                    <span class="rating-value">{course.rating}</span>
                    <span class="reviews">({course.reviews_count.toLocaleString()})</span>
                  </div>
                  
                  <div class="card-footer">
                    <span class="price">{formatPrice(course.price)}</span>
                    {#if isEnrolled(course.id)}
                      <button class="btn-continue" on:click|stopPropagation={() => continueCourse(course)}>
                        Continue
                      </button>
                    {:else}
                      <button class="btn-enroll" on:click|stopPropagation={() => openCourseDetail(course)}>
                        View Details
                      </button>
                    {/if}
                  </div>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      
      {:else if activeTab === 'enrolled'}
        {#if enrolledCourses.length === 0}
          <div class="empty-state">
            <div class="empty-icon">📚</div>
            <h3>No enrolled courses yet</h3>
            <p>Browse our catalog and start learning today!</p>
            <button class="reset-btn" on:click={() => activeTab = 'browse'}>Browse Courses</button>
          </div>
        {:else}
          <div class="course-grid">
            {#each enrolledCourses as course (course.id)}
              {@const enrollment = getEnrollment(course.id)}
              <div class="course-card enrolled" on:click={() => openCourseDetail(course)}>
                <div class="card-image">
                  <img src={course.thumbnail} alt={course.title} />
                  <span class="level-badge" style="background: {getLevelColor(course.level)}">
                    {course.level}
                  </span>
                  <span class="enrolled-badge">✓ Enrolled</span>
                </div>
                
                <div class="card-content">
                  <div class="category-tag">
                    {categories.find(c => c.id === course.category)?.icon} {course.category}
                  </div>
                  
                  <h3 class="course-title">{course.title}</h3>
                  
                  <div class="progress-section">
                    <div class="progress-bar">
                      <div class="progress-fill" style="width: {getProgressWidth(enrollment?.progress || 0)}"></div>
                    </div>
                    <span class="progress-text">{enrollment?.progress || 0}% complete</span>
                  </div>
                  
                  <div class="card-footer">
                    <span class="enrolled-date">
                      Enrolled {new Date(enrollment?.enrolledAt || '').toLocaleDateString()}
                    </span>
                    <button class="btn-continue" on:click|stopPropagation={() => continueCourse(course)}>
                      Continue
                    </button>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      
      {:else}
        {#if progressCourses.filter(p => p.enrollment.progress > 0).length === 0}
          <div class="empty-state">
            <div class="empty-icon">📈</div>
            <h3>No courses in progress</h3>
            <p>Start learning to see your progress here!</p>
            <button class="reset-btn" on:click={() => activeTab = 'browse'}>Start Learning</button>
          </div>
        {:else}
          <div class="progress-list">
            {#each progressCourses.filter(p => p.enrollment.progress > 0) as item (item.course.id)}
              <div class="progress-card">
                <img src={item.course.thumbnail} alt={item.course.title} class="progress-thumb" />
                <div class="progress-info">
                  <h3>{item.course.title}</h3>
                  <p class="instructor-name">{item.course.instructor_name}</p>
                  <div class="progress-bar-large">
                    <div class="progress-fill" style="width: {getProgressWidth(item.enrollment.progress)}"></div>
                  </div>
                  <div class="progress-details">
                    <span>{item.enrollment.progress}% complete</span>
                    <span>{item.enrollment.completedLessons.length}/{item.course.lessons_count} lessons</span>
                  </div>
                </div>
                <button class="btn-continue" on:click={() => continueCourse(item.course)}>
                  Continue
                </button>
              </div>
            {/each}
          </div>
        {/if}
      {/if}
    </div>
  </main>
</div>

<!-- Course Detail Modal -->
{#if showCourseDetail && selectedCourse}
  <div class="modal-overlay" on:click={closeCourseDetail} transition:fade>
    <div class="course-modal" on:click|stopPropagation transition:scale>
      <button class="modal-close" on:click={closeCourseDetail}>✕</button>
      
      {#if showEnrollSuccess}
        <div class="enroll-success" in:scale>
          <div class="success-icon">🎉</div>
          <h2>Successfully Enrolled!</h2>
          <p>You have been enrolled in <strong>{selectedCourse.title}</strong></p>
          <div class="success-actions">
            <button class="btn-primary" on:click={() => { showEnrollSuccess = false; closeCourseDetail(); }}>
              Continue Browsing
            </button>
            <button class="btn-secondary" on:click={() => { showEnrollSuccess = false; continueCourse(selectedCourse); }}>
              Start Learning
            </button>
          </div>
        </div>
      {:else}
        <div class="modal-content">
          <div class="modal-image">
            <img src={selectedCourse.thumbnail} alt={selectedCourse.title} />
            <span class="modal-level" style="background: {getLevelColor(selectedCourse.level)}">
              {selectedCourse.level}
            </span>
          </div>
          
          <div class="modal-details">
            <div class="modal-category">
              {categories.find(c => c.id === selectedCourse.category)?.icon} {selectedCourse.category}
            </div>
            
            <h2>{selectedCourse.title}</h2>
            
            <p class="description">{selectedCourse.description}</p>
            
            <div class="modal-rating">
              <span class="stars">★</span>
              <span class="rating-value">{selectedCourse.rating}</span>
              <span class="reviews">({selectedCourse.reviews_count.toLocaleString()} reviews)</span>
              <span class="students">{selectedCourse.students_count.toLocaleString()} students</span>
            </div>
            
            <div class="instructor-section">
              <span class="avatar-large">{selectedCourse.instructor_avatar}</span>
              <div>
                <span class="instructor-label">Instructor</span>
                <span class="instructor-name">{selectedCourse.instructor_name}</span>
              </div>
            </div>
            
            <div class="course-stats">
              <div class="stat">
                <span class="stat-icon">⏱</span>
                <span>{formatDuration(selectedCourse.duration)}</span>
              </div>
              <div class="stat">
                <span class="stat-icon">📖</span>
                <span>{selectedCourse.lessons_count} lessons</span>
              </div>
              <div class="stat">
                <span class="stat-icon">📚</span>
                <span>{selectedCourse.tags.length} topics</span>
              </div>
            </div>
            
            {#if selectedCourse.requirements.length > 0}
              <div class="requirements">
                <h4>Requirements</h4>
                <ul>
                  {#each selectedCourse.requirements as req}
                    <li>• {req}</li>
                  {/each}
                </ul>
              </div>
            {/if}
            
            {#if selectedCourse.objectives.length > 0}
              <div class="objectives">
                <h4>What you'll learn</h4>
                <ul>
                  {#each selectedCourse.objectives as obj}
                    <li>✓ {obj}</li>
                  {/each}
                </ul>
              </div>
            {/if}
            
            <div class="modal-tags">
              {#each selectedCourse.tags as tag}
                <span class="tag">#{tag}</span>
              {/each}
            </div>
            
            <div class="modal-footer">
              <div class="price-section">
                <span class="modal-price">{formatPrice(selectedCourse.price)}</span>
              </div>
              
              {#if isEnrolled(selectedCourse.id)}
                <button class="btn-continue-full" on:click={() => continueCourse(selectedCourse)}>
                  Continue Learning
                </button>
              {:else}
                <button 
                  class="btn-enroll-full" 
                  on:click={() => enrollInCourse(selectedCourse)}
                  disabled={enrolling}
                >
                  {#if enrolling}
                    <span class="spinner"></span> Enrolling...
                  {:else}
                    Enroll Now
                  {/if}
                </button>
              {/if}
            </div>
          </div>
        </div>
      {/if}
    </div>
  </div>
{/if}

<style>
  .education-page {
    min-height: 100vh;
    background: #f8fafc;
  }

  /* Header */
  .header {
    background: white;
    border-bottom: 1px solid #e2e8f0;
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
    gap: 1.5rem;
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

  .search-bar {
    flex: 1;
    max-width: 400px;
    position: relative;
  }

  .search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
  }

  .search-bar input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 50px;
    font-size: 0.95rem;
  }

  .search-bar input:focus {
    outline: none;
    border-color: #6366f1;
  }

  .clear-search {
    position: absolute;
    right: 1rem;
    background: none;
    border: none;
    cursor: pointer;
    color: #94a3b8;
  }

  .header-stats {
    display: flex;
    gap: 1.5rem;
  }

  .stat {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #6366f1;
  }

  .stat-label {
    font-size: 0.75rem;
    color: #64748b;
  }

  /* Main Layout */
  .main-layout {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
  }

  /* Sidebar */
  .sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
  }

  .sidebar-section {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  }

  .sidebar-section h3 {
    margin: 0 0 1rem;
    font-size: 0.9rem;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .category-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .category-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 0.875rem;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s;
    font-size: 0.9rem;
    color: #475569;
  }

  .category-btn:hover {
    background: #f1f5f9;
    color: #334155;
  }

  .category-btn.active {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
  }

  .cat-icon {
    font-size: 1rem;
  }

  .cat-name {
    flex: 1;
  }

  .cat-count {
    font-size: 0.75rem;
    color: #94a3b8;
    background: #f1f5f9;
    padding: 0.125rem 0.5rem;
    border-radius: 10px;
  }

  .category-btn.active .cat-count {
    background: rgba(255,255,255,0.2);
    color: white;
  }

  .level-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
  }

  .level-btn {
    padding: 0.5rem 0.875rem;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.8rem;
    text-transform: capitalize;
    transition: all 0.2s;
  }

  .level-btn:hover {
    border-color: #6366f1;
    color: #6366f1;
  }

  .level-btn.active {
    background: #6366f1;
    color: white;
    border-color: #6366f1;
  }

  .stats-card {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 12px;
    padding: 1.25rem;
    color: white;
  }

  .stats-card h3 {
    margin: 0 0 1rem;
    font-size: 0.9rem;
    color: rgba(255,255,255,0.9);
  }

  .stats-card .stat-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.5rem 0;
    font-size: 0.875rem;
  }

  .stats-card .stat-icon {
    font-size: 1rem;
  }

  /* Content Area */
  .content-area {
    min-width: 0;
  }

  .tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.5rem;
  }

  .tab {
    padding: 0.75rem 1.25rem;
    background: transparent;
    border: none;
    border-radius: 8px 8px 0 0;
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
    background: #6366f1;
    color: white;
  }

  .toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .results-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #64748b;
    font-size: 0.95rem;
  }

  .clear-btn {
    background: none;
    border: none;
    color: #6366f1;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .clear-btn:hover {
    text-decoration: underline;
  }

  .sort-select {
    padding: 0.5rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    font-size: 0.9rem;
    cursor: pointer;
  }

  /* Course Grid */
  .course-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
  }

  .course-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: all 0.3s;
  }

  .course-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.12);
  }

  .card-image {
    position: relative;
    height: 160px;
    overflow: hidden;
  }

  .card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
  }

  .course-card:hover .card-image img {
    transform: scale(1.05);
  }

  .level-badge {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    color: white;
    text-transform: capitalize;
  }

  .enrolled-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    padding: 0.25rem 0.75rem;
    background: #22c55e;
    color: white;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
  }

  .card-content {
    padding: 1rem;
  }

  .category-tag {
    font-size: 0.75rem;
    color: #6366f1;
    text-transform: capitalize;
    margin-bottom: 0.375rem;
  }

  .course-title {
    font-size: 1rem;
    margin: 0 0 0.625rem;
    color: #1e293b;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
  }

  .instructor {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    font-size: 0.8rem;
    color: #64748b;
  }

  .avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 600;
  }

  .course-meta {
    display: flex;
    gap: 0.75rem;
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.5rem;
  }

  .course-rating {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-bottom: 0.75rem;
  }

  .stars {
    color: #f59e0b;
    font-size: 0.9rem;
  }

  .rating-value {
    font-weight: 600;
    color: #1e293b;
  }

  .reviews {
    color: #94a3b8;
    font-size: 0.8rem;
  }

  .card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.75rem;
    border-top: 1px solid #f1f5f9;
  }

  .price {
    font-size: 1.125rem;
    font-weight: 700;
    color: #6366f1;
  }

  .btn-enroll, .btn-continue {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-enroll {
    background: #6366f1;
    color: white;
  }

  .btn-enroll:hover {
    background: #4f46e5;
  }

  .btn-continue {
    background: #22c55e;
    color: white;
  }

  .btn-continue:hover {
    background: #16a34a;
  }

  .enrolled-date {
    font-size: 0.75rem;
    color: #94a3b8;
  }

  /* Progress Section */
  .progress-section {
    margin-bottom: 0.75rem;
  }

  .progress-bar {
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 0.375rem;
  }

  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 3px;
    transition: width 0.3s;
  }

  .progress-text {
    font-size: 0.75rem;
    color: #64748b;
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
  }

  .empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    margin: 0 0 0.5rem;
    color: #1e293b;
  }

  .empty-state p {
    margin: 0 0 1.5rem;
    color: #64748b;
  }

  .reset-btn {
    padding: 0.75rem 1.5rem;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.95rem;
  }

  /* Progress List */
  .progress-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .progress-card {
    display: flex;
    gap: 1.5rem;
    background: white;
    border-radius: 16px;
    padding: 1.25rem;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  }

  .progress-thumb {
    width: 120px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
  }

  .progress-info {
    flex: 1;
    min-width: 0;
  }

  .progress-info h3 {
    margin: 0 0 0.25rem;
    font-size: 1.1rem;
    color: #1e293b;
  }

  .progress-info .instructor-name {
    margin: 0 0 0.75rem;
    font-size: 0.85rem;
    color: #64748b;
  }

  .progress-bar-large {
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
  }

  .progress-bar-large .progress-fill {
    border-radius: 4px;
  }

  .progress-details {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #64748b;
  }

  /* Modal */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 300;
    padding: 1rem;
  }

  .course-modal {
    background: white;
    border-radius: 20px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
  }

  .modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 36px;
    height: 36px;
    background: #f1f5f9;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1rem;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .modal-close:hover {
    background: #e2e8f0;
  }

  .modal-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }

  .modal-image {
    position: relative;
    height: 100%;
    min-height: 400px;
  }

  .modal-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .modal-level {
    position: absolute;
    bottom: 1rem;
    left: 1rem;
    padding: 0.375rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
    text-transform: capitalize;
  }

  .modal-details {
    padding: 2rem;
  }

  .modal-category {
    font-size: 0.85rem;
    color: #6366f1;
    text-transform: capitalize;
    margin-bottom: 0.5rem;
  }

  .modal-details h2 {
    margin: 0 0 1rem;
    font-size: 1.5rem;
    color: #1e293b;
    line-height: 1.3;
  }

  .description {
    color: #475569;
    line-height: 1.6;
    margin-bottom: 1rem;
  }

  .modal-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
  }

  .students {
    color: #64748b;
    margin-left: auto;
  }

  .instructor-section {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 12px;
  }

  .avatar-large {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
  }

  .instructor-label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
  }

  .instructor-name {
    display: block;
    font-weight: 500;
    color: #1e293b;
  }

  .course-stats {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 12px;
  }

  .course-stats .stat {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.85rem;
    color: #475569;
  }

  .course-stats .stat-icon {
    font-size: 1rem;
  }

  .requirements, .objectives {
    margin-bottom: 1rem;
  }

  .requirements h4, .objectives h4 {
    margin: 0 0 0.5rem;
    font-size: 0.9rem;
    color: #1e293b;
  }

  .requirements ul, .objectives ul {
    margin: 0;
    padding-left: 1.25rem;
    font-size: 0.85rem;
    color: #475569;
  }

  .requirements li, .objectives li {
    margin-bottom: 0.375rem;
  }

  .objectives li {
    color: #16a34a;
  }

  .modal-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
  }

  .tag {
    background: #f1f5f9;
    color: #475569;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
  }

  .modal-footer {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
  }

  .modal-price {
    font-size: 1.75rem;
    font-weight: 700;
    color: #6366f1;
  }

  .btn-enroll-full, .btn-continue-full {
    flex: 1;
    padding: 1rem;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }

  .btn-enroll-full {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
  }

  .btn-enroll-full:hover:not(:disabled) {
    transform: scale(1.02);
  }

  .btn-enroll-full:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  .btn-continue-full {
    background: #22c55e;
    color: white;
  }

  .spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  /* Enroll Success */
  .enroll-success {
    text-align: center;
    padding: 4rem 2rem;
  }

  .success-icon {
    font-size: 5rem;
    margin-bottom: 1.5rem;
  }

  .enroll-success h2 {
    margin: 0 0 0.5rem;
    color: #1e293b;
  }

  .enroll-success p {
    color: #475569;
    margin-bottom: 2rem;
  }

  .success-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
  }

  .btn-primary {
    padding: 0.875rem 1.5rem;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 500;
    cursor: pointer;
  }

  .btn-secondary {
    padding: 0.875rem 1.5rem;
    background: #f1f5f9;
    color: #475569;
    border: none;
    border-radius: 10px;
    font-weight: 500;
    cursor: pointer;
  }

  /* Responsive */
  @media (max-width: 1024px) {
    .main-layout {
      grid-template-columns: 1fr;
    }
    
    .sidebar {
      position: static;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1rem;
    }
    
    .sidebar-section {
      margin-bottom: 0;
    }
    
    .stats-card {
      grid-column: 1 / -1;
    }
  }

  @media (max-width: 768px) {
    .header-content {
      flex-wrap: wrap;
    }
    
    .search-bar {
      order: 3;
      max-width: 100%;
      flex-basis: 100%;
    }
    
    .tabs {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    
    .tab {
      white-space: nowrap;
    }
    
    .course-grid {
      grid-template-columns: 1fr;
    }
    
    .modal-content {
      grid-template-columns: 1fr;
    }
    
    .modal-image {
      min-height: 200px;
    }
    
    .progress-card {
      flex-direction: column;
      text-align: center;
    }
    
    .progress-thumb {
      width: 100%;
      height: 120px;
    }
    
    .progress-bar-large {
      margin-top: 0.75rem;
    }
  }
</style>
