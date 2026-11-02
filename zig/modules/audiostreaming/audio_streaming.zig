const std = @import("std");
const fmt = std.fmt;
const mem = std.mem;
const time = std.time;
const json = std.json;

const AudioStream = struct {
    id: []const u8,
    user_id: []const u8,
    format: []const u8,
    bitrate: i32,
    status: []const u8,
    started_at: i64,
    listener_count: i32,
};

const Playlist = struct {
    id: []const u8,
    user_id: []const u8,
    name: []const u8,
    tracks: [][]const u8,
    created_at: i64,
};

const AudioStreaming = struct {
    allocator: std.mem.Allocator,
    streams: std.StringHashMap(AudioStream),
    playlists: std.StringHashMap(Playlist),
    listeners: std.StringHashMap([]const u8),
    next_stream_id: i32,
    next_playlist_id: i32,
    ets_file: []const u8,

    pub fn init(allocator: std.mem.Allocator, ets_file: []const u8) !@This() {
        var self = @This(){
            .allocator = allocator,
            .streams = std.StringHashMap(AudioStream).init(allocator),
            .playlists = std.StringHashMap(Playlist).init(allocator),
            .listeners = std.StringHashMap([]const u8).init(allocator),
            .next_stream_id = 1,
            .next_playlist_id = 1,
            .ets_file = ets_file,
        };

        try self.ensureDataDirectory(ets_file);
        try self.restoreFromEts();

        std.log.info("AudioStreaming initialized", .{});
        return self;
    }

    pub fn deinit(self: *@This()) void {
        self.persistToEts() catch {};

        var stream_iter = self.streams.iterator();
        while (stream_iter.next()) |entry| {
            self.allocator.free(entry.key_ptr.*);
        }
        var playlist_iter = self.playlists.iterator();
        while (playlist_iter.next()) |entry| {
            self.allocator.free(entry.key_ptr.*);
        }
        var listener_iter = self.listeners.iterator();
        while (listener_iter.next()) |entry| {
            self.allocator.free(entry.key_ptr.*);
            self.allocator.free(entry.value_ptr.*);
        }
        self.streams.deinit();
        self.playlists.deinit();
        self.listeners.deinit();
    }

    fn ensureDataDirectory(_self: *@This(), file_path: []const u8) !void {
        const dir = std.fs.path.dirname(file_path) orelse ".";
        std.fs.cwd().makePath(dir) catch {};
    }

    fn restoreFromEts(self: *@This()) !void {
        const file = std.fs.cwd().openFile(self.ets_file, .{}) catch |err| {
            if (err == error.FileNotFound) {
                std.log.info("No existing ETS file found, starting fresh", .{});
                return;
            }
            return err;
        };
        defer file.close();

        const file_size = try file.getEndPos();
        const buffer = try self.allocator.alloc(u8, file_size);
        defer self.allocator.free(buffer);

        _ = try file.readAll(buffer);

        const parsed = try json.parseFromSlice(json.Value, self.allocator, buffer, .{});
        defer parsed.deinit();

        const root = parsed.value.object;

        if (root.get("streams")) |streams_val| {
            if (streams_val.object) |streams| {
                var iter = streams.iterator();
                while (iter.next()) |entry| {
                    const stream_obj = entry.value.object;
                    const bitrate_int = stream_obj.get("bitrate").?.integer;
                    const stream = AudioStream{
                        .id = try self.allocator.dupe(u8, entry.key),
                        .user_id = try self.allocator.dupe(u8, stream_obj.get("user_id").?.string),
                        .format = try self.allocator.dupe(u8, stream_obj.get("format").?.string),
                        .bitrate = @as(i32, @intCast(bitrate_int)),
                        .status = try self.allocator.dupe(u8, stream_obj.get("status").?.string),
                        .started_at = stream_obj.get("started_at").?.integer,
                        .listener_count = @as(i32, @intCast(stream_obj.get("listener_count").?.integer)),
                    };
                    try self.streams.put(stream.id, stream);
                }
            }
        }

        if (root.get("next_stream_id")) |next_id| {
            self.next_stream_id = @as(i32, @intCast(next_id.integer));
        }
        if (root.get("next_playlist_id")) |next_id| {
            self.next_playlist_id = @as(i32, @intCast(next_id.integer));
        }

        std.log.info("Restored {d} streams", .{self.streams.count()});
    }

    fn persistToEts(self: *@This()) !void {
        var arena = std.heap.ArenaAllocator.init(self.allocator);
        defer arena.deinit();
        const arena_allocator = arena.allocator();

        var streams_obj = json.ObjectMap.init(arena_allocator);
        var stream_iter = self.streams.iterator();
        while (stream_iter.next()) |entry| {
            var stream_obj = json.ObjectMap.init(arena_allocator);
            try stream_obj.put("user_id", json.Value{ .string = entry.value_ptr.user_id });
            try stream_obj.put("format", json.Value{ .string = entry.value_ptr.format });
            try stream_obj.put("bitrate", json.Value{ .integer = entry.value_ptr.bitrate });
            try stream_obj.put("status", json.Value{ .string = entry.value_ptr.status });
            try stream_obj.put("started_at", json.Value{ .integer = entry.value_ptr.started_at });
            try stream_obj.put("listener_count", json.Value{ .integer = entry.value_ptr.listener_count });
            try streams_obj.put(entry.key_ptr.*, json.Value{ .object = stream_obj });
        }

        var root_obj = json.ObjectMap.init(arena_allocator);
        try root_obj.put("streams", json.Value{ .object = streams_obj });
        try root_obj.put("next_stream_id", json.Value{ .integer = self.next_stream_id });
        try root_obj.put("next_playlist_id", json.Value{ .integer = self.next_playlist_id });

        const root_value = json.Value{ .object = root_obj };
        const json_str = try json.stringifyAlloc(arena_allocator, root_value, .{});
        defer arena_allocator.free(json_str);

        const file = try std.fs.cwd().createFile(self.ets_file, .{});
        defer file.close();
        _ = try file.writeAll(json_str);

        std.log.info("AudioStreaming data persisted to {s}", .{self.ets_file});
    }

    pub fn startStream(self: *@This(), user_id: []const u8, format: []const u8, bitrate: i32) !AudioStream {
        const stream_id = try fmt.allocPrint(self.allocator, "stream_{d}", .{self.next_stream_id});
        self.next_stream_id += 1;

        const stream = AudioStream{
            .id = stream_id,
            .user_id = try self.allocator.dupe(u8, user_id),
            .format = try self.allocator.dupe(u8, format),
            .bitrate = bitrate,
            .status = "active",
            .started_at = time.timestamp(),
            .listener_count = 0,
        };

        try self.streams.put(stream_id, stream);
        std.log.info("Started stream: {s}", .{stream_id});
        return stream;
    }

    pub fn stopStream(self: *@This(), stream_id: []const u8) !void {
        if (self.streams.get(stream_id)) |stream| {
            var updated = stream;
            updated.status = "stopped";
            try self.streams.put(stream_id, updated);
        }
        std.log.info("Stopped stream: {s}", .{stream_id});
    }

    pub fn createPlaylist(self: *@This(), user_id: []const u8, name: []const u8, track_ids: [][]const u8) !Playlist {
        const playlist_id = try fmt.allocPrint(self.allocator, "playlist_{d}", .{self.next_playlist_id});
        self.next_playlist_id += 1;

        const tracks = try self.allocator.alloc([]const u8, track_ids.len);
        for (track_ids, 0..) |track_id, i| {
            tracks[i] = try self.allocator.dupe(u8, track_id);
        }

        const playlist = Playlist{
            .id = playlist_id,
            .user_id = try self.allocator.dupe(u8, user_id),
            .name = try self.allocator.dupe(u8, name),
            .tracks = tracks,
            .created_at = time.timestamp(),
        };

        try self.playlists.put(playlist_id, playlist);
        std.log.info("Created playlist: {s}", .{playlist_id});
        return playlist;
    }

    pub fn joinListeningSession(self: *@This(), stream_id: []const u8, user_id: []const u8) !void {
        const user_key = try self.allocator.dupe(u8, user_id);
        const stream_val = try self.allocator.dupe(u8, stream_id);
        try self.listeners.put(user_key, stream_val);

        if (self.streams.getPtr(stream_id)) |stream| {
            stream.listener_count += 1;
        }
        std.log.info("User {s} joined listening session for stream {s}", .{ user_id, stream_id });
    }

    pub fn leaveListeningSession(self: *@This(), user_id: []const u8) !void {
        if (self.listeners.get(user_id)) |stream_id| {
            if (self.streams.getPtr(stream_id)) |stream| {
                stream.listener_count -= 1;
            }
        }
        _ = self.listeners.remove(user_id);
        std.log.info("User {s} left listening session", .{user_id});
    }

    pub fn getStreamStats(self: *@This(), stream_id: []const u8) ?AudioStream {
        return self.streams.get(stream_id);
    }

    pub fn terminate(self: *@This()) void {
        self.persistToEts() catch {};
        std.log.info("AudioStreaming terminating", .{});
    }
};

pub fn main() !void {
    const allocator = std.heap.page_allocator;
    var audio_streaming = try AudioStreaming.init(allocator, "/dev/shm/data/audio_streaming.json");
    defer audio_streaming.deinit();

    const stream = try audio_streaming.startStream("user_123", "mp3", 128);
    std.debug.print("Started stream: {s}\n", .{stream.id});

    try audio_streaming.createPlaylist("user_123", "My Playlist", &[_][]const u8{ "track_1", "track_2" });
    std.debug.print("Created playlist\n", .{});

    try audio_streaming.joinListeningSession(stream.id, "listener_1");
    std.debug.print("Listener joined\n", .{});

    audio_streaming.terminate();
}
