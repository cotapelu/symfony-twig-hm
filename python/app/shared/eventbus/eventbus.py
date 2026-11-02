import asyncio
from typing import Callable, Dict, List, Optional
import logging
from pydantic import BaseModel
from ..domain.event import DomainEvent, EventType


logger = logging.getLogger(__name__)


EventHandler = Callable[[DomainEvent], None]


class EventBus:
    def __init__(self):
        self.handlers: Dict[EventType, List[EventHandler]] = {}
        self.lock = asyncio.Lock()
    
    async def subscribe(self, event_type: EventType, handler: EventHandler):
        async with self.lock:
            if event_type not in self.handlers:
                self.handlers[event_type] = []
            self.handlers[event_type].append(handler)
            logger.info(f"Subscribed to event: {event_type}")
    
    async def publish(self, event: DomainEvent) -> None:
        handlers = self.handlers.get(event.type, [])
        
        if not handlers:
            logger.warning(f"No handlers for event: {event.type}")
            return
        
        logger.info(f"Publishing event: {event.type}, id: {event.id}")
        
        tasks = []
        for handler in handlers:
            tasks.append(self._run_handler(handler, event))
        
        await asyncio.gather(*tasks, return_exceptions=True)
    
    def publish_sync(self, event: DomainEvent) -> None:
        asyncio.run(self.publish(event))
    
    async def _run_handler(self, handler: EventHandler, event: DomainEvent):
        try:
            await handler(event)
        except Exception as e:
            logger.error(f"Error in handler for event {event.type}: {e}")
    
    async def publish_async(self, event: DomainEvent) -> None:
        asyncio.create_task(self.publish(event))
