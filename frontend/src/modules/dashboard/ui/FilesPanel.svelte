<script lang="ts">
	import { onMount } from 'svelte';
	import Button from '$lib/components/atoms/Button.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import { fetchFiles, deleteFile, uploadFile } from '$lib/api';
	import { showToast } from '$lib/stores/global';

	interface FileData {
		id: string;
		name: string;
		size: number;
		uploadedBy?: string;
	}

	export let files: FileData[] = [];

	let loading = true;
	let error: string | null = null;
	let fileInput: HTMLInputElement | null = null;

	onMount(async () => {
		await loadFiles();
	});

	async function loadFiles() {
		try {
			loading = true;
			const data = await fetchFiles() as any;
			files = Array.isArray(data) ? data : (data.files || []);
		} catch (err) {
			error = (err as Error)?.message || 'Unknown error';
			showToast('Failed to load files', 'error');
		} finally {
			loading = false;
		}
	}

	async function handleFileUpload(event: Event) {
		const target = event.target as HTMLInputElement;
		const file = target.files?.[0];
		if (!file) return;

		try {
			await uploadFile(file);
			showToast('File uploaded successfully!', 'success');
			await loadFiles();
		} catch (err) {
			showToast('Failed to upload file: ' + (err as Error).message, 'error');
		} finally {
			if (target) target.value = '';
		}
	}

	async function handleDeleteFile(fileId: string) {
		if (!confirm('Are you sure you want to delete this file?')) return;

		try {
			await deleteFile(fileId);
			showToast('File deleted successfully!', 'success');
			await loadFiles();
		} catch (err) {
			showToast('Failed to delete file: ' + (err as Error).message, 'error');
		}
	}
</script>

<Typography variant="h2">Shared Files</Typography>
{#if loading}
	<p>Loading files...</p>
{:else}
	<div class="files-list">
		{#each files as file}
			<div class="file-item">
				<h3>{file.name}</h3>
				<p>{file.size} bytes • {file.uploadedBy}</p>
				<div class="file-actions">
					<Button onclick={() => window.open(`/files/${file.id}`, '_blank')} variant="ghost">
						📥 Download
					</Button>
					<Button onclick={() => handleDeleteFile(file.id)} variant="danger">
						🗑️ Delete
					</Button>
				</div>
			</div>
		{/each}
		{#if files.length === 0}
			<div class="no-files">
				<p>No shared files yet</p>
				<input
					type="file"
					id="file-upload"
					style="display: none"
					bind:this={fileInput}
					onchange={handleFileUpload}
				/>
				<Button variant="ghost" onclick={() => fileInput?.click()}>
					📤 Upload File
				</Button>
			</div>
		{/if}
	</div>
{/if}

<style>
	.files-list {
		display: grid;
		gap: 1rem;
	}

	.file-item {
		background: rgba(20, 20, 20, 0.8);
		border: 1px solid rgba(255, 0, 255, 0.3);
		border-radius: 10px;
		padding: 1rem;
		transition: all 0.3s ease;
	}

	.file-item h3 {
		color: #ff00ff;
		margin: 0 0 0.5rem 0;
		font-size: 1.1rem;
		text-shadow: 0 0 5px #ff00ff;
	}

	.file-item p {
		color: #ffffff;
		margin: 0 0 0.5rem 0;
		font-size: 0.9rem;
	}

	.file-actions {
		display: flex;
		gap: 0.5rem;
	}

	.no-files {
		text-align: center;
		padding: 2rem;
		color: #cccccc;
	}
</style>
