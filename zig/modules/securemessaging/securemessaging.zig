const std = @import("std");
const Allocator = std.mem.Allocator;
const crypto = std.crypto;

pub const SecureMessagingError = error{
    InvalidMessage,
    EncryptionFailed,
    DecryptionFailed,
    KeyGenerationFailed,
    SignatureInvalid,
    InvalidInput,
    NotFound,
    Unauthorized,
    OutOfMemory,
};

pub const Message = struct {
    id: []const u8,
    sender: []const u8,
    recipient: []const u8,
    content: []const u8,
    timestamp: i64,
    encrypted: bool,
    signature: ?[32]u8,
};

pub const User = struct {
    id: []const u8,
    public_key: [32]u8,
    verified: bool,
};

pub const SecureMessaging = struct {
    allocator: Allocator,
    initialized: bool = false,
    users: std.StringHashMap(User),
    messages: std.ArrayList(Message),
    my_public_key: [32]u8,
    my_private_key: [64]u8,

    const MAX_USER_ID_LENGTH = 100;
    const MAX_MESSAGE_LENGTH = 10000;
    const MAX_RECIPIENT_LENGTH = 100;

    pub fn init(alloc: Allocator) !SecureMessaging {
        var base_key: [32]u8 = undefined;
        crypto.random.bytes(&base_key);
        var private_key: [64]u8 = undefined;
        @memcpy(private_key[0..32], &base_key);
        @memset(private_key[32..64], 0);
        return SecureMessaging{
            .allocator = alloc,
            .initialized = true,
            .users = std.StringHashMap(User).init(alloc),
            .messages = std.ArrayList(Message).initCapacity(alloc, 0) catch unreachable,
            .my_public_key = base_key,
            .my_private_key = private_key,
        };
    }

    pub fn deinit(self: *@This()) void {
        self.initialized = false;
        self.users.deinit();
        // Free allocated fields in each message
        for (self.messages.items) |*msg| {
            self.allocator.free(msg.id);
            self.allocator.free(msg.sender);
            self.allocator.free(msg.recipient);
            self.allocator.free(msg.content);
        }
        self.messages.deinit(self.allocator);
    }

    fn validateUserId(_: *@This(), user_id: []const u8) SecureMessagingError!void {
        if (user_id.len == 0 or user_id.len > MAX_USER_ID_LENGTH) {
            return SecureMessagingError.InvalidInput;
        }
    }

    fn validateMessageContent(_: *@This(), content: []const u8) SecureMessagingError!void {
        if (content.len == 0 or content.len > MAX_MESSAGE_LENGTH) {
            return SecureMessagingError.InvalidInput;
        }
    }

    pub fn generateKeyPair(_: *SecureMessaging) ![2][32]u8 {
        var public_key: [32]u8 = undefined;
        var private_key: [32]u8 = undefined;
        crypto.random.bytes(&public_key);
        crypto.random.bytes(&private_key);
        return .{ public_key, private_key };
    }

    pub fn encryptMessage(self: *@This(), plaintext: []const u8, recipient_public_key: [32]u8) SecureMessagingError![]u8 {
        if (plaintext.len == 0 or plaintext.len > MAX_MESSAGE_LENGTH) {
            return SecureMessagingError.InvalidInput;
        }

        var key: [32]u8 = undefined;

        for (0..32) |i| {
            key[i] = self.my_private_key[i] ^ recipient_public_key[i];
        }

        var ciphertext = try self.allocator.alloc(u8, plaintext.len);
        @memcpy(ciphertext, plaintext);

        for (0..plaintext.len) |i| {
            ciphertext[i] ^= key[i % 32];
        }

        return ciphertext;
    }

    pub fn decryptMessage(self: *@This(), ciphertext: []const u8, sender_public_key: [32]u8) SecureMessagingError![]u8 {
        if (ciphertext.len == 0 or ciphertext.len > MAX_MESSAGE_LENGTH) {
            return SecureMessagingError.InvalidInput;
        }

        var key: [32]u8 = undefined;

        for (0..32) |i| {
            key[i] = self.my_private_key[i] ^ sender_public_key[i];
        }

        var plaintext = try self.allocator.alloc(u8, ciphertext.len);
        @memcpy(plaintext, ciphertext);

        for (0..ciphertext.len) |i| {
            plaintext[i] ^= key[i % 32];
        }

        return plaintext;
    }

    pub fn signMessage(self: *@This(), message: []const u8) [32]u8 {
        var signature: [32]u8 = undefined;
        var hash = crypto.hash.sha2.Sha256.init(.{});

        hash.update(message);
        hash.update(self.my_private_key[0..32]);

        var digest: [32]u8 = undefined;
        hash.final(&digest);

        signature = digest;
        return signature;
    }

    pub fn verifySignature(_: *@This(), message: []const u8, signature: [32]u8, sender_public_key: [32]u8) bool {
        var expected_signature: [32]u8 = undefined;
        var hash = crypto.hash.sha2.Sha256.init(.{});

        hash.update(message);
        hash.update(sender_public_key[0..]);

        var digest: [32]u8 = undefined;
        hash.final(&digest);

        expected_signature = digest;

        for (0..32) |i| {
            if (signature[i] != expected_signature[i]) {
                return false;
            }
        }

        return true;
    }

    pub fn sendMessage(self: *@This(), sender: []const u8, recipient: []const u8, content: []const u8) SecureMessagingError!void {
        try self.validateUserId(sender);
        try self.validateUserId(recipient);
        try self.validateMessageContent(content);

        if (sender.len > MAX_USER_ID_LENGTH or recipient.len > MAX_RECIPIENT_LENGTH) {
            return SecureMessagingError.InvalidInput;
        }

        const message_id = try std.fmt.allocPrint(self.allocator, "msg_{d}", .{self.messages.items.len + 1});

        const message = Message{
            .id = message_id,
            .sender = try self.allocator.dupe(u8, sender),
            .recipient = try self.allocator.dupe(u8, recipient),
            .content = try self.allocator.dupe(u8, content),
            .timestamp = 0,
            .encrypted = false,
            .signature = self.signMessage(content),
        };

        try self.messages.append(self.allocator, message);
    }

    pub fn sendEncryptedMessage(self: *@This(), sender: []const u8, recipient: []const u8, content: []const u8, recipient_key: [32]u8) SecureMessagingError!void {
        try self.validateUserId(sender);
        try self.validateUserId(recipient);
        try self.validateMessageContent(content);

        const encrypted_content = try self.encryptMessage(content, recipient_key);
        defer self.allocator.free(encrypted_content);

        const message_id = try std.fmt.allocPrint(self.allocator, "msg_{d}", .{self.messages.items.len + 1});

        const message = Message{
            .id = message_id,
            .sender = try self.allocator.dupe(u8, sender),
            .recipient = try self.allocator.dupe(u8, recipient),
            .content = try self.allocator.dupe(u8, encrypted_content),
            .timestamp = 0,
            .encrypted = true,
            .signature = self.signMessage(content),
        };

        try self.messages.append(self.allocator, message);
    }

    pub fn getMessages(self: *@This(), user_id: []const u8) SecureMessagingError![]Message {
        try self.validateUserId(user_id);

        var result = std.ArrayList(Message).init(self.allocator);

        for (self.messages.items) |msg| {
            if (std.mem.eql(u8, msg.recipient, user_id) or std.mem.eql(u8, msg.sender, user_id)) {
                result.append(msg) catch {};
            }
        }

        return result.items;
    }

    pub fn getReceivedMessages(self: *@This(), user_id: []const u8) SecureMessagingError![]Message {
        try self.validateUserId(user_id);

        var result = std.ArrayList(Message).init(self.allocator);

        for (self.messages.items) |msg| {
            if (std.mem.eql(u8, msg.recipient, user_id)) {
                result.append(msg) catch {};
            }
        }

        return result.items;
    }

    pub fn addUser(self: *@This(), id: []const u8, public_key: [32]u8) SecureMessagingError!void {
        try self.validateUserId(id);

        const user = User{
            .id = try self.allocator.dupe(u8, id),
            .public_key = public_key,
            .verified = false,
        };

        try self.users.put(try self.allocator.dupe(u8, id), user);
    }

    pub fn verifyUser(self: *@This(), user_id: []const u8) SecureMessagingError!bool {
        try self.validateUserId(user_id);

        const user = self.users.get(user_id) orelse {
            return SecureMessagingError.NotFound;
        };
        return user.verified;
    }

    pub fn getUser(self: *@This(), user_id: []const u8) SecureMessagingError!?User {
        try self.validateUserId(user_id);
        return self.users.get(user_id);
    }

    pub fn markUserVerified(self: *@This(), user_id: []const u8) SecureMessagingError!void {
        try self.validateUserId(user_id);

        const user = self.users.getPtr(user_id) orelse {
            return SecureMessagingError.NotFound;
        };
        user.verified = true;
    }

    pub fn getMessageCount(self: *@This()) usize {
        return self.messages.items.len;
    }

    pub fn getUserCount(self: *@This()) usize {
        return self.users.count();
    }
};

test "securemessaging initialization" {
    var mod = try SecureMessaging.init(std.testing.allocator);
    try std.testing.expect(mod.initialized);
    mod.deinit();
    try std.testing.expect(!mod.initialized);
}

test "key generation" {
    var mod = try SecureMessaging.init(std.testing.allocator);
    defer mod.deinit();

    const keys = try mod.generateKeyPair();
    try std.testing.expect(keys[0].len == 32);
    try std.testing.expect(keys[1].len == 32);
}

test "message signing" {
    var mod = try SecureMessaging.init(std.testing.allocator);
    defer mod.deinit();

    const message = "Hello, World!";
    const signature = mod.signMessage(message);

    const is_valid = mod.verifySignature(message, signature, mod.my_public_key);
    try std.testing.expect(is_valid);
}

test "send message validation" {
    var mod = try SecureMessaging.init(std.testing.allocator);
    defer mod.deinit();

    const result = mod.sendMessage("sender1", "recipient1", "");
    try std.testing.expectError(SecureMessagingError.InvalidInput, result);
}
