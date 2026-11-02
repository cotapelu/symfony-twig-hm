const std = @import("std");
const testing = std.testing;
const test_helpers = @import("test_helpers.zig");

test "getTestConfig returns default values" {
    const config = test_helpers.getTestConfig();
    try testing.expectEqualStrings(":memory:", config.db_path);
    try testing.expectEqualStrings("test", config.env);
}
