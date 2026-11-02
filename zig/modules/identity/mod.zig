const std = @import("std");

pub const AuthError = error{
    TokenExpired,
    InvalidToken,
    MissingToken,
    InvalidFormat,
    AllocatorRequired,
};

pub const Claims = struct {
    user_id: []const u8,
    
    pub fn deinit(self: Claims, allocator: std.mem.Allocator) void {
        allocator.free(self.user_id);
    }
};

pub const AuthService = struct {
    secret_key: []const u8,
    allocator: std.mem.Allocator,
    
    const Self = @This();
    
    pub fn init(allocator: std.mem.Allocator, secret_key: []const u8) !Self {
        const key_copy = try allocator.alloc(u8, secret_key.len);
        @memcpy(key_copy, secret_key);
        
        return Self{
            .secret_key = key_copy,
            .allocator = allocator,
        };
    }
    
    pub fn initFromEnv(allocator: std.mem.Allocator) !Self {
        const secret_key = std.process.getEnvVarOwned(allocator, "JWT_SECRET_KEY") catch |err| {
            std.log.err("JWT_SECRET_KEY environment variable is required", .{});
            return err;
        };
        
        return Self{
            .secret_key = secret_key,
            .allocator = allocator,
        };
    }
    
    pub fn deinit(self: *Self) void {
        self.allocator.free(self.secret_key);
    }
    
    pub fn generateToken(self: Self, user_id: []const u8) AuthError![]const u8 {
        const now = std.time.timestamp();
        const expires_at = now + (24 * 60 * 60);
        
        const claims_json = std.fmt.allocPrint(self.allocator, "{{\"user_id\":\"{s}\",\"exp\":{d}}}", .{ 
            user_id, 
            expires_at 
        }) catch return error.InvalidFormat;
        defer self.allocator.free(claims_json);
        
        const encoder = std.base64.Base64Encoder.init(std.base64.standard_alphabet_chars, '=');
        const claims_b64_size = encoder.calcSize(claims_json.len);
        const claims_b64 = self.allocator.alloc(u8, claims_b64_size) catch return error.InvalidFormat;
        defer self.allocator.free(claims_b64);
        
        _ = encoder.encode(claims_b64, claims_json);
        
        var hmac = std.crypto.auth.hmac.sha2.HmacSha256.init(self.secret_key);
        hmac.update(claims_b64);
        var signature: [32]u8 = undefined;
        hmac.final(&signature);
        
        const sig_b64_size = encoder.calcSize(signature.len);
        const signature_b64 = self.allocator.alloc(u8, sig_b64_size) catch return error.InvalidFormat;
        defer self.allocator.free(signature_b64);
        
        _ = encoder.encode(signature_b64, &signature);
        
        const token = std.fmt.allocPrint(self.allocator, "{s}.{s}", .{ 
            claims_b64, 
            signature_b64 
        }) catch return error.InvalidFormat;
        
        return token;
    }
    
    pub fn verifyToken(self: Self, token: []const u8) AuthError!Claims {
        if (token.len == 0) {
            return error.MissingToken;
        }
        
        const dot_index = std.mem.indexOf(u8, token, ".");
        if (dot_index == null) {
            return error.InvalidFormat;
        }
        
        const claims_b64 = token[0..dot_index.?];
        const signature_b64 = token[dot_index.? + 1 ..];
        
        if (claims_b64.len == 0 or signature_b64.len == 0) {
            return error.InvalidFormat;
        }
        
        const decoder = std.base64.Base64Decoder.init(std.base64.standard_alphabet_chars, '=');
        
        const sig_decoded_size = decoder.calcSizeForSlice(signature_b64) catch return error.InvalidFormat;
        const signature = self.allocator.alloc(u8, sig_decoded_size) catch return error.InvalidFormat;
        defer self.allocator.free(signature);
        
        decoder.decode(signature, signature_b64) catch return error.InvalidFormat;
        
        var hmac = std.crypto.auth.hmac.sha2.HmacSha256.init(self.secret_key);
        hmac.update(claims_b64);
        var expected_signature: [32]u8 = undefined;
        hmac.final(&expected_signature);
        
        if (!std.mem.eql(u8, signature, &expected_signature)) {
            return error.InvalidToken;
        }
        
        const claims_decoded_size = decoder.calcSizeForSlice(claims_b64) catch return error.InvalidFormat;
        const claims_json = self.allocator.alloc(u8, claims_decoded_size) catch return error.InvalidFormat;
        defer self.allocator.free(claims_json);
        
        decoder.decode(claims_json, claims_b64) catch return error.InvalidFormat;
        
        const user_id = parseUserId(claims_json) catch return error.InvalidFormat;
        const expires_at = parseExpiration(claims_json) catch return error.InvalidFormat;
        
        const now = std.time.timestamp();
        if (expires_at < now) {
            return error.TokenExpired;
        }
        
        const user_id_owned = self.allocator.alloc(u8, user_id.len) catch return error.InvalidFormat;
        @memcpy(user_id_owned, user_id);
        
        return Claims{ .user_id = user_id_owned };
    }
};

fn parseUserId(claims_json: []const u8) ![]const u8 {
    const prefix = "\"user_id\":\"";
    const start = std.mem.indexOf(u8, claims_json, prefix);
    if (start == null) {
        return error.InvalidFormat;
    }
    
    const value_start = start.? + prefix.len;
    const end = std.mem.indexOf(u8, claims_json[value_start..], "\"");
    if (end == null) {
        return error.InvalidFormat;
    }
    
    return claims_json[value_start .. value_start + end.?];
}

fn parseExpiration(claims_json: []const u8) !i64 {
    const prefix = "\"exp\":";
    const start = std.mem.indexOf(u8, claims_json, prefix);
    if (start == null) {
        return error.InvalidFormat;
    }
    
    const value_start = start.? + prefix.len;
    var end: usize = value_start;
    
    while (end < claims_json.len and std.ascii.isDigit(claims_json[end])) {
        end += 1;
    }
    
    if (end == value_start) {
        return error.InvalidFormat;
    }
    
    return std.fmt.parseInt(i64, claims_json[value_start..end], 10) catch return error.InvalidFormat;
}
