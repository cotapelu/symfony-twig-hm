const std = @import("std");
const crypto = std.crypto;
const mem = std.mem;
const testing = std.testing;

const cryptoservice = @import("cryptoservice.zig");

pub const ChatError = error{
    KeyExchangeFailed,
    EncryptionFailed,
    DecryptionFailed,
    VerificationFailed,
    SessionNotFound,
};

pub const ForwardSecrecyMessage = struct {
    id: []u8,
    conversation_id: []u8,
    sender_id: []u8,
    epoch: u64,
    ciphertext: []u8,
    iv: [12]u8,
    tag: [16]u8,
    signature: [64]u8,
    timestamp: i64,
    status: []u8,
};

pub const SessionKey = struct {
    conversation_id: []u8,
    user_id: []u8,
    key: [32]u8,
    epoch: u64,
    created_at: i64,
    expires_at: i64,
};

pub const KeyExchange = struct {
    id: []u8,
    conversation_id: []u8,
    sender_id: []u8,
    recipient_id: []u8,
    ephemeral_public_key: [32]u8,
    encrypted_shared_secret: []u8,
    timestamp: i64,
};

pub const EphemeralKeyPair = struct {
    private_key: [32]u8,
    public_key: [32]u8,
};

pub fn generateEphemeralKeyPair() !EphemeralKeyPair {
    var private_key: [32]u8 = undefined;
    var public_key: [32]u8 = undefined;

    try crypto.x25519.X25519.keyPairFromSeed(&private_key, &public_key);

    return EphemeralKeyPair{
        .private_key = private_key,
        .public_key = public_key,
    };
}

pub fn performKeyExchange(
    initiator_private_key: [32]u8,
    initiator_public_key: [32]u8,
    recipient_public_key: [32]u8,
) ![32]u8 {
    var shared_secret: [32]u8 = undefined;
    try crypto.x25519.X25519.dh(&shared_secret, initiator_private_key, recipient_public_key);
    return shared_secret;
}

pub fn createForwardSecrecyMessage(
    conversation_id: []u8,
    sender_id: []u8,
    content: []u8,
    session_key: [32]u8,
    epoch: u64,
) !ForwardSecrecyMessage {
    var iv: [12]u8 = undefined;
    try std.crypto.random.readBytesFull(&iv);

    var ciphertext = try std.heap.c_allocator.alloc(u8, content.len + 16);
    errdefer std.heap.c_allocator.free(ciphertext);

    var tag: [16]u8 = undefined;
    try crypto.aes.gcm.encryptGcm(ciphertext[0..content.len], &tag, content, iv, session_key);

    var signature: [64]u8 = undefined;
    const signature_input = try std.heap.c_allocator.alloc(u8, ciphertext.len + 8);
    defer std.heap.c_allocator.free(signature_input);
    mem.copy(u8, signature_input[0..8], std.mem.asBytes(&epoch));
    mem.copy(u8, signature_input[8..], ciphertext);

    const message_id = try generateUUID();
    const timestamp = std.time.timestamp();

    return ForwardSecrecyMessage{
        .id = message_id,
        .conversation_id = try std.heap.c_allocator.dupe(u8, conversation_id),
        .sender_id = try std.heap.c_allocator.dupe(u8, sender_id),
        .epoch = epoch,
        .ciphertext = ciphertext,
        .iv = iv,
        .tag = tag,
        .signature = signature,
        .timestamp = timestamp,
        .status = "sent",
    };
}

pub fn decryptForwardSecrecyMessage(
    message: ForwardSecrecyMessage,
    session_key: [32]u8,
) ![]u8 {
    if (message.ciphertext.len < 16) {
        return ChatError.DecryptionFailed;
    }

    var plaintext = try std.heap.c_allocator.alloc(u8, message.ciphertext.len - 16);
    errdefer std.heap.c_allocator.free(plaintext);

    try crypto.aes.gcm.decryptGcm(
        plaintext,
        message.ciphertext[0..plaintext.len],
        message.tag,
        message.iv,
        session_key,
    );

    return plaintext;
}

pub fn createKeyExchange(
    conversation_id: []u8,
    sender_id: []u8,
    recipient_id: []u8,
    recipient_public_key: [32]u8,
    initiator_private_key: [32]u8,
    initiator_public_key: [32]u8,
) !KeyExchange {
    const shared_secret = try performKeyExchange(initiator_private_key, initiator_public_key, recipient_public_key);

    const encrypted_shared_secret = cryptoservice.encryptAESGCM(&shared_secret, recipient_public_key) catch {
        return ChatError.KeyExchangeFailed;
    };

    const exchange_id = try generateUUID();
    const timestamp = std.time.timestamp();

    return KeyExchange{
        .id = exchange_id,
        .conversation_id = try std.heap.c_allocator.dupe(u8, conversation_id),
        .sender_id = try std.heap.c_allocator.dupe(u8, sender_id),
        .recipient_id = try std.heap.c_allocator.dupe(u8, recipient_id),
        .ephemeral_public_key = initiator_public_key,
        .encrypted_shared_secret = encrypted_shared_secret.ciphertext,
        .timestamp = timestamp,
    };
}

pub fn completeKeyExchange(
    exchange: KeyExchange,
    recipient_private_key: [32]u8,
    recipient_public_key: [32]u8,
) ![32]u8 {
    const shared_secret = try performKeyExchange(recipient_private_key, recipient_public_key, exchange.ephemeral_public_key);

    var empty_slice: []u8 = &[_]u8{};
    const ed = cryptoservice.EncryptedData{
        .ciphertext = exchange.encrypted_shared_secret,
        .iv = exchange.encrypted_shared_secret[exchange.encrypted_shared_secret.len - 28 .. exchange.encrypted_shared_secret.len - 16].*,
        .tag = exchange.encrypted_shared_secret[exchange.encrypted_shared_secret.len - 16 ..].*,
        .salt = empty_slice,
    };

    const decrypted = cryptoservice.decryptAESGCM(ed, shared_secret) catch {
        return ChatError.KeyExchangeFailed;
    };

    var result: [32]u8 = undefined;
    if (decrypted.len >= 32) {
        mem.copy(u8, &result, decrypted[0..32]);
    }

    return result;
}

pub fn createSessionKey(
    conversation_id: []u8,
    user_id: []u8,
    shared_secret: [32]u8,
    epoch: u64,
) SessionKey {
    const session_key = cryptoservice.hashSHA256(&shared_secret);

    const now = std.time.timestamp();
    const expires_at = now + 86400;

    return SessionKey{
        .conversation_id = try std.heap.c_allocator.dupe(u8, conversation_id),
        .user_id = try std.heap.c_allocator.dupe(u8, user_id),
        .key = session_key,
        .epoch = epoch,
        .created_at = now,
        .expires_at = expires_at,
    };
}

pub fn rotateSessionKey(session_key: *SessionKey, new_shared_secret: [32]u8) !void {
    const new_key = cryptoservice.hashSHA256(&new_shared_secret);
    session_key.key = new_key;
    session_key.epoch += 1;
    session_key.created_at = std.time.timestamp();
    session_key.expires_at = session_key.created_at + 86400;
}

pub fn verifyMessageSignature(message: ForwardSecrecyMessage, sender_public_key: [256]u8) !bool {
    const signature_input = try std.heap.c_allocator.alloc(u8, message.ciphertext.len + 8);
    defer std.heap.c_allocator.free(signature_input);
    mem.copy(u8, signature_input[0..8], std.mem.asBytes(&message.epoch));
    mem.copy(u8, signature_input[8..], message.ciphertext);

    return cryptoservice.verify(signature_input, message.signature, sender_public_key) catch {
        return ChatError.VerificationFailed;
    };
}

fn generateUUID() ![]u8 {
    var bytes: [16]u8 = undefined;
    try std.crypto.random.readBytesFull(&bytes);

    const timestamp = std.time.timestamp();
    var buffer: [64]u8 = undefined;
    const len = try std.fmt.bufPrint(&buffer, "{d}-{s}", .{ timestamp, bytes });
    return try std.heap.c_allocator.dupe(u8, buffer[0..len]);
}

test "forwardSecrecy" {
    const alice_keypair = try generateEphemeralKeyPair();
    const bob_keypair = try generateEphemeralKeyPair();

    const alice_shared = try performKeyExchange(alice_keypair.private_key, alice_keypair.public_key, bob_keypair.public_key);
    const bob_shared = try performKeyExchange(bob_keypair.private_key, bob_keypair.public_key, alice_keypair.public_key);

    try testing.expect(mem.eql(u8, &alice_shared, &bob_shared));

    const session_key = cryptoservice.hashSHA256(&alice_shared);

    const content = "Hello, this is a secret message!";
    const message = try createForwardSecrecyMessage("conv_123", "alice", content, session_key, 1);

    const decrypted = try decryptForwardSecrecyMessage(message, session_key);
    defer std.heap.c_allocator.free(decrypted);

    try testing.expect(mem.eql(u8, content, decrypted));
}
