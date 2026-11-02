<script lang="ts">
    import { onMount } from 'svelte';
    import { user, showToast, subscribeToRealtime } from '$lib/stores/global';
    import { updateProfile, PHP } from '$lib/api';
    import Button from '$lib/components/atoms/Button.svelte';
    import Input from '$lib/components/atoms/Input.svelte';
    import Typography from '$lib/components/atoms/Typography.svelte';
    import Textarea from '$lib/components/Textarea.svelte';
    import Card from '$lib/components/molecules/Card.svelte';
    import type { User } from '$lib/types';

    interface Profile {
        id: string;
        name: string;
        email: string;
        bio: string;
        avatar: string | null;
    }

    let profile: Profile = {
        id: '',
        name: '',
        email: '',
        bio: '',
        avatar: null
    };
    let loading = true;
    let saving = false;
    let error = '';

    onMount(async () => {
        await loadProfile();

        subscribeToRealtime('profile', (data: unknown) => {
            const payload = data as { type: string; user: Partial<User> };
            if (payload.type === 'profile_updated') {
                profile = { ...profile, ...payload.user };
                showToast('Profile updated!', 'success');
            }
        });
    });

    async function loadProfile() {
        loading = true;
        error = '';
        try {
            const token = localStorage.getItem('auth_token');
            const response = await fetch('/api/user/profile', {
                headers: {
                    'Authorization': token ? `Bearer ${token}` : ''
                }
            });

            if (response.ok) {
                const data = await response.json();
                profile = {
                    id: data.id,
                    name: data.name,
                    email: data.email,
                    bio: data.bio || '',
                    avatar: data.avatar
                };
            } else {
                error = 'Failed to load profile';
            }
        } catch (err) {
            error = (err instanceof Error ? err.message : 'Failed to load profile');
        } finally {
            loading = false;
        }
    }

    async function handleSave(event: Event) {
        event.preventDefault();
        saving = true;
        try {
            await updateProfile({
                name: profile.name,
                email: profile.email,
                bio: profile.bio
            });
            showToast('Profile saved successfully!', 'success');
        } catch (err) {
            showToast('Failed to save profile: ' + (err instanceof Error ? err.message : 'Unknown error'), 'error');
        } finally {
            saving = false;
        }
    }

    async function handleAvatarUpload(event: Event) {
        const target = event.target as HTMLInputElement;
        const file = target.files?.[0];
        if (!file) return;

        try {
            const token = localStorage.getItem('auth_token');
            const formData = new FormData();
            formData.append('file', file);

            const response = await fetch('/api/files/upload', {
                method: 'POST',
                headers: {
                    'Authorization': token ? `Bearer ${token}` : ''
                },
                body: formData
            });

            if (response.ok) {
                const data = await response.json();
                profile.avatar = data.file.url;
                showToast('Avatar uploaded successfully!', 'success');
            } else {
                throw new Error('Upload failed');
            }
        } catch (err) {
            showToast('Failed to upload avatar: ' + (err instanceof Error ? err.message : 'Unknown error'), 'error');
        }
    }
</script>

<main class="profile-page">
    <div class="profile-header">
        <Typography variant="h1">Profile Settings</Typography>
        <Typography variant="body">Manage your account information and preferences</Typography>
    </div>

    {#if loading}
        <div class="loading-spinner">
            <p>Loading profile...</p>
        </div>
    {:else if error}
        <div class="error-message">
            <p>{error}</p>
            <Button onclick={loadProfile}>Retry</Button>
        </div>
    {:else}
        <div class="profile-content">
            <Card>
                <Typography variant="h2">Avatar</Typography>
                <div class="avatar-container">
                    {#if profile.avatar}
                        <img src={profile.avatar} alt="Profile avatar" class="avatar-image" />
                    {:else}
                        <div class="avatar-placeholder">
                            {profile.name ? profile.name.charAt(0).toUpperCase() : '?'}
                        </div>
                    {/if}
                    <input
                        type="file"
                        accept="image/*"
                        onchange={handleAvatarUpload}
                        id="avatar-upload"
                        style="display: none"
                    />
                    <Button variant="ghost" as="label" for="avatar-upload">
                        📷 Change Avatar
                    </Button>
                </div>
            </Card>

            <Card>
                <Typography variant="h2">Personal Information</Typography>
                <form onsubmit={handleSave}>
                    <div class="form-group">
                        <Input
                            label="Display Name"
                            type="text"
                            bind:value={profile.name}
                            placeholder="Your display name"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <Input
                            label="Email Address"
                            type="email"
                            bind:value={profile.email}
                            placeholder="your.email@example.com"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <Textarea
                            label="Bio"
                            bind:value={profile.bio}
                            placeholder="Tell us about yourself..."
                            rows={4}
                        />
                    </div>

                    <div class="form-actions">
                        <Button type="submit" disabled={saving}>
                            {#if saving}Saving...{:else}Save Changes{/if}
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    {/if}
</main>

<style>
    .profile-page {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem;
        background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
        color: #ffffff;
        min-height: 100vh;
    }

    .profile-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .profile-header h1 {
        color: #00ffff;
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        text-shadow: 0 0 15px #00ffff;
    }

    .profile-header p {
        color: #cccccc;
        font-size: 1.1rem;
    }

    .loading-spinner {
        text-align: center;
        padding: 3rem;
    }

    .loading-spinner p {
        color: #00ffff;
    }

    .error-message {
        background: rgba(220, 53, 69, 0.1);
        border: 1px solid #ff6b6b;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
        margin: 2rem 0;
    }

    .error-message p {
        color: #ff6b6b;
        margin-bottom: 1rem;
    }

    .error-message button {
        background: #ff6b6b;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        cursor: pointer;
    }

    .profile-content {
        display: grid;
        gap: 2rem;
    }

    .profile-avatar-section {
        text-align: center;
    }

    .profile-avatar-section h2 {
        color: #ff00ff;
        margin-bottom: 1rem;
        text-shadow: 0 0 10px #ff00ff;
    }

    .avatar-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .avatar-image {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 3px solid #00ffff;
        box-shadow: 0 0 20px rgba(0, 255, 255, 0.5);
    }

    .avatar-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(0, 255, 255, 0.1);
        border: 3px solid #00ffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: bold;
        color: #00ffff;
        text-shadow: 0 0 10px #00ffff;
    }

    .avatar-upload-btn {
        background: rgba(0, 255, 255, 0.1);
        border: 1px solid #00ffff;
        color: #00ffff;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .avatar-upload-btn:hover {
        background: rgba(0, 255, 255, 0.2);
        box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
    }

    .profile-form h2 {
        color: #ff00ff;
        margin-bottom: 1.5rem;
        text-shadow: 0 0 10px #ff00ff;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        color: #00ffff;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid rgba(0, 255, 255, 0.3);
        border-radius: 8px;
        background: rgba(20, 20, 20, 0.8);
        color: #ffffff;
        font-family: 'Courier New', monospace;
        transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #00ffff;
        box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }

    .form-actions {
        text-align: center;
        margin-top: 2rem;
    }

    .form-actions button {
        background: linear-gradient(45deg, #00ffff, #ff00ff);
        border: none;
        color: #000000;
        padding: 0.75rem 2rem;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
    }

    .form-actions button:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 0 25px rgba(0, 255, 255, 0.8);
    }

    .form-actions button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    @media (max-width: 768px) {
        .profile-page {
            padding: 1rem;
        }

        .profile-header h1 {
            font-size: 2rem;
        }

        .profile-content {
            grid-template-columns: 1fr;
        }

        .avatar-image,
        .avatar-placeholder {
            width: 100px;
            height: 100px;
        }
    }
</style>