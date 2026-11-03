<script lang="ts">
	import { onMount } from 'svelte';
	import Card from '$lib/components/molecules/Card.svelte';
	import Typography from '$lib/components/atoms/Typography.svelte';
	import Button from '$lib/components/atoms/Button.svelte';
	import Icon from '$lib/components/atoms/Icon.svelte';
	import Badge from '$lib/components/atoms/Badge.svelte';
	import Progress from '$lib/components/atoms/Progress.svelte';
	import Tabs from '$lib/components/Tabs.svelte';
	import Empty from '$lib/components/Empty.svelte';
	import { showToast } from '$lib/stores/global';

	interface FileItem {
		id: string;
		name: string;
		size: number;
		type: string;
		url: string;
		uploadedBy: string;
		uploadedAt: string;
		shared: boolean;
		folder?: string;
	}

	interface UploadProgress {
		id: string;
		name: string;
		progress: number;
		status: 'uploading' | 'success' | 'error';
	}

	let files: FileItem[] = [
		{
			id: '1',
			name: 'Project_Proposal.pdf',
			size: 2456789,
			type: 'application/pdf',
			url: '#',
			uploadedBy: 'Nguyễn Văn A',
			uploadedAt: '2024-02-08',
			shared: true,
			folder: 'Documents'
		},
		{
			id: '2',
			name: 'Design_Mockups.fig',
			size: 8765432,
			type: 'application/octet-stream',
			url: '#',
			uploadedBy: 'Nguyễn Văn A',
			uploadedAt: '2024-02-07',
			shared: false,
			folder: 'Design'
		},
		{
			id: '3',
			name: 'Meeting_Recording.mp4',
			size: 156789012,
			type: 'video/mp4',
			url: '#',
			uploadedBy: 'Trần Thị B',
			uploadedAt: '2024-02-06',
			shared: true,
			folder: 'Videos'
		},
		{
			id: '4',
			name: 'Source_Code.zip',
			size: 45678901,
			type: 'application/zip',
			url: '#',
			uploadedBy: 'Nguyễn Văn A',
			uploadedAt: '2024-02-05',
			shared: false,
			folder: 'Code'
		},
		{
			id: '5',
			name: 'Photo_Banner.png',
			size: 1234567,
			type: 'image/png',
			url: '#',
			uploadedBy: 'Lê Văn C',
			uploadedAt: '2024-02-04',
			shared: true,
			folder: 'Images'
		},
		{
			id: '6',
			name: 'Budget_2024.xlsx',
			size: 345678,
			type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			url: '#',
			uploadedBy: 'Nguyễn Văn A',
			uploadedAt: '2024-02-03',
			shared: false,
			folder: 'Finance'
		}
	];

	let folders = ['Documents', 'Design', 'Videos', 'Code', 'Images', 'Finance'];
	let selectedFolder = 'all';
	let viewMode: 'grid' | 'list' = 'grid';
	// Tabs bind:activeTab target (tab ids: all/shared/recent/starred)
	let activeTab = 'all';
	let selectedFiles: string[] = [];
	let uploading: UploadProgress[] = [];
	let dragOver = false;

	const tabs = [
		{ id: 'all', label: 'All Files', icon: '📁' },
		{ id: 'shared', label: 'Shared', icon: '👥' },
		{ id: 'recent', label: 'Recent', icon: '🕐' },
		{ id: 'starred', label: 'Starred', icon: '⭐' }
	];

	function formatFileSize(bytes: number): string {
		if (bytes === 0) return '0 Bytes';
		const k = 1024;
		const sizes = ['Bytes', 'KB', 'MB', 'GB'];
		const i = Math.floor(Math.log(bytes) / Math.log(k));
		return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
	}

	function getFileIcon(type: string): string {
		if (type.startsWith('image/')) return '🖼️';
		if (type.startsWith('video/')) return '🎬';
		if (type.startsWith('audio/')) return '🎵';
		if (type.includes('pdf')) return '📕';
		if (type.includes('word') || type.includes('document')) return '📘';
		if (type.includes('excel') || type.includes('spreadsheet')) return '📗';
		if (type.includes('zip') || type.includes('archive')) return '📦';
		if (type.includes('code') || type.includes('javascript') || type.includes('python')) return '💻';
		return '📄';
	}

	function handleDragOver(e: DragEvent) {
		e.preventDefault();
		dragOver = true;
	}

	function handleDragLeave() {
		dragOver = false;
	}

	async function handleDrop(e: DragEvent) {
		e.preventDefault();
		dragOver = false;
		const droppedFiles = e.dataTransfer?.files;
		if (droppedFiles) {
			for (let i = 0; i < droppedFiles.length; i++) {
				await uploadFile(droppedFiles[i]);
			}
		}
	}

	async function handleFileSelect(e: Event) {
		const input = e.target as HTMLInputElement;
		if (input.files) {
			for (let i = 0; i < input.files.length; i++) {
				await uploadFile(input.files[i]);
			}
		}
	}

	async function uploadFile(file: File) {
		const uploadId = Math.random().toString(36).substr(2, 9);
		uploading = [...uploading, { id: uploadId, name: file.name, progress: 0, status: 'uploading' }];

		try {
			for (let i = 0; i <= 100; i += 10) {
				await new Promise(resolve => setTimeout(resolve, 100));
				uploading = uploading.map(u => u.id === uploadId ? { ...u, progress: i } : u);
			}

			const newFile: FileItem = {
				id: Math.random().toString(36).substr(2, 9),
				name: file.name,
				size: file.size,
				type: file.type,
				url: '#',
				uploadedBy: 'Nguyễn Văn A',
				uploadedAt: new Date().toISOString().split('T')[0],
				shared: false,
				folder: selectedFolder !== 'all' ? selectedFolder : 'Uncategorized'
			};

			files = [newFile, ...files];
			uploading = uploading.map(u => u.id === uploadId ? { ...u, status: 'success' } : u);
			showToast(`${file.name} uploaded successfully`, 'success');
		} catch (err) {
			uploading = uploading.map(u => u.id === uploadId ? { ...u, status: 'error' } : u);
			showToast(`Failed to upload ${file.name}`, 'error');
		}

		setTimeout(() => {
			uploading = uploading.filter(u => u.id !== uploadId);
		}, 3000);
	}

	function handleDownload(file: FileItem) {
		showToast(`Downloading ${file.name}...`, 'info');
	}

	function handleDelete(file: FileItem) {
		if (confirm(`Delete "${file.name}"?`)) {
			files = files.filter(f => f.id !== file.id);
			showToast(`${file.name} deleted`, 'success');
		}
	}

	function toggleFileSelection(id: string) {
		if (selectedFiles.includes(id)) {
			selectedFiles = selectedFiles.filter(f => f !== id);
		} else {
			selectedFiles = [...selectedFiles, id];
		}
	}

	function deleteSelected() {
		if (selectedFiles.length === 0) return;
		if (confirm(`Delete ${selectedFiles.length} files?`)) {
			files = files.filter(f => !selectedFiles.includes(f.id));
			selectedFiles = [];
			showToast('Files deleted', 'success');
		}
	}

	function getFilteredFiles(): FileItem[] {
		return files.filter(f => {
			if (selectedFolder !== 'all' && f.folder !== selectedFolder) return false;
			return true;
		});
	}

	$: filteredFiles = getFilteredFiles();
	$: foldersWithCount = folders.map(f => ({
		name: f,
		count: files.filter(file => file.folder === f).length
	}));
</script>

<div class="files-page">
	<div class="files-header">
		<div class="header-left">
			<Typography variant="h2">Files</Typography>
			<Typography variant="body" color="secondary">
				{filteredFiles.length} files • {formatFileSize(files.reduce((acc, f) => acc + f.size, 0))} used
			</Typography>
		</div>
		<div class="header-actions">
			<Button variant="outline" onclick={() => viewMode = viewMode === 'grid' ? 'list' : 'grid'}>
				<Icon name={viewMode === 'grid' ? 'list' : 'grid'} />
			</Button>
			<Button variant="primary">
				<Icon name="plus" /> New Folder
			</Button>
		</div>
	</div>

	<div class="files-layout">
		<aside class="sidebar">
			<Card>
				<div class="folder-list">
					<button
						class="folder-item"
						class:active={selectedFolder === 'all'}
						onclick={() => selectedFolder = 'all'}
					>
						<Icon name="folder" />
						<span>All Files</span>
						<Badge variant="secondary">{files.length}</Badge>
					</button>
					{#each foldersWithCount as folder}
						<button
							class="folder-item"
							class:active={selectedFolder === folder.name}
							onclick={() => selectedFolder = folder.name}
						>
							<Icon name="folder" />
							<span>{folder.name}</span>
							<Badge variant="secondary">{folder.count}</Badge>
						</button>
					{/each}
				</div>
			</Card>
		</aside>

		<main class="main-content">
			<Card>
				<Tabs {tabs} bind:activeTab variant="pills" />

				<div class="toolbar">
					<div class="toolbar-left">
						{#if selectedFiles.length > 0}
							<Button variant="ghost" onclick={deleteSelected}>
								<Icon name="trash" /> Delete ({selectedFiles.length})
							</Button>
						{/if}
					</div>
					<div class="toolbar-right">
						<Button variant="ghost" onclick={() => selectedFolder = 'all'}>
							<Icon name="refresh" />
						</Button>
					</div>
				</div>

				<div
					class="drop-zone"
					class:drag-over={dragOver}
					ondragover={handleDragOver}
					ondragleave={handleDragLeave}
					ondrop={handleDrop}
				>
					{#if dragOver}
						<div class="drop-content">
							<Icon name="upload" size="xl" />
							<Typography variant="h4">Drop files here</Typography>
						</div>
					{:else}
						<div class="upload-area">
							<Icon name="cloud-upload" size="xl" />
							<Typography variant="h4">Drag & drop files here</Typography>
							<Typography variant="body" color="secondary">or</Typography>
							<label class="upload-btn">
								<Icon name="folder-plus" />
								Browse Files
								<input type="file" multiple onchange={handleFileSelect} />
							</label>
						</div>
					{/if}
				</div>

				{#if uploading.length > 0}
					<div class="upload-progress">
						{#each uploading as upload}
							<div class="upload-item">
								<Icon name={upload.status === 'success' ? 'check' : 'upload'} size="sm" />
								<span class="upload-name">{upload.name}</span>
								<Progress percent={upload.progress} color="#00ffff" />
							</div>
						{/each}
					</div>
				{/if}

				{#if filteredFiles.length === 0}
					<Empty
						icon="folder"
						title="No files yet"
						description="Upload your first file to get started"
					/>
				{:else if viewMode === 'grid'}
					<div class="files-grid">
						{#each filteredFiles as file}
							<button
								class="file-card"
								class:selected={selectedFiles.includes(file.id)}
								onclick={() => toggleFileSelection(file.id)}
							>
								<div class="file-icon">
									{getFileIcon(file.type)}
								</div>
								<div class="file-info">
									<Typography variant="body" fontWeight="medium" truncate>{file.name}</Typography>
									<Typography variant="caption" color="secondary">
										{formatFileSize(file.size)} • {file.uploadedAt}
									</Typography>
								</div>
								{#if file.shared}
									<Badge variant="info" size="sm">Shared</Badge>
								{/if}
								<div class="file-actions">
									<button onclick={(e) => { e.stopPropagation(); handleDownload(file); }}>
										<Icon name="download" size="sm" />
									</button>
									<button onclick={(e) => { e.stopPropagation(); handleDelete(file); }}>
										<Icon name="trash" size="sm" />
									</button>
								</div>
							</button>
						{/each}
					</div>
				{:else}
					<div class="files-list">
						{#each filteredFiles as file}
							<div
								class="file-row"
								class:selected={selectedFiles.includes(file.id)}
								onclick={() => toggleFileSelection(file.id)}
							>
								<div class="file-icon-sm">{getFileIcon(file.type)}</div>
								<div class="file-details">
									<Typography variant="body" fontWeight="medium">{file.name}</Typography>
									<Typography variant="caption" color="secondary">
										{file.uploadedBy} • {file.uploadedAt}
									</Typography>
								</div>
								<Typography variant="body" color="secondary">{formatFileSize(file.size)}</Typography>
								{#if file.shared}
									<Badge variant="info" size="sm">Shared</Badge>
								{/if}
								<div class="row-actions">
									<Button variant="ghost" size="sm" onclick={(e) => { e.stopPropagation(); handleDownload(file); }}>
										<Icon name="download" />
									</Button>
									<Button variant="ghost" size="sm" onclick={(e) => { e.stopPropagation(); handleDelete(file); }}>
										<Icon name="trash" />
									</Button>
								</div>
							</div>
						{/each}
					</div>
				{/if}
			</Card>
		</main>
	</div>
</div>

<style>
	.files-page {
		display: flex;
		flex-direction: column;
		gap: 1.5rem;
	}

	.files-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.header-left {
		display: flex;
		flex-direction: column;
		gap: 0.25rem;
	}

	.header-actions {
		display: flex;
		gap: 0.75rem;
	}

	.files-layout {
		display: grid;
		grid-template-columns: 250px 1fr;
		gap: 1.5rem;
	}

	.sidebar {
		height: fit-content;
		position: sticky;
		top: 1rem;
	}

	.folder-list {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
	}

	.folder-item {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		padding: 0.75rem 1rem;
		background: transparent;
		border: none;
		border-radius: 8px;
		color: #fff;
		cursor: pointer;
		transition: all 0.2s ease;
		text-align: left;
		width: 100%;
	}

	.folder-item:hover {
		background: rgba(255, 255, 255, 0.05);
	}

	.folder-item.active {
		background: rgba(0, 255, 255, 0.1);
		color: #00ffff;
	}

	.main-content {
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.toolbar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1rem 0;
	}

	.drop-zone {
		border: 2px dashed rgba(255, 255, 255, 0.2);
		border-radius: 12px;
		padding: 2rem;
		text-align: center;
		transition: all 0.2s ease;
	}

	.drop-zone.drag-over {
		border-color: #00ffff;
		background: rgba(0, 255, 255, 0.05);
	}

	.upload-area {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.75rem;
	}

	.drop-content {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.75rem;
		color: #00ffff;
	}

	.upload-btn {
		display: inline-flex;
		align-items: center;
		gap: 0.5rem;
		padding: 0.75rem 1.5rem;
		background: rgba(0, 255, 255, 0.1);
		border: 1px solid #00ffff;
		border-radius: 8px;
		color: #00ffff;
		cursor: pointer;
		transition: all 0.2s ease;
	}

	.upload-btn:hover {
		background: rgba(0, 255, 255, 0.2);
	}

	.upload-btn input {
		display: none;
	}

	.upload-progress {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
		padding: 1rem;
	}

	.upload-item {
		display: flex;
		align-items: center;
		gap: 0.75rem;
	}

	.upload-name {
		flex: 1;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.files-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
		gap: 1rem;
		padding: 1rem 0;
	}

	.file-card {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.75rem;
		padding: 1.25rem;
		background: rgba(255, 255, 255, 0.02);
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 12px;
		cursor: pointer;
		transition: all 0.2s ease;
		text-align: center;
	}

	.file-card:hover {
		background: rgba(255, 255, 255, 0.05);
		border-color: #00ffff;
	}

	.file-card.selected {
		background: rgba(0, 255, 255, 0.1);
		border-color: #00ffff;
	}

	.file-icon {
		font-size: 3rem;
	}

	.file-info {
		width: 100%;
		text-align: center;
	}

	.file-actions {
		display: flex;
		gap: 0.5rem;
		opacity: 0;
		transition: opacity 0.2s ease;
	}

	.file-card:hover .file-actions {
		opacity: 1;
	}

	.file-actions button {
		padding: 0.5rem;
		background: rgba(255, 255, 255, 0.1);
		border: none;
		border-radius: 6px;
		color: #fff;
		cursor: pointer;
	}

	.file-actions button:hover {
		background: rgba(0, 255, 255, 0.2);
	}

	.files-list {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
	}

	.file-row {
		display: flex;
		align-items: center;
		gap: 1rem;
		padding: 0.75rem 1rem;
		background: rgba(255, 255, 255, 0.02);
		border: 1px solid transparent;
		border-radius: 8px;
		cursor: pointer;
		transition: all 0.2s ease;
	}

	.file-row:hover {
		background: rgba(255, 255, 255, 0.05);
		border-color: rgba(255, 255, 255, 0.1);
	}

	.file-row.selected {
		background: rgba(0, 255, 255, 0.1);
		border-color: #00ffff;
	}

	.file-icon-sm {
		font-size: 1.5rem;
	}

	.file-details {
		flex: 1;
		min-width: 0;
	}

	.row-actions {
		display: flex;
		gap: 0.5rem;
		opacity: 0;
		transition: opacity 0.2s ease;
	}

	.file-row:hover .row-actions {
		opacity: 1;
	}

	@media (max-width: 768px) {
		.files-layout {
			grid-template-columns: 1fr;
		}

		.sidebar {
			position: static;
		}

		.folder-list {
			flex-direction: row;
			flex-wrap: wrap;
		}

		.folder-item {
			flex: 1;
			min-width: 120px;
			justify-content: center;
		}
	}
</style>