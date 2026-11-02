const std = @import("std");
const Allocator = std.mem.Allocator;

pub const ModuleRegistry = struct {
    allocator: Allocator,
    modules: std.StringHashMap(ModuleInfo),
    loaded_modules: std.StringHashMap(*u8),
    dependencies: std.StringHashMap([]const []const u8),
    load_order: std.ArrayList([]const u8),

    const ModuleInfo = struct {
        name: []const u8,
        path: []const u8,
    };

    pub fn init(allocator: Allocator) ModuleRegistry {
        return ModuleRegistry{
            .allocator = allocator,
            .modules = std.StringHashMap(ModuleInfo).init(allocator),
            .loaded_modules = std.StringHashMap(*u8).init(allocator),
            .dependencies = std.StringHashMap([]const []const u8).init(allocator),
            .load_order = std.ArrayList([]const u8).initCapacity(allocator, 0) catch unreachable,
        };
    }

    pub fn deinit(self: *ModuleRegistry) void {
        // Free modules: keys (module name strings) and value.path
        var modules_it = self.modules.iterator();
        while (modules_it.next()) |entry| {
            self.allocator.free(entry.key_ptr.*); // key string
            self.allocator.free(entry.value_ptr.path); // allocated path
        }

        // Free loaded_modules: keys (module name strings) and value (allocated instance)
        var loaded_it = self.loaded_modules.iterator();
        while (loaded_it.next()) |entry| {
            self.allocator.free(entry.key_ptr.*); // key string
            self.allocator.destroy(entry.value_ptr.*); // allocated *u8
        }

        // Free dependencies: keys (module name strings)
        var deps_it = self.dependencies.iterator();
        while (deps_it.next()) |entry| {
            self.allocator.free(entry.key_ptr.*);
            // values are static empty slices; no free
        }

        // Free load_order strings
        for (self.load_order.items) |item| {
            self.allocator.free(item);
        }

        // Deinitialize hash maps and ArrayList (now all owned memory freed)
        self.modules.deinit();
        self.loaded_modules.deinit();
        self.dependencies.deinit();
        self.load_order.deinit(self.allocator);
    }

    pub fn discover(self: *ModuleRegistry, name: []const u8, path: []const u8) !void {
        const name_copy = try self.allocator.dupe(u8, name);
        const path_copy = try self.allocator.dupe(u8, path);

        try self.modules.put(name_copy, ModuleInfo{
            .name = name_copy,
            .path = path_copy,
        });
    }

    pub fn load(self: *ModuleRegistry, module_name: []const u8) !void {
        if (self.loaded_modules.contains(module_name)) {
            return;
        }

        if (self.modules.get(module_name) == null) {
            return error.ModuleNotFound;
        }

        // In Zig, module instantiation would depend on the specific module type
        // This is a placeholder - actual implementation would require module-specific logic
        const module_instance = try self.allocator.create(u8);
        module_instance.* = 0;

        try self.loaded_modules.put(try self.allocator.dupe(u8, module_name), module_instance);

        // Dependencies would be retrieved from the module instance
        try self.dependencies.put(try self.allocator.dupe(u8, module_name), &[_][]const u8{});
    }

    pub fn getLoadedModules(self: *const ModuleRegistry) std.StringHashMap(*anyopaque) {
        return self.loaded_modules;
    }

    pub fn getModule(self: *const ModuleRegistry, name: []const u8) ?*u8 {
        return self.loaded_modules.get(name);
    }

    pub fn isLoaded(self: *const ModuleRegistry, name: []const u8) bool {
        return self.loaded_modules.contains(name);
    }

    pub fn getDependencies(self: *const ModuleRegistry, module_name: []const u8) []const []const u8 {
        return self.dependencies.get(module_name) orelse &[_][]const u8{};
    }

    pub fn getLoadOrder(self: *ModuleRegistry) ![]const []const u8 {
        if (self.load_order.items.len == 0) {
            try self.calculateLoadOrder();
        }
        return self.load_order.items;
    }

    fn calculateLoadOrder(self: *ModuleRegistry) !void {
        var visited = std.StringHashMap(void).init(self.allocator);
        defer visited.deinit();
        var visiting = std.StringHashMap(void).init(self.allocator);
        defer visiting.deinit();
        var order = std.ArrayList([]const u8).initCapacity(self.allocator, 0) catch unreachable;
        defer order.deinit(self.allocator);

        var it = self.loaded_modules.keyIterator();
        while (it.next()) |module| {
            try self.visitModule(module.*, &visited, &visiting, &order);
        }

        try self.load_order.appendSlice(self.allocator, order.items);
    }

    fn visitModule(
        self: *ModuleRegistry,
        module: []const u8,
        visited: *std.StringHashMap(void),
        visiting: *std.StringHashMap(void),
        order: *std.ArrayList([]const u8)
    ) !void {
        if (visited.contains(module)) {
            return;
        }

        if (visiting.contains(module)) {
            return error.CircularDependency;
        }

        try visiting.put(module, {});

        const deps = self.getDependencies(module);
        for (deps) |dependency| {
            if (self.loaded_modules.contains(dependency)) {
                try self.visitModule(dependency, visited, visiting, order);
            }
        }

        _ = visiting.remove(module);
        try visited.put(module, {});
        try order.append(self.allocator, try self.allocator.dupe(u8, module));
    }
};

pub const Event = struct {
    allocator: Allocator,
    name: []u8,
    data: std.StringHashMap(*anyopaque),

    pub fn init(allocator: Allocator, name: []const u8) Event {
        return Event{
            .allocator = allocator,
            .name = allocator.dupe(u8, name) catch unreachable,
            .data = std.StringHashMap(*anyopaque).init(allocator),
        };
    }

    pub fn deinit(self: *Event) void {
        self.data.deinit();
        self.allocator.free(self.name);
    }
};

pub const EventListener = struct {
    listener: *const fn (*Event) anyerror!void,
    priority: i32,
};

pub const EventDispatcher = struct {
    allocator: Allocator,
    listeners: std.StringHashMap(std.ArrayList(EventListener)),
    wildcard_listeners: std.StringHashMap(EventListener),
    async_dispatch: bool,
    max_retries: u32,

    pub fn init(allocator: Allocator, async_dispatch: bool, max_retries: u32) EventDispatcher {
        return EventDispatcher{
            .allocator = allocator,
            .listeners = std.StringHashMap(std.ArrayList(EventListener)).init(allocator),
            .wildcard_listeners = std.StringHashMap(EventListener).init(allocator),
            .async_dispatch = async_dispatch,
            .max_retries = max_retries,
        };
    }

    pub fn deinit(self: *EventDispatcher) void {
        var it = self.listeners.valueIterator();
        while (it.next()) |list| {
            list.deinit(self.allocator);
        }
        self.listeners.deinit();

        // Free wildcard listener keys
        var wc_it = self.wildcard_listeners.keyIterator();
        while (wc_it.next()) |key| {
            self.allocator.free(key.*);
        }
        self.wildcard_listeners.deinit();
    }

    pub fn addListener(self: *EventDispatcher, event_name: []const u8, listener: *const fn (*Event) anyerror!void, priority: i32) ![]const u8 {
        if (std.mem.eql(u8, event_name, "*")) {
            const listener_id = try self.allocator.dupe(u8, "0");
            try self.wildcard_listeners.put(listener_id, EventListener{
                .listener = listener,
                .priority = priority,
            });
            return listener_id;
        } else {
            var list = self.listeners.getOrPutValue(event_name, std.ArrayList(EventListener).initCapacity(self.allocator, 0) catch unreachable) catch unreachable;
            try list.value_ptr.append(self.allocator, EventListener{
                .listener = listener,
                .priority = priority,
            });
            self.sortListeners(event_name);
            return "specific";
        }
    }

    pub fn dispatch(self: *EventDispatcher, event: *Event) !void {
        try self.dispatchToListeners(event.name, event);
        var wc_it = self.wildcard_listeners.valueIterator();
        while (wc_it.next()) |listener| {
            try self.invokeListener(listener.*, event);
        }
    }

    fn dispatchToListeners(self: *EventDispatcher, event_name: []const u8, event: *Event) !void {
        const event_listeners = self.listeners.get(event_name) orelse return;

        for (event_listeners.items) |listener| {
            try self.invokeListener(listener, event);
        }
    }

    fn invokeListener(self: *const EventDispatcher, listener: EventListener, event: *Event) !void {
        var retries: u32 = 0;
        while (retries < self.max_retries) {
            listener.listener(event) catch {
                retries += 1;
                if (retries >= self.max_retries) {
                    return error.ListenerFailed;
                }
                continue;
            };
            break;
        }
    }

    fn sortListeners(self: *EventDispatcher, event_name: []const u8) void {
        const list = self.listeners.getPtr(event_name) orelse return;
        const items = list.items;
        var i: usize = 1;
        while (i < items.len) {
            var j = i;
            while (j > 0 and items[j-1].priority < items[j].priority) {
                const temp = items[j-1];
                items[j-1] = items[j];
                items[j] = temp;
                j -= 1;
            }
            i += 1;
        }
    }
};

pub const TransactionManager = struct {
    allocator: Allocator,
    in_transaction: bool,
    nesting_level: u32,
    save_points: std.StringHashMap([]const u8),

    pub fn init(allocator: Allocator) TransactionManager {
        return TransactionManager{
            .allocator = allocator,
            .in_transaction = false,
            .nesting_level = 0,
            .save_points = std.StringHashMap([]const u8).init(allocator),
        };
    }

    pub fn deinit(self: *TransactionManager) void {
        self.save_points.deinit();
    }

    pub fn begin(self: *TransactionManager) !void {
        if (self.nesting_level == 0) {
            // In a real implementation, this would start a database transaction
            self.in_transaction = true;
        }
        self.nesting_level += 1;
    }

    pub fn commit(self: *TransactionManager) !void {
        if (self.nesting_level == 0) {
            return;
        }

        self.nesting_level -= 1;

        if (self.nesting_level == 0) {
            // In a real implementation, this would commit the database transaction
            self.in_transaction = false;
            self.clearSavePoints();
        }
    }

    pub fn rollback(self: *TransactionManager) !void {
        if (self.nesting_level == 0) {
            return;
        }

        self.nesting_level -= 1;

        if (self.nesting_level == 0) {
            // In a real implementation, this would rollback the database transaction
            self.in_transaction = false;
            self.clearSavePoints();
        }
    }

    pub fn execute(self: *TransactionManager, operation: *const fn () anyerror!void) !void {
        try self.executeWithRetry(operation, 3);
    }

    pub fn executeWithRetry(self: *TransactionManager, operation: *const fn () anyerror!void, max_retries: u32) !void {
        var retries: u32 = 0;

        while (retries < max_retries) {
            self.begin() catch |err| {
                retries += 1;
                if (retries >= max_retries) return err;
                std.time.sleep(100 * std.time.ns_per_ms * retries);
                continue;
            };

            operation() catch |err| {
                self.rollback() catch {};
                retries += 1;
                if (retries >= max_retries) return err;
                std.time.sleep(100 * std.time.ns_per_ms * retries);
                continue;
            };

            self.commit() catch |err| {
                retries += 1;
                if (retries >= max_retries) return err;
                std.time.sleep(100 * std.time.ns_per_ms * retries);
                continue;
            };

            return;
        }
    }

    pub fn isInTransaction(self: *const TransactionManager) bool {
        return self.in_transaction;
    }

    pub fn getNestingLevel(self: *const TransactionManager) u32 {
        return self.nesting_level;
    }

    fn clearSavePoints(self: *TransactionManager) void {
        self.save_points.clearRetainingCapacity();
    }
};

pub const ModuleFacade = struct {
    allocator: Allocator,
    module_registry: *ModuleRegistry,

    pub fn init(allocator: Allocator, module_registry: *ModuleRegistry) ModuleFacade {
        return ModuleFacade{
            .allocator = allocator,
            .module_registry = module_registry,
        };
    }

    pub fn get(self: *const ModuleFacade, module_name: []const u8) ?*anyopaque {
        return self.module_registry.getModule(module_name);
    }

    pub fn has(self: *const ModuleFacade, module_name: []const u8) bool {
        return self.module_registry.isLoaded(module_name);
    }

    pub fn getAll(self: *const ModuleFacade) std.StringHashMap(*anyopaque) {
        return self.module_registry.getLoadedModules();
    }
};

pub const Bootstrap = struct {
    allocator: Allocator,
    module_registry: ModuleRegistry,
    event_dispatcher: EventDispatcher,
    transaction_manager: TransactionManager,
    facade: ModuleFacade,

    pub fn init(allocator: Allocator) !Bootstrap {
        return Bootstrap{
            .allocator = allocator,
            .module_registry = ModuleRegistry.init(allocator),
            .event_dispatcher = EventDispatcher.init(allocator, false, 3),
            .transaction_manager = TransactionManager.init(allocator),
            .facade = ModuleFacade.init(allocator, undefined), // Will be set after module_registry is initialized
        };
    }

    pub fn deinit(self: *Bootstrap) void {
        self.module_registry.deinit();
        self.event_dispatcher.deinit();
        self.transaction_manager.deinit();
    }

    pub fn bootstrap(self: *Bootstrap) !void {
        std.log.info("Starting application bootstrap...", .{});

        self.initializeCore();
        try self.initializePlatformServices();
        try self.initializeModules();
        self.initializeEventing();
        self.initializeTransactions();
        self.registerFacades();
        try self.fireBootstrapEvents();

        std.log.info("Application bootstrap completed successfully", .{});
    }

    fn initializeCore(self: *Bootstrap) void {
        self.facade = ModuleFacade.init(self.allocator, &self.module_registry);
        std.log.info("Core services initialized", .{});
    }

    fn initializePlatformServices(_: *Bootstrap) !void {
        // Initialize platform services
        // IAM, MDM, Eventing, Config services would be initialized here
        // and made available to modules

        std.log.info("Platform services initialized (placeholder)", .{});
    }

    fn initializeModules(self: *Bootstrap) !void {
        const module_paths = try self.getModulePaths();
        defer self.allocator.free(module_paths);

        for (module_paths) |entry| {
            self.module_registry.discover(entry.key, entry.value) catch |err| {
                std.log.warn("Failed to discover module {s}: {}", .{entry.key, err});
            };
        }

        const load_order = try self.module_registry.getLoadOrder();
        for (load_order) |module_name| {
            self.module_registry.load(module_name) catch |err| {
                std.log.err("Failed to load module {s}: {}", .{module_name, err});
                return err;
            };
        }

        std.log.info("All modules loaded successfully", .{});
    }

    fn initializeEventing(_: *Bootstrap) void {
        std.log.info("Event system initialized", .{});
    }

    fn initializeTransactions(_: *Bootstrap) void {
        std.log.info("Transaction system initialized", .{});
    }

    fn registerFacades(_: *Bootstrap) void {
        std.log.info("Facades registered", .{});
    }

    fn fireBootstrapEvents(self: *Bootstrap) !void {
        var started_event = Event.init(self.allocator, "app.bootstrap.started");
        defer started_event.deinit();

        var completed_event = Event.init(self.allocator, "app.bootstrap.completed");
        defer completed_event.deinit();

        try self.event_dispatcher.dispatch(&started_event);
        try self.event_dispatcher.dispatch(&completed_event);

        std.log.info("Bootstrap events fired", .{});
    }

    fn getModulePaths(_: *const Bootstrap) ![]const struct{key: []const u8, value: []const u8} {
        // Default module paths
        return &[_]struct{key: []const u8, value: []const u8}{
            .{.key = "users", .value = "src/modules/users"},
            .{.key = "billing", .value = "src/modules/billing"},
            .{.key = "orders", .value = "src/modules/orders"},
            .{.key = "products", .value = "src/modules/products"},
        };
    }

    pub fn shutdown(self: *Bootstrap) !void {
        std.log.info("Starting application shutdown...", .{});

        var shutdown_event = Event.init(self.allocator, "app.shutdown.started");
        defer shutdown_event.deinit();

        try self.event_dispatcher.dispatch(&shutdown_event);

        std.log.info("Application shutdown completed", .{});
    }
};