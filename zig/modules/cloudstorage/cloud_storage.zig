const std = @import("std");

pub const CloudStorageError = error{
    UploadFailed,
    DownloadFailed,
    DeleteFailed,
    FileNotFound,
    InvalidPath,
    StorageFull,
    PermissionDenied,
    NotImplemented,
};

pub const FileMetadata = struct {
    name: []const u8,
    size: u64,
    content_type: []const u8,
    created_at: i64,
    modified_at: i64,
    checksum: []const u8,
};

pub const StorageProvider = enum {
    local,
    s3,
    gcs,
    azure,
};

pub const CloudStorageConfig = struct {
    provider: StorageProvider,
    bucket: []const u8,
    region: []const u8,
    endpoint: ?[]const u8,

    /// Credentials must be loaded from environment variables at runtime.
    /// Storing credentials in code or config files is a security vulnerability.
    ///
    /// SECURITY: Never hardcode access_key/secret_key.
    /// Use loadConfigFromEnv() to load credentials securely from:
    /// - Environment variables: CLOUD_ACCESS_KEY, CLOUD_SECRET_KEY
    /// - Or a secrets manager (AWS Secrets Manager, HashiCorp Vault)
    access_key: []const u8,
    secret_key: []const u8,
};

/// Load CloudStorageConfig from environment variables.
/// Fails if CLOUD_ACCESS_KEY or CLOUD_SECRET_KEY is not set.
///
/// Usage:
///   const config = try CloudStorageConfig.loadFromEnv(allocator, .s3, "my-bucket", "us-east-1");
///
pub fn loadConfigFromEnv(
    allocator: std.mem.Allocator,
    provider: StorageProvider,
    bucket: []const u8,
    region: []const u8,
    endpoint: ?[]const u8,
) !CloudStorageConfig {
    const access_key = std.process.getEnvVarOwned(allocator, "CLOUD_ACCESS_KEY") catch {
        return error.MissingCloudAccessKey;
    };
    errdefer allocator.free(access_key);

    const secret_key = std.process.getEnvVarOwned(allocator, "CLOUD_SECRET_KEY") catch {
        allocator.free(access_key);
        return error.MissingCloudSecretKey;
    };

    return CloudStorageConfig{
        .provider = provider,
        .bucket = bucket,
        .region = region,
        .endpoint = endpoint,
        .access_key = access_key,
        .secret_key = secret_key,
    };
}

pub const CloudStorage = struct {
    allocator: std.mem.Allocator,
    config: CloudStorageConfig,

    pub fn init(allocator: std.mem.Allocator, config: CloudStorageConfig) CloudStorage {
        return CloudStorage{
            .allocator = allocator,
            .config = config,
        };
    }

    pub fn uploadFile(self: *CloudStorage, file_path: []const u8, object_name: []const u8, content_type: []const u8) !FileMetadata {
        const file = std.fs.cwd().openFile(file_path, .{}) catch {
            return error.FileNotFound;
        };
        defer file.close();

        const stat = try file.stat();
        const size = stat.size;

        const content = try self.allocator.alloc(u8, size);
        defer self.allocator.free(content);

        _ = try file.readAll(content);

        const checksum = try calculateChecksum(self.allocator, content);

        const metadata = FileMetadata{
            .name = object_name,
            .size = @as(u64, size),
            .content_type = content_type,
            .created_at = std.time.timestamp(),
            .modified_at = std.time.timestamp(),
            .checksum = checksum,
        };

        try self.uploadObject(object_name, content, &metadata);

        return metadata;
    }

    pub fn uploadObject(self: *CloudStorage, object_name: []const u8, content: []const u8, metadata: *FileMetadata) !void {
        _ = self;
        _ = object_name;
        _ = content;
        _ = metadata;
        return error.NotImplemented;
    }

    pub fn downloadObject(self: *CloudStorage, object_name: []const u8) ![]const u8 {
        _ = self;
        _ = object_name;
        return error.NotImplemented;
    }

    pub fn deleteFile(self: *CloudStorage, object_name: []const u8) !void {
        _ = self;
        _ = object_name;
        return error.NotImplemented;
    }

    pub fn listObjects(self: *CloudStorage, prefix: []const u8) ![]const FileMetadata {
        _ = self;
        _ = prefix;
        return error.NotImplemented;
    }

    pub fn copyObject(self: *CloudStorage, src_name: []const u8, dest_name: []const u8) !void {
        _ = self;
        _ = src_name;
        _ = dest_name;
        return error.NotImplemented;
    }

    pub fn downloadFile(self: *CloudStorage, object_name: []const u8, dest_path: []const u8) !FileMetadata {
        _ = self;
        _ = object_name;
        _ = dest_path;
        return error.NotImplemented;
    }

    pub fn getObjectUrl(self: *CloudStorage, object_name: []const u8) ![]const u8 {
        const endpoint = self.config.endpoint orelse "";
        return try std.fmt.allocPrint(self.allocator, "{s}/{s}/{s}", .{ endpoint, self.config.bucket, object_name });
    }

    pub fn moveObject(self: *CloudStorage, src_name: []const u8, dest_name: []const u8) !void {
        try self.copyObject(src_name, dest_name);
        try self.deleteFile(src_name);
    }

    pub fn getObjectSize(self: *CloudStorage, object_name: []const u8) !u64 {
        const metadata = try self.getObjectMetadata(object_name);
        return metadata.size;
    }

    pub fn getObjectMetadata(self: *CloudStorage, object_name: []const u8) !FileMetadata {
        _ = self;
        _ = object_name;
        return error.NotImplemented;
    }

    pub fn createDirectory(self: *CloudStorage, path: []const u8) !void {
        _ = self;
        _ = path;
        return error.NotImplemented;
    }

    pub fn deleteDirectory(self: *CloudStorage, path: []const u8) !void {
        _ = self;
        _ = path;
        return error.NotImplemented;
    }

    pub fn generateSignedUrl(self: *CloudStorage, object_name: []const u8, expires_in_seconds: i64) ![]const u8 {
        _ = self;
        _ = object_name;
        _ = expires_in_seconds;
        return error.NotImplemented;
    }
};

pub fn calculateChecksum(allocator: std.mem.Allocator, data: []const u8) ![]const u8 {
    var hash = std.crypto.hash.sha256.init(.{});
    hash.update(data);
    var digest: [32]u8 = undefined;
    hash.final(&digest);

    const checksum = try allocator.alloc(u8, 64);
    for (digest, 0..) |byte, i| {
        _ = std.fmt.formatIntHex(byte, 2, .lower, .{}, checksum[i * 2 .. i * 2 + 2]);
    }

    return checksum;
}

pub fn compressData(allocator: std.mem.Allocator, data: []const u8, level: u6) ![]const u8 {
    _ = level;
    var out_buffer = try allocator.alloc(u8, try std.compress.gzip.compressBounds(data));
    var stream = std.compress.gzip.compressStream(allocator, out_buffer);
    try stream.writeAll(data);
    stream.finalize() catch {};

    return out_buffer[0..stream.getWritten()];
}

pub fn decompressData(allocator: std.mem.Allocator, compressed: []const u8) ![]const u8 {
    var in_stream = std.io.fixedBufferStream(compressed);
    var decompressor = std.compress.gzip.decompressStream(allocator, in_stream.reader());
    return try decompressor.readAllAlloc(allocator, std.math.maxInt(usize));
}

pub const ChunkedUpload = struct {
    upload_id: []const u8,
    parts: []const u8,
    current_part: u32,
    total_parts: u32,
    file_name: []const u8,
    file_size: u64,
    content_type: []const u8,
    checksum: []const u8,

    pub fn init(file_name: []const u8, file_size: u64, content_type: []const u8) ChunkedUpload {
        return ChunkedUpload{
            .upload_id = "",
            .parts = &[_]u8{},
            .current_part = 0,
            .total_parts = @as(u32, @intCast((file_size + 5 * 1024 * 1024 - 1) / (5 * 1024 * 1024))),
            .file_name = file_name,
            .file_size = file_size,
            .content_type = content_type,
            .checksum = "",
        };
    }
};

pub const MultipartUpload = struct {
    allocator: std.mem.Allocator,
    upload_id: []const u8,
    parts: std.ArrayList(PartInfo),
    object_name: []const u8,

    pub const PartInfo = struct {
        part_number: u32,
        etag: []const u8,
        size: u64,
    };

    pub fn init(allocator: std.mem.Allocator) MultipartUpload {
        return MultipartUpload{
            .allocator = allocator,
            .upload_id = "",
            .parts = std.ArrayList(PartInfo).init(allocator),
            .object_name = "",
        };
    }

    pub fn deinit(self: *MultipartUpload) void {
        self.parts.deinit();
    }
};
