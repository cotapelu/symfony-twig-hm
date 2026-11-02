const std = @import("std");
const testing = std.testing;

test "sample unit test" {
    const result = 2 + 2;
    try testing.expectEqual(@as(u32, 4), result);
}
