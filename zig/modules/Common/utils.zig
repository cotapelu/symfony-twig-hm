// Common utility functions
const std = @import("std");

/// Add two integers
pub fn add(a: i32, b: i32) i32 {
    return a + b;
}

/// Multiply two integers
pub fn multiply(a: i32, b: i32) i32 {
    return a * b;
}

/// Compute factorial (simple iterative)
pub fn factorial(n: u32) u64 {
    if (n == 0) return 1;
    var result: u64 = 1;
    var i: u32 = 1;
    while (i <= n) : (i += 1) {
        result *= i;
    }
    return result;
}

/// Check if string contains a substring
pub fn contains(haystack: []const u8, needle: []const u8) bool {
    if (needle.len == 0) return true;
    if (needle.len > haystack.len) return false;
    var i: usize = 0;
    while (i <= haystack.len - needle.len) : (i += 1) {
        if (std.mem.eql(u8, haystack[i..i+needle.len], needle)) {
            return true;
        }
    }
    return false;
}

/// Convert a string to uppercase (in-place, ASCII only)
pub fn toUpper(buffer: []u8) void {
    for (buffer) |*c| {
        if (c.* >= 'a' and c.* <= 'z') {
            c.* = @as(u8, @intCast(c.* - 32));
        }
    }
}
