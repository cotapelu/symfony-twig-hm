// Domain layer: pure domain models and events.
pub const SampleEntity = struct {
    id: []const u8,
    name: []const u8,
    value: i32,
};

pub const SampleEvent = struct {
    // In a real system, this might contain event data.
    // For the sample, we just define the type.
};
