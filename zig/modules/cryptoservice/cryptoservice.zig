const std = @import("std");
const Allocator = std.mem.Allocator;

pub const CryptoService = struct {
    allocator: Allocator,

    pub fn init(allocator: Allocator) CryptoService {
        return .{ .allocator = allocator };
    }

    pub fn encrypt(self: *CryptoService, data: []const u8, key: []const u8) ![]u8 {
        if (key.len == 0) return error.EmptyKey;
        if (data.len == 0) return error.EmptyData;

        const result = try self.allocator.alloc(u8, data.len);
        errdefer self.allocator.free(result);

        for (data, 0..) |byte, i| {
            result[i] = byte ^ key[i % key.len];
        }

        return result;
    }

    pub fn decrypt(self: *CryptoService, encryptedData: []const u8, key: []const u8) ![]u8 {
        return self.encrypt(encryptedData, key);
    }

    pub fn hash(self: *CryptoService, data: []const u8) ![]u8 {
        if (data.len == 0) return error.EmptyData;

        const result = try self.allocator.alloc(u8, 32);
        errdefer self.allocator.free(result);

        var h: u64 = 5381; // use u64 to avoid overflow panic in safe mode
        for (data) |c| {
            h = (h *% 33) +% c;
        }

        for (0..32) |i| {
            result[i] = @truncate((h >> @intCast(i % 4)) & 0xFF);
        }

        return result;
    }

    pub fn verifyHash(self: *CryptoService, data: []const u8, expectedHash: []const u8) !bool {
        const computedHash = try self.hash(data);
        defer self.allocator.free(computedHash);

        if (computedHash.len != expectedHash.len) return false;

        for (computedHash, 0..) |b, i| {
            if (b != expectedHash[i]) return false;
        }

        return true;
    }

    pub fn generateKeyPair(self: *CryptoService) ![2][]u8 {
        const privateKey = try self.allocator.alloc(u8, 64);
        errdefer self.allocator.free(privateKey);

        std.crypto.random.bytes(privateKey);

        const publicKey = try self.allocator.alloc(u8, 64);
        errdefer self.allocator.free(publicKey);
        @memcpy(publicKey, privateKey); // For mock, public = private

        return [2][]u8{ publicKey, privateKey };
    }

    pub fn sign(self: *CryptoService, data: []const u8, privateKey: []const u8) ![]u8 {
        if (data.len == 0) return error.EmptyData;
        if (privateKey.len == 0) return error.EmptyPrivateKey;

        const signature = try self.allocator.alloc(u8, 64);
        errdefer self.allocator.free(signature);

        var computedHash: u64 = 5381;

        for (data) |c| {
            computedHash = (computedHash *% 33) +% c;
        }

        for (privateKey, 0..) |k, i| {
            computedHash = (computedHash *% 33) +% k +% @as(u8, @truncate(i));
            signature[i % 64] = @truncate(computedHash);
        }

        return signature;
    }

    pub fn verifySignature(self: *CryptoService, data: []const u8, signature: []const u8, publicKey: []const u8) !bool {
        _ = self; // unused
        if (data.len == 0) return error.EmptyData;
        if (publicKey.len == 0) return error.EmptyPublicKey;
        if (signature.len != 64) return error.InvalidSignatureLength;

        var computedHash: u64 = 5381;
        for (data) |c| {
            computedHash = (computedHash *% 33) +% c;
        }

        for (signature, 0..) |s, i| {
            computedHash = (computedHash *% 33) +% publicKey[i % publicKey.len] +% @as(u8, @truncate(i));
            const expected: u8 = @truncate(computedHash);
            if (s != expected) {
                return false;
            }
        }

        return true;
    }

    pub fn freeKeyPair(self: *CryptoService, keys: [2][]u8) void {
        self.allocator.free(keys[0]);
        self.allocator.free(keys[1]);
    }
};
