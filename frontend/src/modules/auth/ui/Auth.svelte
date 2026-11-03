<script lang="ts">
  import { createEventDispatcher } from 'svelte';
  import { fade, slide, scale } from 'svelte/transition';
  import { setUser } from '$lib/stores/global';
  import { loginAPI, registerAPI } from '$lib/api';
  import SocialLogin from '$lib/components/SocialLogin.svelte';
  import PhoneRegistration from '$lib/components/PhoneRegistration.svelte';
  import Button from '$lib/components/atoms/Button.svelte';
  import Input from '$lib/components/atoms/Input.svelte';
  import Typography from '$lib/components/atoms/Typography.svelte';
  import Form from '$lib/components/molecules/Form.svelte';

  export let showLogin = true;
  let error = '';
  let loading = false;
  let success = false;
  let showPhoneRegistration = false;

  let loginData = { email: '', password: '' };
  let registerData = { name: '', email: '', password: '', confirmPassword: '' };
  let oauthLoading = false;

  const dispatch = createEventDispatcher();

  async function handleLogin(): Promise<void> {
    if (!loginData.email.trim() || !loginData.email.includes('@')) {
      error = 'Valid email is required.';
      return;
    }
    if (!loginData.password.trim()) {
      error = 'Password is required.';
      return;
    }

    loading = true;
    error = '';
    try {
      const user = await loginAPI({
        email: loginData.email.trim().toLowerCase(),
        password: loginData.password
      });
      setUser(user);
      dispatch('login', { user });
    } catch (err) {
      error = (err as Error).message || 'Login failed. Please check your credentials.';
    } finally {
      loading = false;
    }
  }

  async function handleRegister(): Promise<void> {
    if (!registerData.name.trim()) {
      error = 'Name is required.';
      return;
    }
    if (!registerData.email.trim() || !registerData.email.includes('@')) {
      error = 'Valid email is required.';
      return;
    }
    if (registerData.password.length < 8) {
      error = 'Password must be at least 8 characters.';
      return;
    }
    if (registerData.password !== registerData.confirmPassword) {
      error = 'Passwords do not match.';
      return;
    }

    loading = true;
    error = '';
    try {
      const user = await registerAPI({
        name: registerData.name.trim(),
        email: registerData.email.trim().toLowerCase(),
        password: registerData.password
      });
      setUser(user);
      dispatch('login', { user });
    } catch (err) {
      error = (err as Error).message || 'Registration failed. Please try again.';
    } finally {
      loading = false;
    }
  }

  function toggleMode(): void {
    showLogin = !showLogin;
    error = '';
  }

  function handleOAuthStart(_event: CustomEvent): void {
    oauthLoading = true;
    error = '';
  }

  function handleOAuthSuccess(event: CustomEvent): void {
    const { user, token } = event.detail;
    oauthLoading = false;

     // Set user and token
     setUser(user);
     if (token) {
       localStorage.setItem('auth_token', token);
     }

     // Dispatch success event
     dispatch('login', { user, method: 'oauth' });
   }

   function handleOAuthError(event) {
     oauthLoading = false;
     const { error: oauthError, message } = event.detail;
     error = message || `OAuth error: ${oauthError}`;
   }

   function handleOAuthCancel(event) {
     oauthLoading = false;
   }
</script>

<div class="auth-container">
    <div class="auth-card" in:fade={{ duration: 500 }}>
        <div class="auth-header">
          <Typography variant="h2">{showLogin ? 'Login' : 'Register'}</Typography>
          <Typography variant="body">{showLogin ? 'Welcome back!' : 'Create your account'}</Typography>
        </div>

     {#if error}
       <div class="error-message" in:slide={{ duration: 300 }} out:fade={{ duration: 200 }}>
         <div class="error-icon">⚠️</div>
         <span>{error}</span>
       </div>
     {/if}

     {#if success}
       <div class="success-message" in:scale={{ duration: 400, start: 0.8 }}>
         <div class="success-icon">✅</div>
         <span>Welcome back! Redirecting...</span>
       </div>
     {/if}

     {#if showLogin}
        <Form onSubmit={handleLogin}>
         <div class="form-group">
           <Input
             label="Email"
             type="email"
             bind:value={loginData.email}
             required
             placeholder="your@email.com"
           />
         </div>

         <div class="form-group">
           <Input
             label="Password"
             type="password"
             bind:value={loginData.password}
             required
             placeholder="••••••••"
           />
         </div>

         <Button type="submit" class="auth-button" disabled={loading}>
           {loading ? 'Logging in...' : 'Login'}
         </Button>
       </Form>
     {:else}
        <Form onSubmit={handleRegister}>
         <div class="form-group">
           <Input
             label="Full Name"
             type="text"
             bind:value={registerData.name}
             required
             placeholder="John Doe"
           />
         </div>

         <div class="form-group">
           <Input
             label="Email"
             type="email"
             bind:value={registerData.email}
             required
             placeholder="your@email.com"
           />
         </div>

         <div class="form-group">
           <Input
             label="Password"
             type="password"
             bind:value={registerData.password}
             required
             placeholder="••••••••"
           />
         </div>

         <div class="form-group">
           <Input
             label="Confirm Password"
             type="password"
             bind:value={registerData.confirmPassword}
             required
             placeholder="••••••••"
           />
         </div>

         <Button type="submit" class="auth-button" disabled={loading}>
           {loading ? 'Registering...' : 'Register'}
         </Button>
       </Form>
     {/if}

     <SocialLogin
       disabled={loading || oauthLoading}
       on:oauthStart={handleOAuthStart}
       on:oauthSuccess={handleOAuthSuccess}
       on:oauthError={handleOAuthError}
         on:oauthCancel={handleOAuthCancel}
      />

      <!-- Phone Registration -->
      <PhoneRegistration
        bind:showPhoneRegistration
        onlogin={handleLogin}
        onSwitchToEmail={() => showLogin = true}
      />

      <div class="auth-toggle">
        <p>
          {showLogin ? "Don't have an account?" : "Already have an account?"}
          <Button type="button" onclick={toggleMode} variant="ghost">
            {showLogin ? 'Register' : 'Login'}
          </Button>
        </p>
        {#if showLogin}
          <p>
            Or
            <Button type="button" onclick={() => { showLogin = false; showPhoneRegistration = true; }} variant="ghost">
              Register with Phone
            </Button>
          </p>
        {/if}
     </div>
  </div>
</div>

<style>
	.auth-container {
		display: flex;
		justify-content: center;
		align-items: center;
		min-height: 100vh;
		background: radial-gradient(ellipse at center, #0a0a0a 0%, #000000 100%);
		padding: 1rem;
		position: relative;
	}

	.auth-container::before {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background:
			radial-gradient(circle at 30% 70%, rgba(0, 255, 255, 0.2) 0%, transparent 50%),
			radial-gradient(circle at 70% 30%, rgba(255, 0, 255, 0.2) 0%, transparent 50%);
		animation: authFloat 15s ease-in-out infinite;
		pointer-events: none;
	}

	@keyframes authFloat {
		0%, 100% { transform: translateY(0px) rotate(0deg); }
		50% { transform: translateY(-20px) rotate(2deg); }
	}

	.auth-card {
		background: rgba(10, 10, 10, 0.95);
		border-radius: 15px;
		border: 2px solid #00ffff;
		box-shadow:
			0 0 30px rgba(0, 255, 255, 0.4),
			inset 0 0 30px rgba(0, 255, 255, 0.1);
		padding: 2rem;
		width: 100%;
		max-width: 400px;
		position: relative;
	}

	.auth-card::before {
		content: '';
		position: absolute;
		top: -2px;
		left: -2px;
		right: -2px;
		bottom: -2px;
		background: linear-gradient(45deg, #00ffff, #ff00ff, #00ffff);
		border-radius: inherit;
		z-index: -1;
		animation: authGlow 3s ease-in-out infinite alternate;
	}

	@keyframes authGlow {
		0% { opacity: 0.3; }
		100% { opacity: 0.7; }
	}

  .auth-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .auth-header h2 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 1.8rem;
  }

  .auth-header p {
    margin: 0;
    color: #666;
  }

  .error-message {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid rgba(239, 68, 68, 0.2);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: shake 0.5s ease-in-out;
  }

  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
  }

  .success-message {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid rgba(16, 185, 129, 0.2);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .error-icon, .success-icon {
    font-size: 1.2rem;
    flex-shrink: 0;
  }

  .auth-form {
    display: flex;
    flex-direction: column;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
  }

	input {
		width: 100%;
		padding: 0.75rem;
		border: 1px solid #00ffff;
		border-radius: 8px;
		font-size: 1rem;
		background: rgba(20, 20, 20, 0.8);
		transition: all 0.3s ease;
		color: #00ffff;
		font-family: 'Courier New', monospace;
		box-shadow: 0 0 5px rgba(0, 255, 255, 0.3);
	}

	input::placeholder {
		color: rgba(0, 255, 255, 0.5);
	}

	input:focus {
		box-shadow:
			0 0 15px rgba(0, 255, 255, 0.6),
			0 0 30px rgba(255, 0, 255, 0.4);
		border-color: #ff00ff;
	}

  input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
  }

	.auth-button {
		background: linear-gradient(45deg, #00ffff, #ff00ff);
		color: #000000;
		border: 1px solid #00ffff;
		padding: 0.75rem;
		border-radius: 8px;
		font-size: 1rem;
		font-weight: bold;
		cursor: pointer;
		transition: all 0.3s ease;
		margin-top: 0.5rem;
		font-family: 'Courier New', monospace;
		box-shadow:
			0 0 10px rgba(0, 255, 255, 0.5),
			0 0 20px rgba(255, 0, 255, 0.3);
		position: relative;
		overflow: hidden;
	}

	.auth-button::before {
		content: '';
		position: absolute;
		top: 0;
		left: -100%;
		width: 100%;
		height: 100%;
		background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
		transition: left 0.8s;
	}

	.auth-button:hover::before {
		left: 100%;
	}

	.auth-button:hover {
		transform: translateY(-3px);
		box-shadow:
			0 0 25px rgba(0, 255, 255, 0.8),
			0 0 50px rgba(255, 0, 255, 0.6);
	}

  .auth-button:hover {
    transform: translateY(-1px);
  }

  .auth-button:active {
    transform: translateY(0);
  }

  .auth-toggle {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
  }

  .auth-toggle p {
    margin: 0;
    color: #666;
  }

	.link-button {
		background: none;
		border: 1px solid #ff00ff;
		color: #ff00ff;
		cursor: pointer;
		font-weight: 500;
		text-decoration: none;
		transition: all 0.3s ease;
		padding: 0.5rem 1rem;
		border-radius: 5px;
		font-family: 'Courier New', monospace;
		box-shadow: 0 0 5px rgba(255, 0, 255, 0.3);
	}

	.link-button:hover {
		background: rgba(255, 0, 255, 0.1);
		box-shadow:
			0 0 15px rgba(255, 0, 255, 0.6),
			0 0 30px rgba(0, 255, 255, 0.4);
		color: #00ffff;
		border-color: #00ffff;
	}

  .link-button:hover {
    color: #764ba2;
  }

  /* Mobile responsive */
  @media (max-width: 480px) {
    .auth-container {
      padding: 0.5rem;
    }

    .auth-card {
      padding: 1.5rem;
    }

	.auth-header h2 {
		text-align: center;
		margin-bottom: 2rem;
		color: #00ffff;
		font-family: 'Courier New', monospace;
		text-shadow: 0 0 10px #00ffff;
	}
  }
</style>