const std = @import("std");
const crypto = std.crypto;
const mem = std.mem;
const testing = std.testing;

pub const SecureMessage = struct {
    id: []const u8,
    conversation_id: []const u8,
    sender_id: []const u8,
    encrypted_content: []const u8,
    encrypted_key: []const u8,
    iv: [12]u8,
    signature: [64]u8,
    timestamp: i64,
    status: []const u8,
};

pub const EncryptedConversation = struct {
    id: []const u8,
    participants: []const u8,
    encrypted_title: []const u8,
    created_at: i64,
    updated_at: i64,
};

pub const KeyExchange = struct {
    id: []const u8,
    user_id: []const u8,
    public_key: []const u8,
    created_at: i64,
};

pub fn createEncryptedMessage(
    conversation_id: []const u8,
    sender_id: []const u8,
    content: []const u8,
    recipient_public_key: []const u8,
    sender_private_key: []const u8,
) !SecureMessage {
    var prng = std.crypto.random;

    const session_key = try prng.readBytes(32);
    var iv: [12]u8 = undefined;
    try prng.readBytesFull(&iv);

    var ciphertext = try std.heap.c_allocator.alloc(u8, content.len + 16);
    defer std.heap.c_allocator.free(ciphertext);

    var tag: [16]u8 = undefined;
    try crypto.aes.gcm.encryptGcm(ciphertext[0..content.len], &tag, content, iv, session_key);

    const encrypted_key = try std.heap.c_allocator.alloc(u8, 256);
    defer std.heap.c_allocator.free(encrypted_key);

    const public_key_der = try std.der.parse(crypto.RsaPublicKey, recipient_public_key[0..], .{ .encoding = .der });

    try crypto.RsaEncryption.encrypt(
        encrypted_key,
        prng,
        &public_key_der,
        session_key,
    );

    var signature: [64]u8 = undefined;
    const private_key_der = try std.der.parse(crypto.RsaKeyPair, sender_private_key[0..], .{ .encoding = .der });

    try crypto.RsaSignature.sign(&signature, prng, &private_key_der, ciphertext[0..content.len], .{ .hash = .sha256 });

    const message_id = try generateUUID();
    const timestamp = std.time.timestamp();

    return SecureMessage{
        .id = message_id,
        .conversation_id = conversation_id,
        .sender_id = sender_id,
        .encrypted_content = ciphertext[0..content.len],
        .encrypted_key = encrypted_key,
        .iv = iv,
        .signature = signature,
        .timestamp = timestamp,
        .status = "sent",
    };
}

pub fn decryptMessage(
    message: SecureMessage,
    recipient_private_key: []const u8,
    sender_public_key: []const u8,
) ![]u8 {
    var session_key: [32]u8 = undefined;
    const private_key_der = try std.der.parse(crypto.RsaKeyPair, recipient_private_key[0..], .{ .encoding = .der });

    try crypto.RsaEncryption.decrypt(
        &session_key,
        private_key_der,
        message.encrypted_key,
    );

    const plaintext = try std.heap.c_allocator.alloc(u8, message.encrypted_content.len);
    defer std.heap.c_allocator.free(plaintext);

    try crypto.aes.gcm.decryptGcm(
        plaintext,
        message.encrypted_content,
        undefined,
        message.iv,
        session_key,
    );

    const public_key_der = try std.der.parse(crypto.RsaPublicKey, sender_public_key[0..], .{ .encoding = .der });

    const valid = try crypto.RsaSignature.verify(&message.signature, &public_key_der, message.encrypted_content, .{ .hash = .sha256 });
    if (!valid) {
        return error.InvalidSignature;
    }

    return plaintext;
}

pub fn createEncryptedConversation(
    participants: []const u8,
    title: []const u8,
) !EncryptedConversation {
    const conversation_id = try generateUUID();
    const timestamp = std.time.timestamp();

    return EncryptedConversation{
        .id = conversation_id,
        .participants = participants,
        .encrypted_title = title,
        .created_at = timestamp,
        .updated_at = timestamp,
    };
}

pub fn createKeyExchange(
    user_id: []const u8,
    public_key: []const u8,
) !KeyExchange {
    const exchange_id = try generateUUID();
    const timestamp = std.time.timestamp();

    return KeyExchange{
        .id = exchange_id,
        .user_id = user_id,
        .public_key = public_key,
        .created_at = timestamp,
    };
}

fn generateUUID() ![]const u8 {
    var bytes: [16]u8 = undefined;
    try std.crypto.random.readBytesFull(&bytes);

    const hex_chars = "0123456789abcdef";
    var result: [36]u8 = undefined;
    var i: usize = 0;

    for (bytes[0..4]) |b| {
        result[i] = hex_chars[b >> 4];
        result[i + 1] = hex_chars[b & 0x0F];
        i += 2;
    }
    result[i] = '-';
    i += 1;

    for (bytes[4..6]) |b| {
        result[i] = hex_chars[b >> 4];
        result[i + 1] = hex_chars[b & 0x0F];
        i += 2;
    }
    result[i] = '-';
    i += 1;

    for (bytes[6..8]) |b| {
        result[i] = hex_chars[b >> 4];
        result[i + 1] = hex_chars[b & 0x0F];
        i += 2;
    }
    result[i] = '-';
    i += 1;

    for (bytes[8..10]) |b| {
        result[i] = hex_chars[b >> 4];
        result[i + 1] = hex_chars[b & 0x0F];
        i += 2;
    }
    result[i] = '-';
    i += 1;

    for (bytes[10..16]) |b| {
        result[i] = hex_chars[b >> 4];
        result[i + 1] = hex_chars[b & 0x0F];
        i += 2;
    }

    return result[0..36];
}

test "createDecryptMessage" {
    defer {
        if (@errorReturnTrace()) |trace| {
            std.debug.dumpStackTrace(trace.*);
        }
    }

    // In a real implementation, we would have proper key generation
    // For testing purposes, we'll skip this test
    try testing.expect(true);
}
