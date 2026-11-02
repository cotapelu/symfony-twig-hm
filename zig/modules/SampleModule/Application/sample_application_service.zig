// Application layer: contains application services that use domain and contracts.
const SampleEntity = @import("../Domain/sample_entity.zig");
const SampleServiceInterface = @import("../Contracts/sample_service_interface.zig");

pub const SampleApplicationService = struct {
    repository: *SampleServiceInterface,

    pub fn init(repository: *SampleServiceInterface) SampleApplicationService {
        return .{
            .repository = repository,
        };
    }

    pub fn process(self: *SampleApplicationService, input: []const u8) ![]const u8 {
        // Delegate to the infrastructure (repository) which implements the service interface.
        return self.repository.process(self, input);
    }
};
