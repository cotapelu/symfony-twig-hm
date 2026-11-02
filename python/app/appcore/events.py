from typing import Protocol

class Event(Protocol):
    def name(self) -> str:
        ...

class EventHandler(Protocol):
    def handle(self, event: Event):
        ...

class EventDispatcher:
    def __init__(self):
        self.handlers = {}

    def subscribe(self, event_name: str, handler: EventHandler):
        if event_name not in self.handlers:
            self.handlers[event_name] = []
        self.handlers[event_name].append(handler)

    def dispatch(self, event: Event):
        if event.name() in self.handlers:
            for handler in self.handlers[event.name()]:
                handler.handle(event)