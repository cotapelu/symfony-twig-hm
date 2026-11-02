<script lang="ts">
  import { onMount } from 'svelte';
  import { documentWorkflowService, citizenService } from '$lib/services';
  
  let workflow = null;
  let loading = false;
  let error = null;
  let success = null;
  
  // Form data
  let formData = {
    documentId: '',
    fullName: '',
    identityNumber: '',
    province: '',
    district: '',
    ward: '',
    street: '',
    apartmentNumber: '',
    email: '',
    phone: '',
    dateOfBirth: '',
    gender: 'other',
    ethnicity: '',
    nationality: 'Vietnam',
    occupation: '',
    educationLevel: '',
    maritalStatus: '',
    householdId: ''
  };
  
  // Load citizen data if documentId is provided (for editing/viewing)
  async function loadCitizen() {
    if (!formData.documentId) return;
    
    try {
      const citizen = await citizenService.getCitizenById(formData.documentId);
      if (citizen) {
        // Map citizen data to form fields
        formData = {
          ...formData,
          fullName: citizen.fullName,
          identityNumber: citizen.identityNumber,
          email: citizen.email || '',
          phone: citizen.phone || '',
          gender: citizen.gender,
          // Note: address fields are not in the citizen entity, so we leave them blank
          // In a real app, we would have address in the citizen entity or a separate service
        };
      }
    } catch (err) {
      console.error('Failed to load citizen:', err);
    }
  }
  
  async function handleSubmit() {
    error = null;
    success = null;
    loading = true;
    
    try {
      // First, we need to create or update the citizen record
      // For simplicity, we assume the citizen already exists and we are just submitting a document
      // In a real app, we would have a citizen creation/update step first
      
      // Submit the document for review
      const result = await documentWorkflowService.submitForReview(formData.documentId);
      
      if (result) {
        success = 'Document submitted for review successfully';
        // Reset form or navigate to a success page
        formData = {
          ...formData,
          documentId: '' // Clear the document ID to allow submitting another
        };
      } else {
        throw new Error('Failed to submit document');
      }
    } catch (err) {
      error = err instanceof Error ? err.message : 'Unknown error';
      console.error('Failed to submit document:', err);
    } finally {
      loading = false;
    }
  }
  
  onMount(() => {
    // If we have a documentId in the route parameters, load the citizen data
    // For now, we rely on the form data being set externally (e.g., from a citizen list)
    loadCitizen();
  });
</script>

<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-6">Submit Document for Review</h1>
  
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
  
  <form on:submit|preventDefault={handleSubmit} class="bg-white rounded-lg shadow-md p-6">
    <div class="mb-4">
      <label class="block text-sm font-medium mb-1">Document ID (Citizen ID)</label>
      <input 
        bind:value={formData.documentId}
        type="text"
        placeholder="Enter the citizen's ID"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        required
      />
    </div>
    
    <div class="mb-6">
      <h2 class="text-lg font-semibold mb-4">Citizen Information</h2>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Full Name</label>
          <input 
            bind:value={formData.fullName}
            type="text"
            placeholder="Enter full name"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            required
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Identity Number (CCCD/CMND)</label>
          <input 
            bind:value={formData.identityNumber}
            type="text"
            placeholder="Enter identity number"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            required
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Date of Birth</label>
          <input 
            bind:value={formData.dateOfBirth}
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Gender</label>
          <select 
            bind:value={formData.gender}
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Email</label>
          <input 
            bind:value={formData.email}
            type="email"
            placeholder="Enter email address"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Phone Number</label>
          <input 
            bind:value={formData.phone}
            type="tel"
            placeholder="Enter phone number"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Ethnicity</label>
          <input 
            bind:value={formData.ethnicity}
            type="text"
            placeholder="Enter ethnicity"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-1" for="nationality-input">Nationality</label>
            <input 
              id="nationality-input"
              bind:value={formData.nationality}
              type="text"
              placeholder="Enter nationality"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Occupation</label>
          <input 
            bind:value={formData.occupation}
            type="text"
            placeholder="Enter occupation"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Education Level</label>
          <input 
            bind:value={formData.educationLevel}
            type="text"
            placeholder="Enter education level"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Marital Status</label>
          <input 
            bind:value={formData.maritalStatus}
            type="text"
            placeholder="Enter marital status"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Household ID</label>
          <input 
            bind:value={formData.householdId}
            type="text"
            placeholder="Enter household ID"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>
    </div>
    
    <div class="mb-6">
      <h2 class="text-lg font-semibold mb-4">Address Information</h2>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Province</label>
          <input 
            bind:value={formData.province}
            type="text"
            placeholder="Enter province"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            required
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">District</label>
          <input 
            bind:value={formData.district}
            type="text"
            placeholder="Enter district"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            required
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Ward</label>
          <input 
            bind:value={formData.ward}
            type="text"
            placeholder="Enter ward"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            required
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Street</label>
          <input 
            bind:value={formData.street}
            type="text"
            placeholder="Enter street"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            required
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Apartment Number</label>
          <input 
            bind:value={formData.apartmentNumber}
            type="text"
            placeholder="Enter apartment number (optional)"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>
    </div>
    
    <div class="flex justify-end">
      <button 
        type="submit"
        disabled={loading}
        class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md"
      >
        {#if loading}
          Submitting...
        {:else}
          Submit for Review
        {/if}
      </button>
    </div>
  </form>
</div>