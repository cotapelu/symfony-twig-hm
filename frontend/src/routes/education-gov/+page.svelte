<script lang="ts">
  import { goto } from '$app/navigation';

  interface School {
    id: string;
    name: string;
    type: string;
    address: string;
    students: number;
    teachers: number;
  }

  interface Course {
    id: string;
    name: string;
    category: string;
    duration: string;
    participants: number;
    status: string;
  }

  interface TrainingRecord {
    id: string;
    citizen: string;
    course: string;
    date: string;
    status: string;
    certificate: string;
  }

  const schools: School[] = [
    { id: '1', name: 'Trường Tiểu học Nguyễn Trãi', type: 'Primary', address: '123 Nguyễn Trãi', students: 850, teachers: 45 },
    { id: '2', name: 'Trường THCS Lý Thường Kiệt', type: 'Secondary', address: '456 Lý Thường Kiệt', students: 1200, teachers: 65 },
    { id: '3', name: 'Trường Mầm non Hoa Hồng', type: 'Kindergarten', address: '789 Hoàng Quốc Việt', students: 320, teachers: 25 }
  ];

  const courses: Course[] = [
    { id: '1', name: 'Nghề phổ thông', category: 'Vocational', duration: '3 tháng', participants: 45, status: 'Đang mở' },
    { id: '2', name: 'Tin học cơ bản', category: 'IT', duration: '2 tháng', participants: 60, status: 'Đang mở' },
    { id: '3', name: 'Ngoại ngữ (Anh)', category: 'Language', duration: '6 tháng', participants: 80, status: 'Sắp mở' },
    { id: '4', name: 'Kỹ năng mềm', category: 'Soft Skills', duration: '1 tháng', participants: 35, status: 'Đang mở' }
  ];

  const trainingRecords: TrainingRecord[] = [
    { id: '1', citizen: 'Nguyễn Văn A', course: 'Nghề phổ thông', date: '2026-01-15', status: 'Hoàn thành', certificate: 'Có' },
    { id: '2', citizen: 'Trần Thị B', course: 'Tin học cơ bản', date: '2026-02-01', status: 'Đang học', certificate: 'Chưa' },
    { id: '3', citizen: 'Lê Văn C', course: 'Ngoại ngữ (Anh)', date: '2026-01-20', status: 'Hoàn thành', certificate: 'Có' },
    { id: '4', citizen: 'Phạm Thị D', course: 'Kỹ năng mềm', date: '2026-02-10', status: 'Đang học', certificate: 'Chưa' }
  ];

  const stats = {
    totalSchools: 3,
    totalStudents: 2370,
    totalTeachers: 135,
    trainingCourses: 12,
    certificatesIssued: 245,
    inTraining: 68
  };

  function navigateTo(path: string) {
    goto(path);
  }
</script>

<svelte:head>
  <title>Education & Training - Phường/Xã Super App</title>
</svelte:head>

<div class="page-container">
  <header class="page-header">
    <div class="header-content">
      <button class="back-btn" on:click={() => navigateTo('/government')}>
        ← Back
      </button>
      <div>
        <h1>🎓 Education & Training</h1>
        <p class="subtitle">Giáo dục và đào tạo - Trường học, khóa học</p>
      </div>
    </div>
  </header>

  <div class="content">
    <section class="stats-section">
      <h2>📊 Education Overview</h2>
      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-icon">🏫</div>
          <div class="stat-info">
            <span class="stat-value">{stats.totalSchools}</span>
            <span class="stat-label">Schools</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">👨‍🎓</div>
          <div class="stat-info">
            <span class="stat-value">{stats.totalStudents.toLocaleString()}</span>
            <span class="stat-label">Students</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">👨‍🏫</div>
          <div class="stat-info">
            <span class="stat-value">{stats.totalTeachers}</span>
            <span class="stat-label">Teachers</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📚</div>
          <div class="stat-info">
            <span class="stat-value">{stats.trainingCourses}</span>
            <span class="stat-label">Training Courses</span>
          </div>
        </div>
        <div class="stat-card highlight">
          <div class="stat-icon">🎖️</div>
          <div class="stat-info">
            <span class="stat-value">{stats.certificatesIssued}</span>
            <span class="stat-label">Certificates</span>
          </div>
        </div>
      </div>
    </section>

    <section class="schools-section">
      <div class="section-header">
        <h2>🏫 Schools</h2>
        <button class="btn-primary">+ Add School</button>
      </div>
      <div class="schools-grid">
        {#each schools as school}
          <div class="school-card">
            <div class="school-icon">
              {#if school.type === 'Primary'}🏫{:else if school.type === 'Secondary'}🎒{:else}🎨{/if}
            </div>
            <h3>{school.name}</h3>
            <p class="school-type">{school.type}</p>
            <div class="school-details">
              <p>📍 {school.address}</p>
              <p>👨‍🎓 {school.students} students</p>
              <p>👨‍🏫 {school.teachers} teachers</p>
            </div>
          </div>
        {/each}
      </div>
    </section>

    <section class="courses-section">
      <div class="section-header">
        <h2>📚 Available Courses</h2>
        <button class="btn-primary">+ Create Course</button>
      </div>
      <div class="courses-grid">
        {#each courses as course}
          <div class="course-card">
            <div class="course-header">
              <span class="course-category">{course.category}</span>
              <span class="course-status {course.status === 'Đang mở' ? 'active' : 'pending'}">{course.status}</span>
            </div>
            <h3>{course.name}</h3>
            <div class="course-details">
              <p>⏱️ {course.duration}</p>
              <p>👥 {course.participants} participants</p>
            </div>
            <button class="btn-secondary">Register</button>
          </div>
        {/each}
      </div>
    </section>

    <section class="records-section">
      <div class="section-header">
        <h2>📋 Training Records</h2>
        <button class="btn-secondary">View All</button>
      </div>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Citizen</th>
              <th>Course</th>
              <th>Date</th>
              <th>Status</th>
              <th>Certificate</th>
            </tr>
          </thead>
          <tbody>
            {#each trainingRecords as record}
              <tr>
                <td>{record.citizen}</td>
                <td>{record.course}</td>
                <td>{record.date}</td>
                <td><span class="status-badge {record.status === 'Hoàn thành' ? 'completed' : 'ongoing'}">{record.status}</span></td>
                <td><span class="certificate-badge {record.certificate === 'Có' ? 'yes' : 'no'}">{record.certificate}</span></td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </section>

    <section class="actions-section">
      <h2>⚡ Quick Actions</h2>
      <div class="actions-grid">
        <button class="action-card">
          <div class="action-icon">📝</div>
          <span>Register Course</span>
        </button>
        <button class="action-card">
          <div class="action-icon">🏫</div>
          <span>School Management</span>
        </button>
        <button class="action-card">
          <div class="action-icon">🎖️</div>
          <span>Issue Certificate</span>
        </button>
        <button class="action-card">
          <div class="action-icon">📊</div>
          <span>Training Report</span>
        </button>
      </div>
    </section>
  </div>
</div>

<style>
  .page-container {
    min-height: 100vh;
    background: #f8fafc;
  }

  .page-header {
    background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
    color: white;
    padding: 2rem;
  }

  .header-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .back-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
  }

  h1 {
    margin: 0;
    font-size: 1.75rem;
  }

  .subtitle {
    margin: 0.5rem 0 0;
    opacity: 0.9;
  }

  .content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  h2 {
    margin: 0 0 1rem;
    color: #1e293b;
    font-size: 1.25rem;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
  }

  .stat-card {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .stat-card.primary {
    background: #ecfeff;
    border: 2px solid #06b6d4;
  }

  .stat-card.highlight {
    background: #fef3c7;
    border: 2px solid #f59e0b;
  }

  .stat-icon {
    font-size: 2rem;
  }

  .stat-info {
    display: flex;
    flex-direction: column;
  }

  .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .stat-label {
    font-size: 0.875rem;
    color: #64748b;
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .btn-primary {
    background: #06b6d4;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
  }

  .btn-secondary {
    background: #f1f5f9;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
  }

  .schools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
  }

  .school-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1.5rem;
  }

  .school-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
  }

  .school-card h3 {
    margin: 0.5rem 0;
    color: #1e293b;
  }

  .school-type {
    color: #06b6d4;
    font-weight: 600;
    font-size: 0.875rem;
  }

  .school-details p {
    margin: 0.25rem 0;
    font-size: 0.875rem;
    color: #64748b;
  }

  .courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
  }

  .course-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
  }

  .course-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
  }

  .course-category {
    font-size: 0.75rem;
    color: #64748b;
  }

  .course-status {
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
  }

  .course-status.active {
    background: #dcfce7;
    color: #166534;
  }

  .course-status.pending {
    background: #fef3c7;
    color: #92400e;
  }

  .course-card h3 {
    margin: 0.5rem 0;
    color: #1e293b;
  }

  .course-details p {
    margin: 0.25rem 0;
    font-size: 0.875rem;
    color: #64748b;
  }

  .table-container {
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  th, td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
  }

  th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e293b;
  }

  .status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .status-badge.completed {
    background: #dcfce7;
    color: #166534;
  }

  .status-badge.ongoing {
    background: #dbeafe;
    color: #1d4ed8;
  }

  .certificate-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .certificate-badge.yes {
    background: #dcfce7;
    color: #166534;
  }

  .certificate-badge.no {
    background: #f1f5f9;
    color: #64748b;
  }

  .actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }

  .action-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
  }

  .action-card:hover {
    background: #ecfeff;
    border-color: #06b6d4;
  }

  .action-icon {
    font-size: 2rem;
  }

  @media (max-width: 768px) {
    .content {
      padding: 1rem;
    }
  }
</style>
