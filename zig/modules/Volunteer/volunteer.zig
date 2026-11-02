const std = @import("std");
const fmt = std.fmt;

pub const VolunteerOpportunity = struct {
    id: []u8,
    title: []const u8,
    organization: []const u8,
    location: []const u8,
    date: []const u8,
    volunteers_needed: u32,
    volunteers_joined: u32,
    category: []const u8,
};

pub const VolunteerRecord = struct {
    id: []u8,
    citizen: []const u8,
    opportunity: []const u8,
    hours: u32,
    date: []const u8,
    status: []const u8,
};

var volunteer_allocator: std.mem.Allocator = undefined;
var opportunities: ?std.ArrayList(VolunteerOpportunity) = null;
var records: ?std.ArrayList(VolunteerRecord) = null;
var id_counter: u32 = 5;

/// Initialize the Volunteer module with the given allocator.
/// If already initialized, deinitializes first to allow reinitialization (for tests).
pub fn init(allocator: std.mem.Allocator) !void {
    // If already initialized, clean up first (test scenario)
    if (opportunities != null or records != null) {
        deinit();
    }
    volunteer_allocator = allocator;
    opportunities = try std.ArrayList(VolunteerOpportunity).initCapacity(allocator, 0);
    records = try std.ArrayList(VolunteerRecord).initCapacity(allocator, 0);

    // Seed initial opportunities with allocated IDs to simplify deinit.
    try opportunities.?.append(allocator, .{
        .id = try fmt.allocPrint(allocator, "1", .{}),
        .title = "Dọn dẹp môi trường",
        .organization = "Đoàn Thanh niên",
        .location = "Khu vực phường",
        .date = "2026-02-20",
        .volunteers_needed = 50,
        .volunteers_joined = 32,
        .category = "Môi trường",
    });
    try opportunities.?.append(allocator, .{
        .id = try fmt.allocPrint(allocator, "2", .{}),
        .title = "Thăm hỏi người cao tuổi",
        .organization = "Hội Người cao tuổi",
        .location = "Nhà dân",
        .date = "2026-02-18",
        .volunteers_needed = 20,
        .volunteers_joined = 15,
        .category = "Xã hội",
    });
    try opportunities.?.append(allocator, .{
        .id = try fmt.allocPrint(allocator, "3", .{}),
        .title = "Dạy kèm trẻ em",
        .organization = "Hội Phụ nữ",
        .location = "Trường Tiểu học",
        .date = "2026-02-22",
        .volunteers_needed = 15,
        .volunteers_joined = 10,
        .category = "Giáo dục",
    });
    try opportunities.?.append(allocator, .{
        .id = try fmt.allocPrint(allocator, "4", .{}),
        .title = "Hiến máu tình nguyện",
        .organization = "Hội Chữ thập đỏ",
        .location = "Trạm y tế",
        .date = "2026-03-01",
        .volunteers_needed = 100,
        .volunteers_joined = 45,
        .category = "Y tế",
    });

    try records.?.append(allocator, .{
        .id = try fmt.allocPrint(allocator, "1", .{}),
        .citizen = "Nguyễn Văn A",
        .opportunity = "Dọn dẹp môi trường",
        .hours = 4,
        .date = "2026-02-10",
        .status = "Hoàn thành",
    });
    try records.?.append(allocator, .{
        .id = try fmt.allocPrint(allocator, "2", .{}),
        .citizen = "Trần Thị B",
        .opportunity = "Thăm hỏi người cao tuổi",
        .hours = 3,
        .date = "2026-02-08",
        .status = "Hoàn thành",
    });
    try records.?.append(allocator, .{
        .id = try fmt.allocPrint(allocator, "3", .{}),
        .citizen = "Lê Văn C",
        .opportunity = "Dạy kèm trẻ em",
        .hours = 6,
        .date = "2026-02-05",
        .status = "Hoàn thành",
    });
    try records.?.append(allocator, .{
        .id = try fmt.allocPrint(allocator, "4", .{}),
        .citizen = "Phạm Thị D",
        .opportunity = "Hiến máu tình nguyện",
        .hours = 2,
        .date = "2026-02-01",
        .status = "Hoàn thành",
    });
}

/// Internal helper to append an opportunity, allocating a fresh copy of the ID.
fn appendOpportunity(opp: VolunteerOpportunity) !void {
    const allocator = volunteer_allocator;
    var new_opp = opp;
    new_opp.id = try fmt.allocPrint(allocator, "{}", .{id_counter});
    id_counter += 1;
    try opportunities.?.append(allocator, new_opp);
}

/// Internal helper to append a record, allocating a fresh copy of the ID.
fn appendRecord(rec: VolunteerRecord) !void {
    const allocator = volunteer_allocator;
    var new_rec = rec;
    new_rec.id = try fmt.allocPrint(allocator, "{}", .{id_counter});
    id_counter += 1;
    try records.?.append(allocator, new_rec);
}

pub fn getOpportunities() []const VolunteerOpportunity {
    return (opportunities orelse std.ArrayList(VolunteerOpportunity){}).items;
}

pub fn getOpportunity(id: []const u8) ?VolunteerOpportunity {
    const opps = opportunities orelse return null;
    for (opps.items) |opp| {
        if (std.mem.eql(u8, opp.id, id)) {
            return opp;
        }
    }
    return null;
}

pub fn getRecords() []const VolunteerRecord {
    return (records orelse std.ArrayList(VolunteerRecord){}).items;
}

pub fn getStatistics() struct {
    total_volunteers: u32,
    active_opportunities: u32,
    total_hours: u32,
    certificates_issued: u32,
} {
    const opps = opportunities orelse return .{
        .total_volunteers = 0,
        .active_opportunities = 0,
        .total_hours = 0,
        .certificates_issued = 0,
    };
    const all_recs = records orelse std.ArrayList(VolunteerRecord){};
    var total_hours: u32 = 0;
    for (all_recs.items) |r| {
        total_hours += r.hours;
    }
    return .{
        .total_volunteers = 156,
        .active_opportunities = @intCast(opps.items.len),
        .total_hours = total_hours,
        .certificates_issued = 89,
    };
}

pub fn addOpportunity(opp: VolunteerOpportunity) !void {
    try appendOpportunity(opp);
}

pub fn addRecord(rec: VolunteerRecord) !void {
    try appendRecord(rec);
}

pub fn deleteOpportunity(id: []const u8) bool {
    if (opportunities) |*opps| {
        for (opps.items, 0..) |_, i| {
            if (std.mem.eql(u8, opps.items[i].id, id)) {
                const removed = opps.orderedRemove(i);
                volunteer_allocator.free(removed.id);
                return true;
            }
        }
    }
    return false;
}

pub fn deinit() void {
    if (opportunities) |*opps| {
        // Free allocated IDs
        for (opps.items) |*opp| {
            volunteer_allocator.free(opp.id);
        }
        opps.deinit(volunteer_allocator);
    }
    opportunities = null;
    if (records) |*recs| {
        for (recs.items) |*rec| {
            volunteer_allocator.free(rec.id);
        }
        recs.deinit(volunteer_allocator);
    }
    records = null;
}
