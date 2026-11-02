// Test helpers for Zig backend
// This bootstrap file provides common test utilities and database setup

pub const TestConfig = struct {
    db_path: []const u8 = ":memory:",
    env: []const u8 = "test",
};

pub fn getTestConfig() TestConfig {
    return TestConfig{};
}

// Helper function to initialize test database
pub fn initTestDb() !void {
    // Will implement SQLite connection setup here
}

// Provide missing sanitizer coverage symbol when building with -Dcoverage.
// This stub satisfies the linker and allows coverage data to be collected.
pub extern "c" fn __sanitizer_cov_trace_pc_guard_init() void {}
