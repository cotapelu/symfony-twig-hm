from typing import Protocol

class Module(Protocol):
    def name(self) -> str:
        ...

    def init(self):
        ...

class Registry:
    def __init__(self):
        self.modules = {}

    def register(self, module: Module):
        self.modules[module.name()] = module

    def get(self, name: str) -> Module:
        return self.modules.get(name)

    def init_all(self):
        for module in self.modules.values():
            module.init()