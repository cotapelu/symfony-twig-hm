from .registry import Registry
from .events import EventDispatcher, Event
from .transaction import TransactionManager, Transaction

class Facade:
    def __init__(self, registry: Registry, event_dispatcher: EventDispatcher, transaction_manager: TransactionManager):
        self.registry = registry
        self.event_dispatcher = event_dispatcher
        self.transaction_manager = transaction_manager

    def get_module(self, name: str):
        return self.registry.get(name)

    def dispatch_event(self, event: Event):
        self.event_dispatcher.dispatch(event)

    def begin_transaction(self) -> Transaction:
        return self.transaction_manager.begin()