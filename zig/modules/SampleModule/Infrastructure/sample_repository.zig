// Infrastructure layer: implementations of domain interfaces (e.g., repositories).
const std = @import("std");
const sample_entity_mod = @import("../Domain/sample_entity.zig");
const SampleEntity = sample_entity_mod.SampleEntity;
const sample_service_interface_mod = @import("../Contracts/sample_service_interface.zig");
const SampleServiceInterface = sample_service_interface_mod.SampleServiceInterface;

pub const SampleRepository = struct {
    // In-memory storage for sample items.
    items: std.ArrayList(SampleEntity),
    allocator: std.mem.Allocator,

    pub fn init(allocator: std.mem.Allocator) SampleRepository {
        return .{
            .items = std.ArrayList(SampleEntity).initCapacity(allocator, 0) catch unreachable,
            .allocator = allocator,
        };
    }

    pub fn deinit(self: *SampleRepository) void {
        self.items.deinit(self.allocator);
    }

    pub fn addItem(self: *SampleRepository, item: SampleEntity) !void {
        try self.items.append(self.allocator, item);
    }

    pub fn getItems(self: *SampleRepository) []const SampleEntity {
        return self.items.items;
    }
};
