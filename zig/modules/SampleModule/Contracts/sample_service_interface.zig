// Contracts layer: defines the interface between Application and Infrastructure.
// For the sample module, we define a simple service interface.
pub const SampleServiceInterface = struct {
    pub fn process(self: *@This(), input: []const u8) ![]const u8 {
        // This is an interface, so we expect the implementation to be provided.
        _ = self; // unused
        _ = input; // unused
        return error.NotImplemented;
    }
};
