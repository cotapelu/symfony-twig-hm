<script lang="ts">
	import { onMount } from 'svelte';
	import PostCard from './PostCard.svelte';
	import PostCreator from './PostCreator.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import Button from '$lib/components/atoms/Button.svelte';
	import Icon from '$lib/components/atoms/Icon.svelte';
	import Tabs from '$lib/components/Tabs.svelte';
	import Avatar from '$lib/components/Avatar.svelte';
	import Card from '$lib/components/molecules/Card.svelte';

	let showCreator = false;
	let activeFilter = 'all';
	let loading = false;

	const tabs = [
		{ id: 'all', label: 'All' },
		{ id: 'following', label: 'Following' },
		{ id: 'trending', label: 'Trending' }
	];

	const posts = [
		{
			id: '1',
			author: { name: 'Nguyễn Văn A', avatar: '' },
			content: 'Just finished working on the new dashboard design! 🎨 The dark theme with cyan accents really pops. What do you think?',
			image: '',
			likes: 42,
			comments: 12,
			shares: 5,
			liked: false,
			createdAt: '2024-02-10T10:30:00Z'
		},
		{
			id: '2',
			author: { name: 'Trần Thị B', avatar: '' },
			content: 'Amazing progress on the backend refactoring! The new modular architecture is so clean. Kudos to the whole team! 👏',
			likes: 128,
			comments: 34,
			shares: 18,
			liked: true,
			createdAt: '2024-02-10T09:15:00Z'
		},
		{
			id: '3',
			author: { name: 'Lê Văn C', avatar: '' },
			content: 'Working on integrating the new payment gateway. The API documentation is excellent, making this integration smooth.',
			likes: 56,
			comments: 8,
			shares: 2,
			liked: false,
			createdAt: '2024-02-09T16:45:00Z'
		}
	];

	async function handleRefresh() {
		loading = true;
		await new Promise(resolve => setTimeout(resolve, 800));
		loading = false;
	}

	function handleLike(id: string) {
		posts.forEach(p => {
			if (p.id === id) {
				p.liked = !p.liked;
				p.likes += p.liked ? 1 : -1;
			}
		});
	}
</script>

<div class="social-page">
	<div class="social-header">
		<div class="header-left">
			<Typography variant="h2">Social Feed</Typography>
			<Typography variant="body" color="secondary">See what's happening</Typography>
		</div>
		<div class="header-actions">
			<Tabs {tabs} bind:activeTab={activeFilter} variant="pills" />
			<Button variant="primary" onclick={() => showCreator = true}>
				<Icon name="plus" /> New Post
			</Button>
		</div>
	</div>

	<div class="feed-layout">
		<aside class="sidebar-left">
			<Card>
				<div class="user-card">
					<Avatar src="" name="Nguyễn Văn A" size="xl" />
					<Typography variant="h4">Nguyễn Văn A</Typography>
					<Typography variant="body" color="secondary">@nguyenvana</Typography>
				</div>
				<div class="stats-row">
					<div class="stat">
						<Typography variant="h4">128</Typography>
						<Typography variant="caption" color="secondary">Posts</Typography>
					</div>
					<div class="stat">
						<Typography variant="h4">1.2K</Typography>
						<Typography variant="caption" color="secondary">Followers</Typography>
					</div>
					<div class="stat">
						<Typography variant="h4">342</Typography>
						<Typography variant="caption" color="secondary">Following</Typography>
					</div>
				</div>
			</Card>
		</aside>

		<main class="feed-main">
			{#if showCreator}
				<PostCreator onClose={() => showCreator = false} onPostCreated={() => {}} />
			{/if}

			{#if loading}
				<div class="loading-state">
					<div class="spinner"></div>
					<Typography variant="body" color="secondary">Loading posts...</Typography>
				</div>
			{:else}
				<div class="posts-feed">
					{#each posts as post}
						<Card className="post-card">
							<div class="post-header">
								<Avatar src={post.author.avatar} name={post.author.name} size="md" />
								<div class="post-meta">
									<Typography variant="body" fontWeight="medium">{post.author.name}</Typography>
									<Typography variant="caption" color="secondary">
										{new Date(post.createdAt).toLocaleDateString('vi-VN')}
									</Typography>
								</div>
							</div>
							<div class="post-content">
								<Typography variant="body">{post.content}</Typography>
							</div>
							<div class="post-actions">
								<button
									class="action-btn"
									class:liked={post.liked}
									onclick={() => handleLike(post.id)}
								>
									<span>{post.liked ? '❤️' : '🤍'}</span>
									{post.likes}
								</button>
								<button class="action-btn">
									<span>💬</span>
									{post.comments}
								</button>
								<button class="action-btn">
									<span>🔄</span>
									{post.shares}
								</button>
								<button class="action-btn">
									<span>📤</span>
								</button>
							</div>
						</Card>
					{/each}
				</div>
			{/if}
		</main>

		<aside class="sidebar-right">
			<Card>
				<div class="section-header">
					<Typography variant="h4">Trending</Typography>
				</div>
				<div class="trending-list">
					<div class="trending-item">
						<Typography variant="caption" color="secondary">#technology</Typography>
						<Typography variant="body">5.2K posts</Typography>
					</div>
					<div class="trending-item">
						<Typography variant="caption" color="secondary">#programming</Typography>
						<Typography variant="body">3.8K posts</Typography>
					</div>
					<div class="trending-item">
						<Typography variant="caption" color="secondary">#ai</Typography>
						<Typography variant="body">2.1K posts</Typography>
					</div>
				</div>
			</Card>
		</aside>
	</div>
</div>

<style>
	.social-page {
		max-width: 1200px;
		margin: 0 auto;
		padding: 1.5rem;
	}

	.social-header {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
		margin-bottom: 2rem;
		gap: 2rem;
	}

	.header-left {
		display: flex;
		flex-direction: column;
		gap: 0.25rem;
	}

	.header-actions {
		display: flex;
		align-items: center;
		gap: 1rem;
	}

	.feed-layout {
		display: grid;
		grid-template-columns: 280px 1fr 300px;
		gap: 1.5rem;
	}

	.sidebar-left,
	.sidebar-right {
		height: fit-content;
		position: sticky;
		top: 1rem;
	}

	.user-card {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.75rem;
		padding: 1rem;
		text-align: center;
	}

	.stats-row {
		display: flex;
		justify-content: space-around;
		padding-top: 1rem;
		border-top: 1px solid rgba(255, 255, 255, 0.1);
	}

	.stat {
		text-align: center;
	}

	.loading-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 4rem;
		gap: 1rem;
	}

	.spinner {
		width: 40px;
		height: 40px;
		border: 3px solid rgba(255, 255, 255, 0.1);
		border-top-color: #00ffff;
		border-radius: 50%;
		animation: spin 1s linear infinite;
	}

	@keyframes spin {
		to { transform: rotate(360deg); }
	}

	.post-card {
		padding: 1.5rem;
	}

	.post-header {
		display: flex;
		align-items: center;
		gap: 1rem;
		margin-bottom: 1rem;
	}

	.post-meta {
		flex: 1;
	}

	.post-content {
		margin-bottom: 1rem;
		line-height: 1.6;
	}

	.post-actions {
		display: flex;
		gap: 0.5rem;
		padding-top: 1rem;
		border-top: 1px solid rgba(255, 255, 255, 0.1);
	}

	.action-btn {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.5rem 0.75rem;
		background: transparent;
		border: none;
		border-radius: 8px;
		color: #888;
		cursor: pointer;
		transition: all 0.2s ease;
	}

	.action-btn:hover {
		background: rgba(255, 255, 255, 0.05);
		color: #fff;
	}

	.action-btn.liked {
		color: #ff6b6b;
	}

	.section-header {
		margin-bottom: 1rem;
	}

	.trending-list {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}

	.trending-item {
		display: flex;
		justify-content: space-between;
		padding: 0.5rem;
		border-radius: 8px;
		cursor: pointer;
		transition: background 0.2s ease;
	}

	.trending-item:hover {
		background: rgba(255, 255, 255, 0.05);
	}

	@media (max-width: 1024px) {
		.feed-layout {
			grid-template-columns: 1fr;
		}

		.sidebar-left,
		.sidebar-right {
			display: none;
		}
	}

	@media (max-width: 768px) {
		.social-header {
			flex-direction: column;
		}

		.header-actions {
			width: 100%;
			flex-wrap: wrap;
		}
	}
</style>
