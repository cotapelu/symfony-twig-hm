const std = @import("std");

pub const ProtocolError = error{
    InvalidPacket,
    ParsingFailed,
    SerializationFailed,
    ConnectionFailed,
    Timeout,
};

pub const PacketType = enum(u8) {
    syn = 1,
    syn_ack = 2,
    ack = 3,
    fin = 4,
    data = 5,
    heartbeat = 6,
    heartbeat_ack = 7,
    protocol_error = 8,
};

pub const Packet = struct {
    packet_type: PacketType,
    sequence_number: u32,
    acknowledgment_number: u32,
    payload: []const u8,
    timestamp: i64,
    checksum: u16,

    pub fn create(packet_type: PacketType, sequence_number: u32, acknowledgment_number: u32, payload: []const u8) Packet {
        const timestamp = std.time.timestamp();

        var checksum: u16 = 0;
        // Calculate checksum from packet data for integrity verification
        var hash = std.crypto.hash.sha256.init(.{});
        hash.update(&std.mem.toBytes(sequence_number));
        hash.update(&std.mem.toBytes(acknowledgment_number));
        hash.update(&std.mem.toBytes(@as(i64, timestamp)));
        hash.update(payload);
        var digest: [32]u8 = undefined;
        hash.final(&digest);
        checksum = @as(u16, @intCast(digest[0])) | (@as(u16, @intCast(digest[1])) << 8);

        return Packet{
            .packet_type = packet_type,
            .sequence_number = sequence_number,
            .acknowledgment_number = acknowledgment_number,
            .payload = payload,
            .timestamp = timestamp,
            .checksum = checksum,
        };
    }

    pub fn serialize(self: *Packet, allocator: std.mem.Allocator) ![]const u8 {
        var buffer = std.ArrayList(u8).init(allocator);
        defer buffer.deinit();

        try buffer.append(@intFromEnum(self.packet_type));
        try buffer.appendSlice(&std.mem.toBytes(self.sequence_number));
        try buffer.appendSlice(&std.mem.toBytes(self.acknowledgment_number));
        try buffer.appendSlice(&std.mem.toBytes(@as(i64, self.timestamp)));
        try buffer.appendSlice(&std.mem.toBytes(@as(u16, self.payload.len)));
        try buffer.appendSlice(self.payload);

        const result = try allocator.alloc(u8, buffer.items.len);
        @memcpy(result, buffer.items);
        return result;
    }

    pub fn deserialize(allocator: std.mem.Allocator, data: []const u8) !Packet {
        if (data.len < 16) {
            return error.InvalidPacket;
        }

        var offset: usize = 0;
        const packet_type = @as(PacketType, @enumFromInt(data[offset]));
        offset += 1;

        const sequence_number = std.mem.bytesToValue(u32, data[offset .. offset + 4]);
        offset += 4;

        const acknowledgment_number = std.mem.bytesToValue(u32, data[offset .. offset + 4]);
        offset += 4;

        const timestamp = std.mem.bytesToValue(i64, data[offset .. offset + 8]);
        offset += 8;

        const payload_len = std.mem.bytesToValue(u16, data[offset .. offset + 2]);
        offset += 2;

        if (offset + payload_len != data.len) {
            return error.InvalidPacket;
        }

        const payload = try allocator.alloc(u8, payload_len);
        @memcpy(payload, data[offset..]);

        return Packet{
            .packet_type = packet_type,
            .sequence_number = sequence_number,
            .acknowledgment_number = acknowledgment_number,
            .payload = payload,
            .timestamp = timestamp,
            .checksum = 0,
        };
    }
};

pub const Connection = struct {
    allocator: std.mem.Allocator,
    socket: std.net.Stream,
    local_sequence: u32,
    remote_sequence: u32,
    timeout_ms: u32,

    pub fn connect(allocator: std.mem.Allocator, address: std.net.Address, timeout_ms: u32) !Connection {
        const socket = try std.net.tcpConnectToAddress(address);

        return Connection{
            .allocator = allocator,
            .socket = socket,
            .local_sequence = 0,
            .remote_sequence = 0,
            .timeout_ms = timeout_ms,
        };
    }

    pub fn send(self: *Connection, packet: *Packet) !void {
        _ = self;
        _ = packet;
    }

    pub fn receive(self: *Connection) !Packet {
        _ = self;
        return Packet.create(.data, 0, 0, &[_]u8{});
    }

    pub fn close(self: *Connection) void {
        self.socket.close();
    }
};

pub const MessageFrame = struct {
    allocator: std.mem.Allocator,
    max_frame_size: usize,
    current_frame: usize,

    pub fn init(allocator: std.mem.Allocator, max_frame_size: usize) MessageFrame {
        return MessageFrame{
            .allocator = allocator,
            .max_frame_size = max_frame_size,
            .current_frame = 0,
        };
    }

    pub fn encode(self: *MessageFrame, message: []const u8) ![]const []const u8 {
        const frame_count = (message.len + self.max_frame_size - 1) / self.max_frame_size;
        const frames = try self.allocator.alloc([]const u8, frame_count);

        for (frames, 0..) |*frame, i| {
            const start = i * self.max_frame_size;
            const end = @min(start + self.max_frame_size, message.len);
            frame.* = message[start..end];
        }

        return frames;
    }

    pub fn decode(self: *MessageFrame, frames: []const []const u8) ![]const u8 {
        var total_size: usize = 0;
        for (frames) |frame| {
            total_size += frame.len;
        }

        const result = try self.allocator.alloc(u8, total_size);
        var offset: usize = 0;

        for (frames) |frame| {
            @memcpy(result[offset .. offset + frame.len], frame);
            offset += frame.len;
        }

        return result;
    }
};

pub const Protocol = struct {
    pub fn handshake(_: std.mem.Allocator, connection: *Connection) !void {
        const syn = Packet.create(.syn, 0, 0, &[_]u8{});
        try connection.send(&syn);
    }

    pub fn handleHandshake(allocator: std.mem.Allocator, connection: *Connection) !void {
        _ = allocator;
        _ = connection;
    }

    pub fn sendMessage(connection: *Connection, message: []const u8) !void {
        const packet = Packet.create(.data, 0, 0, message);
        try connection.send(&packet);
    }

    pub fn receiveMessage(connection: *Connection, allocator: std.mem.Allocator) ![]const u8 {
        const packet = try connection.receive();
        return allocator.alloc(u8, packet.payload.len);
    }

    pub fn heartbeat(connection: *Connection) !void {
        const heartbeat_packet = Packet.create(.heartbeat, 0, 0, &[_]u8{});
        try connection.send(&heartbeat_packet);
    }
};

test "PacketType enum values" {
    try std.testing.expect(@intFromEnum(PacketType.syn) == 1);
    try std.testing.expect(@intFromEnum(PacketType.syn_ack) == 2);
    try std.testing.expect(@intFromEnum(PacketType.ack) == 3);
    try std.testing.expect(@intFromEnum(PacketType.fin) == 4);
    try std.testing.expect(@intFromEnum(PacketType.data) == 5);
    try std.testing.expect(@intFromEnum(PacketType.heartbeat) == 6);
    try std.testing.expect(@intFromEnum(PacketType.heartbeat_ack) == 7);
    try std.testing.expect(@intFromEnum(PacketType.protocol_error) == 8);
}

test "MessageFrame init" {
    const gpa = std.testing.allocator;
    var frame = MessageFrame.init(gpa, 10);

    try std.testing.expect(frame.max_frame_size == 10);
    try std.testing.expect(frame.current_frame == 0);
}
