package appcore

// Facade provides a unified interface for inter-module communication
type Facade struct {
    registry          *Registry
    eventDispatcher   *EventDispatcher
    transactionManager TransactionManager
}

// NewFacade creates a new facade
func NewFacade(registry *Registry, eventDispatcher *EventDispatcher, transactionManager TransactionManager) *Facade {
    return &Facade{
        registry:           registry,
        eventDispatcher:    eventDispatcher,
        transactionManager: transactionManager,
    }
}

// GetModule retrieves a module
func (f *Facade) GetModule(name string) Module {
    return f.registry.Get(name)
}

// DispatchEvent dispatches an event
func (f *Facade) DispatchEvent(event Event) error {
    return f.eventDispatcher.Dispatch(event)
}

// BeginTransaction starts a transaction
func (f *Facade) BeginTransaction() (Transaction, error) {
    return f.transactionManager.Begin()
}