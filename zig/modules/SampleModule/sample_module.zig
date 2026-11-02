// This file brings together the five layers of the Sample module.
const Api = @import("./Api/sample_api.zig");
const Application = @import("./Application/sample_application_service.zig");
const Domain = @import("./Domain/sample_entity.zig");
const Infrastructure = @import("./Infrastructure/sample_repository.zig");
const Contracts = @import("./Contracts/sample_service_interface.zig");

// We can expose the public interfaces of each layer if needed.
// For now, we just want the module to be available for import.

// This module doesn't need to export anything special; the layers are already public.
