    private function loadConfiguration(): void
    {
        $loader = new YamlFileLoader($this->container);
        
        try {
            $loader->load(__DIR__ . '/../../config/services.yaml');
        } catch (\Exception $e) {
            $this->getLogger()->error('Failed to load configuration: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => __DIR__ . '/../../config/services.yaml'
            ]);
            
            // Continue with default configuration - this is not fatal
            // The application can still operate with default settings
        }
    }