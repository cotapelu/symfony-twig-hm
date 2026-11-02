<script lang="ts">
  import { onMount } from 'svelte';
  import { documentWorkflowService, citizenService } from '$lib/services';

  let workflow = null;
  let loading = false;
  let error = null;
  let success = null;

  // Form data for approval/rejection
  let formData = {
    approvedBy: '',
    comments: '',
    rejectedBy: '',
    reason: ''
  };

  // We expect a documentId to be passed via route parameters or state
  // For simplicity, we'll assume we have a way to get the documentId.
  // In a real app, we would get it from the route or from a store.
  let documentId = ''; // This should be set from the route or parent component

  async function loadWorkflow() {
    if (!documentId) return;

    try {
      // We can fetch the workflow by documentId and status pending or approved/rejected
      // But for simplicity, we'll just fetch the pending ones and find the one with matching documentId
      const pendingWorkflows = await documentWorkflowService.getPendingDocuments();
      workflow = pendingWorkflows.find(w => w.documentId === documentId);

      if (!workflow) {
        // If not in pending, try approved
        const approvedWorkflows = await documentWorkflowService.getDocumentsByStatus('approved');
        workflow = approvedWorkflows.find(w => w.documentId === documentId);
      }

      if (!workflow) {
        // Try rejected
        const rejectedWorkflows = await documentWorkflowService.getDocumentsByStatus('rejected');
        workflow = rejectedWorkflows.find(w => w.documentId === documentId);
      }
    } catch (err) {
      error = err instanceof Error ? err.message : 'Unknown error';
      console.error('Failed to load workflow:', err);
    }
  }

  async function handleApprove() {
    error = null;
    success = null;
    loading = true;

    try {
      if (!formData.approvedBy) {
        throw new Error('Approved by is required');
      }

      const result = await documentWorkflowService.approve(documentId, formData.approvedBy, formData.comments);

      if (result) {
        success = 'Document approved successfully';
        // Reset form or navigate to a success page
        formData = {
          ...formData,
          approvedBy: '',
          comments: ''
        };
        // Reload the workflow to reflect the new status
        await loadWorkflow();
      } else {
        throw new Error('Failed to approve document');
      }
    } catch (err) {
      error = err instanceof Error ? err.message : 'Unknown error';
      console.error('Failed to approve document:', err);
    } finally {
      loading = false;
    }
  }

  async function handleReject() {
    error = null;
    success = null;
    loading = true;

    try {
      if (!formData.reason) {
        throw new Error('Rejection reason is required');
      }

      const result = await documentWorkflowService.reject(documentId, formData.rejectedBy || null, formData.reason);

      if (result) {
        success = 'Document rejected successfully';
        // Reset form or navigate to a success page
        formData = {
          ...formData,
          rejectedBy: '',
          reason: ''
        };
        // Reload the workflow to reflect the new status
        await loadWorkflow();
      } else {
        throw new Error('Failed to reject document');
      }
    } catch (err) {
      error = err instanceof Error ? err.message : 'Unknown error';
      console.error('Failed to reject document:', err);
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    // In a real app, we would get the documentId from the route parameters
    // For now, we'll set it to a placeholder or expect it to be set by parent
    // We'll try to get it from the URL if we are in a route like /government/document-approve/:id
    // But since we are creating a standalone page, we'll assume it's set via some other means.
    // For demonstration, we'll leave it empty and show an error if not set.
    loadWorkflow();
  });
</script>

<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-6">Document Approval</h1>

  {#if error}
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
      {error}
    </div>
  {/if}

  {#if success}
    <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-4" role="status">
      {success}
    </div>
  {/if}

  {#if workflow}
    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 class="text-lg font-semibold mb-4">Document Details</h2>
      <p><strong>Document ID:</strong> {workflow.documentId}</p>
      <p><strong>Current Status:</strong>
        {#if workflow.status === 'pending'}
          <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Pending</span>
        {:else if workflow.status === 'approved'}
          <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Approved</span>
        {:else if workflow.status === 'rejected'}
          <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Rejected</span>
        {:else if workflow.status === 'published'}
          <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Published</span>
        {:else if workflow.status === 'archived'}
          <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">Archived</span>
        {/if}
      </p>
      {#if workflow.processedBy}
        <p><strong>Processed By:</strong> {workflow.processedBy}</p>
      {/if}
      {#if workflow.comments}
        <p><strong>Comments:</strong> {workflow.comments}</p>
      {/if}
      <p><strong>Processed At:</strong> {new Date(workflow.processedAt).toLocaleString()}</p>
      <p><strong>Created At:</strong> {new Date(workflow.createdAt).toLocaleString()}</p>
      <p><strong>Updated At:</strong> {new Date(workflow.updatedAt).toLocaleString()}</p>
    </div>

    <div class="mt-6">
      <h2 class="text-lg font-semibold mb-4">Take Action</h2>

      {#if workflow.status === 'pending'}
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Approved By</label>
            <input
              bind:value={formData.approvedBy}
              type="text"
              placeholder="Enter approver's name"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Comments (optional)</label>
            <textarea
              bind:value={formData.comments}
              rows="3"
              placeholder="Enter comments for approval"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <div class="flex space-x-4">
            <button
              on:click={handleApprove}
              disabled={loading}
              class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-md"
            >
              {#if loading}
                Approving...
              {:else}
                Approve
              {/if}
            </button>

            <button
              on:click={() => {
                // Switch to rejection form
                // We'll just show the rejection form by setting a flag, but for simplicity we have separate buttons
                // In a real app, we might have a toggle or separate sections.
                // We'll just focus on the approval button for now.
              }}
              class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md"
            >
              Reject Instead
            </button>
          </div>
        </div>
      {:else if workflow.status === 'approved' || workflow.status === 'rejected'}
        <p class="text-gray-500">This document has already been {workflow.status === 'approved' ? 'approved' : 'rejected'}. No further action can be taken.</p>
      {/if}
    </div>
  {:else}
    {#if documentId}
      <div class="text-center py-8">
        <p class="text-gray-500">Loading document workflow...</p>
        <div class="animate-spin rounded-full border-4 border-b-2 border-blue-500 w-12 h-12 mx-auto"></div>
      </div>
    {:else}
      <div class="text-center py-8">
        <p class="text-yellow-500">No document ID provided. Please navigate to this page with a valid document ID.</p>
      </div>
    {/if}
  {/if}
</div>