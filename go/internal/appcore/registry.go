package appcore

// Module interface for all modules
type Module interface {
    Name() string
    Init() error
}

// Registry manages module registration
type Registry struct {
    modules map[string]Module
}

// NewRegistry creates a new registry
func NewRegistry() *Registry {
    return &Registry{
        modules: make(map[string]Module),
    }
}

// Register adds a module to the registry
func (r *Registry) Register(module Module) {
    r.modules[module.Name()] = module
}

// Get retrieves a module by name
func (r *Registry) Get(name string) Module {
    return r.modules[name]
}

// InitAll initializes all registered modules
func (r *Registry) InitAll() error {
    for _, module := range r.modules {
        if err := module.Init(); err != nil {
            return err
        }
    }
    return nil
}