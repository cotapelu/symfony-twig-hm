const std = @import("std");
const http = std.http;
const json = std.json;
const mem = std.mem;
const crypto = std.crypto;

const Message = struct {
    id: []const u8,
    conversation_id: []const u8,
    sender_id: []const u8,
    content: []const u8,
    created_at: i64,
    type: []const u8,
    updated_at: ?i64,
    attachments: ?[]const u8,
    is_deleted: bool = false,
};

const CreateMessageRequest = struct {
    conversation_id: []const u8,
    sender_id: []const u8,
    content: []const u8,
    type: []const u8,
    attachments: ?[]const u8,
};

const DeleteMessageRequest = struct {
    message_id: []const u8,
    user_id: []const u8,
    conversation_id: []const u8,
    for_everyone: bool,
};

const GetMessageRequest = struct {
    message_id: []const u8,
    user_id: []const u8,
    conversation_id: []const u8,
    page: u32,
    limit: u32,
};

const GetMessagesRequest = struct {
    conversation_id: []const u8,
    user_id: []const u8,
    cursor: ?[]const u8,
    limit: u32,
    include_deleted: bool = false,
};

const MessageServiceError = error{
    InvalidRequest,
    MessageNotFound,
    ConversationNotFound,
    Unauthorized,
};

pub const MessageService = struct {
    allocator: mem.Allocator,
    messages: std.StringHashMap([]const u8, Message),
    next_message_id: u32 = 0,
};

pub fn init(allocator: mem.Allocator) MessageService {
    return MessageService{
        .allocator = allocator,
        .messages = std.StringHashMap([]const u8, Message).init(allocator),
        .next_message_id = 1,
    };
}

pub fn createMessage(service: *MessageService, req: CreateMessageRequest) !Message {
    const now = std.time.timestamp();

    const message = Message{
        .id = generateId(service.next_message_id),
        .conversation_id = req.conversation_id,
        .sender_id = req.sender_id,
        .content = req.content,
        .created_at = now,
        .type = if (req.type.len > 0) req.type else "text",
        .updated_at = null,
        .attachments = req.attachments,
    };

    service.next_message_id += 1;
    service.messages.put(message.id, message);

    return message;
}

pub fn deleteMessage(service: *MessageService, req: DeleteMessageRequest) !bool {
    const message = service.messages.get(req.message_id) orelse return error.MessageNotFound;

    if (message.sender_id != req.user_id) {
        return error.Unauthorized;
    }

    message.is_deleted = true;
    message.updated_at = std.time.timestamp();

    return true;
}

pub fn getMessage(service: *MessageService, req: GetMessageRequest) ?Message {
    const message = service.messages.get(req.message_id) orelse return null;

    if (!req.include_deleted and message.is_deleted) {
        return null;
    }

    if (message.sender_id != req.user_id and !req.include_deleted) {
        return null;
    }

    return message;
}

pub fn getMessages(service: *MessageService, req: GetMessagesRequest) []const Message {
    var filtered = std.ArrayList([]const Message).init(service.allocator);

    var iterator = service.messages.valueIterator();
    while (iterator.next()) |entry| {
        const message = entry.key_ptr.*;

        if (message.conversation_id != req.conversation_id) continue;
        if (!req.include_deleted and message.is_deleted) continue;

        filtered.append(message);
        if (filtered.len >= req.limit) break;
    }

    const page_start: usize = @intCast((req.page - 1) * req.limit);
    const page_end = @min(filtered.len, page_start + req.limit);

    for (page_start..page_end) |i| {
        if (i >= filtered.len) break;
        const message = filtered.get(i);
        filtered.set(i, message.*);
    }

    return filtered.toOwnedSlice();
}

pub fn getConversationMessageCount(service: *MessageService, conversation_id: []const u8) usize {
    var count: usize = 0;
    var iterator = service.messages.valueIterator();

    while (iterator.next()) |entry| {
        const message = entry.key_ptr.*;
        if (message.conversation_id == conversation_id and !message.is_deleted) {
            count += 1;
        }
    }

    return count;
}

fn generateId(next_id: u32) []const u8 {
    var hash_input: [32]u8 = undefined;
    std.mem.copy(u8, &next_id, &hash_input);

    const unique_id = std.fmt.hex(hash_input, "");

    return unique_id[0..];
}

fn generateIdSimple(next_id: u32) []const u8 {
    return std.fmt.hex(next_id, "0", 16);
}

pub fn formatMessageTimestamp(ts: i64) []const u8 {
    const time = std.time.unixToDateTime(ts);
    return std.fmt.allocPrint("{s}", time);
}
