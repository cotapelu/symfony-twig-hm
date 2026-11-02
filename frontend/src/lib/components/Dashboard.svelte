<script lang="ts">
  import { onMount } from 'svelte';
  import Typography from './atoms/Typography.svelte';
  import Card from './molecules/Card.svelte';
  import Grid from './Grid.svelte';

  interface Stats {
    messages: number;
    groups: number;
    calls: number;
  }

  let stats: Stats = {
    messages: 0,
    groups: 0,
    calls: 0
  };
  let recentActivity: string[] = [];

  onMount(async () => {
    await loadDashboardData();
  });

  async function loadDashboardData() {
    try {
      const messagesRes = await fetch('/messages');
      if (messagesRes.ok) {
        const messagesData = await messagesRes.json();
        stats.messages = typeof messagesData.unread_count === 'number' ? messagesData.unread_count : 0;
      }

      const groupsRes = await fetch('/groups');
      if (groupsRes.ok) {
        const groupsData = await groupsRes.json();
        stats.groups = Array.isArray(groupsData.groups) ? groupsData.groups.length : 0;
      }

      const callsRes = await fetch('/calls');
      if (callsRes.ok) {
        const callsData = await callsRes.json();
        stats.calls = Array.isArray(callsData.active_calls) ? callsData.active_calls.length : 0;
      }

      recentActivity = [
        'New message in Dev Team',
        'File shared in Marketing',
        'Call started in Design Team'
      ];
    } catch (error) {
      console.error('Failed to load dashboard data:', error);
    }
  }
</script>

<div class="dashboard">
	<Typography variant="h1">CommUnity Dashboard</Typography>

	<Grid cols={{ sm: 1, md: 2, lg: 3 }} gap="md">
		<Card>
			<Typography variant="h3">Messages</Typography>
			<Typography variant="body">{stats.messages} unread</Typography>
		</Card>
		<Card>
			<Typography variant="h3">Groups</Typography>
			<Typography variant="body">{stats.groups} active</Typography>
		</Card>
		<Card>
			<Typography variant="h3">Calls</Typography>
			<Typography variant="body">{stats.calls} active</Typography>
		</Card>
	</Grid>

	<Card>
		<Typography variant="h2">Recent Activity</Typography>
		<ul>
			{#each recentActivity as activity}
				<li><Typography variant="body">{activity}</Typography></li>
			{/each}
		</ul>
	</Card>
</div>

<style>
	.dashboard {
		max-width: 1200px;
		margin: 0 auto;
	}

	ul {
		list-style: none;
		padding: 0;
	}

	li {
		padding: 0.5rem 0;
		border-bottom: 1px solid #eee;
	}
</style>