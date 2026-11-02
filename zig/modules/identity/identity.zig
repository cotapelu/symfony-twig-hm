const std = @import("std");
const Allocator = std.mem.Allocator;

pub const IdentityError = error{
    InvalidInput,
    NotFound,
    AlreadyExists,
    Unauthorized,
    OutOfMemory,
};

pub const Identity = struct {
    allocator: Allocator,
    users: std.StringHashMap(User),
    initialized: bool = false,

    pub const User = struct {
        id: []const u8,
        username: []const u8,
        email: []const u8,
        created_at: i64,
        role: []const u8,
    };

    pub fn init(alloc: Allocator) Identity {
        return Identity{
            .allocator = alloc,
            .users = std.StringHashMap(User).init(alloc),
            .initialized = true,
        };
    }

    pub fn deinit(self: *@This()) void {
        var it = self.users.iterator();
        while (it.next()) |entry| {
            const user = entry.value_ptr.*;
            self.allocator.free(user.id);
            self.allocator.free(user.username);
            self.allocator.free(user.email);
            self.allocator.free(user.role);
            self.allocator.free(entry.key_ptr.*);
        }
        self.users.deinit();
        self.initialized = false;
    }

    fn validateUsername(_: *@This(), username: []const u8) IdentityError!void {
        if (username.len < 3 or username.len > 50) {
            return IdentityError.InvalidInput;
        }
        for (username) |c| {
            if (!std.ascii.isAlphanumeric(c) and c != '_' and c != '-') {
                return IdentityError.InvalidInput;
            }
        }
    }

    fn validateEmail(_: *Identity, email: []const u8) IdentityError!void {
        if (email.len < 5 or email.len > 255) {
            return IdentityError.InvalidInput;
        }
        var has_at = false;
        var has_dot = false;
        for (email) |c| {
            if (c == '@') has_at = true;
            if (c == '.' and has_at) has_dot = true;
        }
        if (!has_at or !has_dot) {
            return IdentityError.InvalidInput;
        }
    }

    pub fn createUser(self: *@This(), id: []const u8, username: []const u8, email: []const u8, role: []const u8) IdentityError!User {
        if (id.len == 0 or id.len > 100) {
            return IdentityError.InvalidInput;
        }

        try self.validateUsername(username);
        try self.validateEmail(email);

        if (self.users.contains(id)) {
            return IdentityError.AlreadyExists;
        }

        const id_copy = try self.allocator.dupe(u8, id);
        const username_copy = try self.allocator.dupe(u8, username);
        const email_copy = try self.allocator.dupe(u8, email);
        const role_copy = try self.allocator.dupe(u8, role);

        const user_val = User{
            .id = id_copy,
            .username = username_copy,
            .email = email_copy,
            .created_at = 0,
            .role = role_copy,
        };

        try self.users.put(id_copy, user_val);
        return user_val;
    }

    pub fn getUser(self: *@This(), id: []const u8) IdentityError!User {
        const user = self.users.get(id) orelse {
            return IdentityError.NotFound;
        };
        return user;
    }

    pub fn deleteUser(self: *@This(), id: []const u8) IdentityError!void {
        const entry = self.users.fetchRemove(id) orelse {
            return IdentityError.NotFound;
        };
        const user = entry.value_ptr.*;
        self.allocator.free(user.id);
        self.allocator.free(user.username);
        self.allocator.free(user.email);
        self.allocator.free(user.role);
        self.allocator.free(entry.key_ptr.*);
    }

    pub fn updateUserRole(self: *@This(), id: []const u8, new_role: []const u8) IdentityError!*const User {
        const user = self.users.getPtr(id) orelse {
            return IdentityError.NotFound;
        };

        if (new_role.len == 0 or new_role.len > 20) {
            return IdentityError.InvalidInput;
        }

        self.allocator.free(user.role);
        user.role = try self.allocator.dupe(u8, new_role);

        return user;
    }

    pub fn verifyUser(self: *@This(), id: []const u8, role: []const u8) IdentityError!bool {
        const user = self.users.get(id) orelse {
            return IdentityError.NotFound;
        };
        return std.mem.eql(u8, user.role, role);
    }

    pub fn listUsers(self: *@This()) []*const User {
        var list = std.ArrayList(*const User).init(self.allocator);
        var it = self.users.valueIterator();
        while (it.next()) |user| {
            list.append(self.allocator, user) catch {};
        }
        return list.items;
    }

    pub fn getUserCount(self: *@This()) usize {
        return self.users.count();
    }
};

test "identity initialization" {
    var mod = Identity.init(std.testing.allocator);
    defer mod.deinit();
    try std.testing.expect(mod.initialized);
}

test "create and get user" {
    var mod = Identity.init(std.testing.allocator);
    defer mod.deinit();

    const user = try mod.createUser("user1", "john_doe", "john@example.com", "user");
    try std.testing.expect(std.mem.eql(u8, user.username, "john_doe"));
    try std.testing.expect(std.mem.eql(u8, user.role, "user"));

    const found = try mod.getUser("user1");
    try std.testing.expect(std.mem.eql(u8, found.email, "john@example.com"));
}

test "invalid username" {
    var mod = Identity.init(std.testing.allocator);
    defer mod.deinit();

    const result = mod.createUser("user1", "ab", "test@test.com", "user");
    try std.testing.expectError(IdentityError.InvalidInput, result);
}

test "invalid email" {
    var mod = Identity.init(std.testing.allocator);
    defer mod.deinit();

    const result = mod.createUser("user1", "username", "invalid-email", "user");
    try std.testing.expectError(IdentityError.InvalidInput, result);
}

test "verify role" {
    var mod = Identity.init(std.testing.allocator);
    defer mod.deinit();

    _ = try mod.createUser("user1", "john", "john@test.com", "admin");

    const is_admin = try mod.verifyUser("user1", "admin");
    try std.testing.expect(is_admin);

    const is_user = try mod.verifyUser("user1", "user");
    try std.testing.expect(!is_user);
}
