const std = @import("std");
const testing = std.testing;

// Import Common utils for tests
const utils = @import("modules/Common/utils.zig");

// Import modules under test
const SampleRepository = @import("modules/SampleModule/Infrastructure/sample_repository.zig").SampleRepository;
const SampleEntity = @import("modules/SampleModule/Domain/sample_entity.zig").SampleEntity;
const CryptoService = @import("modules/cryptoservice/cryptoservice.zig").CryptoService;
const cloudstorage = @import("modules/cloudstorage/cloudstorage.zig").cloudstorage;
const appcore = @import("modules/appcore.zig");

// Include inline tests from other modules to expand test suite
// Include inline tests from other modules (selected, safe)
const _audiostreaming = @import("modules/audiostreaming/audiostreaming.zig");
const _contentmoderation = @import("modules/contentmoderation/contentmoderation.zig");
const _datacompression = @import("modules/datacompression/datacompression.zig");
const _mediaprocessing = @import("modules/mediaprocessing/mediaprocessing.zig");
const _networkprotocol = @import("modules/networkprotocol/networkprotocol.zig");
const _securemessaging = @import("modules/securemessaging/securemessaging.zig");
const _identity_test = @import("modules/identity_test.zig");
const _volunteer = @import("modules/Volunteer/volunteer.zig");
const userprofile = @import("modules/userprofile.zig");
const _appcore = @import("modules/appcore.zig");

// Force inclusion of identity_test module via dummy test
test "include additional modules" {
    _ = _audiostreaming;
    _ = _contentmoderation;
    _ = _datacompression;
    _ = _mediaprocessing;
    _ = _networkprotocol;
    _ = _securemessaging;
    _ = _identity_test;
    _ = _volunteer;
    _ = userprofile;
    _ = _appcore;
}

// Global state for EventDispatcher tests
var ed_test_called: bool = false;
var ed_test_call_count: u32 = 0;
var ed_call_order: [10][]const u8 = undefined;
var ed_order_len: usize = 0;

fn ed_test_listener(_: *appcore.Event) anyerror!void {
    ed_test_called = true;
    ed_test_call_count += 1;
}

fn ed_listener_high(_: *appcore.Event) anyerror!void {
    ed_call_order[ed_order_len] = "high";
    ed_order_len += 1;
}
fn ed_listener_low(_: *appcore.Event) anyerror!void {
    ed_call_order[ed_order_len] = "low";
    ed_order_len += 1;
}
fn ed_wildcard_listener(_: *appcore.Event) anyerror!void {
    ed_call_order[ed_order_len] = "wildcard";
    ed_order_len += 1;
}


// Existing tests

// Helper to convert a string to a fixed-size [64]u8 array for userprofile functions.
fn toFixed64(s: []const u8) [64]u8 {
    var arr: [64]u8 = undefined;
    const len = @min(s.len, 64);
    @memcpy(arr[0..len], s);
    if (len < 64) arr[len] = 0;
    return arr;
}

test "simple assertion" {
    const value = 42;
    try testing.expectEqual(@as(u32, 42), value);
}

test "string comparison" {
    const expected = "hello";
    const actual = "hello";
    try testing.expectEqualStrings(expected, actual);
}

test "array operations" {
    const arr = [_]i32{ 1, 2, 3 };
    try testing.expectEqual(@as(i32, 1), arr[0]);
    try testing.expectEqual(@as(i32, 2), arr[1]);
    try testing.expectEqual(@as(i32, 3), arr[2]);
}

test "cloud storage operations" {
    const CloudStorage = struct {
        bucket_name: []const u8,
        region: []const u8,
    };

    const storage = CloudStorage{
        .bucket_name = "my-bucket",
        .region = "us-east-1",
    };

    try testing.expectEqualStrings("my-bucket", storage.bucket_name);
}

test "network protocol validation" {
    const NetworkPacket = struct {
        protocol: []const u8,
        port: u16,
        valid: bool,
    };

    const packet = NetworkPacket{
        .protocol = "TCP",
        .port = 8080,
        .valid = true,
    };

    try testing.expect(packet.valid);
    try testing.expectEqual(@as(u16, 8080), packet.port);
}

test "utils add" {
    try testing.expectEqual(@as(i32, 5), utils.add(2, 3));
}

test "utils multiply" {
    try testing.expectEqual(@as(i32, 20), utils.multiply(4, 5));
}

test "utils factorial" {
    try testing.expectEqual(@as(u64, 120), utils.factorial(5));
    try testing.expectEqual(@as(u64, 1), utils.factorial(0));
}

test "utils contains" {
    const str = "hello world";
    try testing.expect(utils.contains(str, "world"));
    try testing.expect(!utils.contains(str, "foo"));
}

test "utils toUpper" {
    var buffer = "hello".*;
    utils.toUpper(&buffer);
    try testing.expectEqualStrings("HELLO", &buffer);
}

// CloudStorage tests

test "cloudstorage initialization" {
    var mod = cloudstorage.init(std.testing.allocator);
    try std.testing.expect(mod.initialized);
    mod.deinit();
    try std.testing.expect(!mod.initialized);
}

// CryptoService tests

test "CryptoService encrypt/decrypt" {
    var gpa = std.testing.allocator;
    var service = CryptoService.init(gpa);

    const data = "Hello, World!";
    const key = "secretKey";

    const encrypted = try service.encrypt(data, key);
    defer gpa.free(encrypted);

    const decrypted = try service.decrypt(encrypted, key);
    defer gpa.free(decrypted);

    try std.testing.expectEqualStrings(data, decrypted);
}

test "CryptoService hash/verifyHash" {
    var gpa = std.testing.allocator;
    var service = CryptoService.init(gpa);

    const data = "test data";

    const computedHash = try service.hash(data);
    defer gpa.free(computedHash);

    const isValid = try service.verifyHash(data, computedHash);
    try std.testing.expect(isValid);

    const wrongData = "wrong data";
    const isNotValid = try service.verifyHash(wrongData, computedHash);
    try std.testing.expect(!isNotValid);
}

test "CryptoService sign/verifySignature" {
    var gpa = std.testing.allocator;
    var service = CryptoService.init(gpa);

    const data = "important message";

    const keys = try service.generateKeyPair();
    defer service.freeKeyPair(keys);

    const signature = try service.sign(data, keys[1]);
    defer gpa.free(signature);

    const isValid = try service.verifySignature(data, signature, keys[0]);
    try std.testing.expect(isValid);
}

// CryptoService error path tests

test "CryptoService encrypt empty data returns error" {
    var service = CryptoService.init(std.testing.allocator);
    const result = service.encrypt("", "key");
    try std.testing.expectError(error.EmptyData, result);
}

test "CryptoService encrypt empty key returns error" {
    var service = CryptoService.init(std.testing.allocator);
    const result = service.encrypt("data", "");
    try std.testing.expectError(error.EmptyKey, result);
}

test "CryptoService hash empty data returns error" {
    var service = CryptoService.init(std.testing.allocator);
    const result = service.hash("");
    try std.testing.expectError(error.EmptyData, result);
}

test "CryptoService verifyHash mismatch returns false" {
    var service = CryptoService.init(std.testing.allocator);
    const data = "test";
    const hash = try service.hash(data);
    defer std.testing.allocator.free(hash);
    const isValid = try service.verifyHash("wrong", hash);
    try std.testing.expect(!isValid);
}

test "CryptoService sign empty data returns error" {
    var service = CryptoService.init(std.testing.allocator);
    const keys = try service.generateKeyPair();
    defer service.freeKeyPair(keys);
    const result = service.sign("", keys[1]);
    try std.testing.expectError(error.EmptyData, result);
}

test "CryptoService sign empty private key returns error" {
    var service = CryptoService.init(std.testing.allocator);
    const empty_key: []const u8 = "";
    const result = service.sign("data", empty_key);
    try std.testing.expectError(error.EmptyPrivateKey, result);
}

test "CryptoService verifySignature empty data returns error" {
    var service = CryptoService.init(std.testing.allocator);
    const keys = try service.generateKeyPair();
    defer service.freeKeyPair(keys);
    const sig = try service.sign("data", keys[1]);
    defer std.testing.allocator.free(sig);
    const result = service.verifySignature("", sig, keys[0]);
    try std.testing.expectError(error.EmptyData, result);
}

test "CryptoService verifySignature empty public key returns error" {
    var service = CryptoService.init(std.testing.allocator);
    const keys = try service.generateKeyPair();
    defer service.freeKeyPair(keys);
    const sig = try service.sign("data", keys[1]);
    defer std.testing.allocator.free(sig);
    const empty_key: []const u8 = "";
    const result = service.verifySignature("data", sig, empty_key);
    try std.testing.expectError(error.EmptyPublicKey, result);
}

test "CryptoService verifySignature wrong length returns error" {
    var service = CryptoService.init(std.testing.allocator);
    const keys = try service.generateKeyPair();
    defer service.freeKeyPair(keys);
    // slice with length 0 is not 64
    const bad_sig: []const u8 = &[_]u8{};
    const result = service.verifySignature("data", bad_sig, keys[0]);
    try std.testing.expectError(error.InvalidSignatureLength, result);
}

// SampleRepository tests

test "SampleRepository addItem and getItems" {
    var repo = SampleRepository.init(std.testing.allocator);
    defer repo.deinit();

    const item1 = SampleEntity{
        .id = "id1",
        .name = "first",
        .value = 100,
    };
    const item2 = SampleEntity{
        .id = "id2",
        .name = "second",
        .value = 200,
    };

    try repo.addItem(item1);
    try repo.addItem(item2);

    const items = repo.getItems();
    try std.testing.expectEqual(@as(usize, 2), items.len);
    try std.testing.expectEqualStrings("id1", items[0].id);
    try std.testing.expectEqualStrings("first", items[0].name);
    try std.testing.expectEqual(@as(i32, 100), items[0].value);
    try std.testing.expectEqualStrings("id2", items[1].id);
}

test "SampleRepository getItems empty initially" {
    var repo = SampleRepository.init(std.testing.allocator);
    defer repo.deinit();

    const items = repo.getItems();
    try std.testing.expectEqual(@as(usize, 0), items.len);
}

test "NetworkProtocol operations" {
    var proto = _networkprotocol.NetworkProtocol.init(std.testing.allocator);
    var conn = try proto.connect("127.0.0.1", 8080);
    try std.testing.expect(conn.connected);

    const data = "hello";
    const sent = try proto.send(&conn, data);
    try std.testing.expectEqual(@as(usize, data.len), sent);

    var buf: [100]u8 = undefined;
    const received = try proto.receive(&conn, &buf);
    try std.testing.expectEqual(@as(usize, 0), received);

    proto.disconnect(&conn);
    try std.testing.expect(!conn.connected);
}

// Additional tests from other modules

test "contentmoderation initialization" {
    var mod = _contentmoderation.contentmoderation.init(std.testing.allocator);
    try std.testing.expect(mod.initialized);
    mod.deinit();
    try std.testing.expect(!mod.initialized);
}

test "datacompression initialization" {
    var mod = _datacompression.datacompression.init(std.testing.allocator);
    try std.testing.expect(mod.initialized);
    mod.deinit();
    try std.testing.expect(!mod.initialized);
}

test "audiostreaming initialization" {
    var mod = _audiostreaming.audiostreaming.init(std.testing.allocator);
    try std.testing.expect(mod.initialized);
    mod.deinit();
    try std.testing.expect(!mod.initialized);
}

test "MediaProcessor initialization" {
    var processor = _mediaprocessing.MediaProcessor.init(std.testing.allocator);
    try std.testing.expect(processor.validateFormat("jpg"));
    try std.testing.expect(processor.validateFormat("PNG"));
    try std.testing.expect(!processor.validateFormat("bmp"));
}

test "MediaProcessor.compressVideo" {
    var processor = _mediaprocessing.MediaProcessor.init(std.testing.allocator);
    // stub implementation: should succeed without error
    try processor.compressVideo("input.mp4", "output.mp4", 80);
}

test "MediaProcessor.generateThumbnail" {
    var processor = _mediaprocessing.MediaProcessor.init(std.testing.allocator);
    try processor.generateThumbnail("video.mp4", "thumb.jpg", 320, 240);
}

test "Volunteer module" {
    try _volunteer.init(std.testing.allocator);
    defer _volunteer.deinit();

    const opps = _volunteer.getOpportunities();
    try std.testing.expect(opps.len >= 4);

    const recs = _volunteer.getRecords();
    try std.testing.expect(recs.len >= 4);

    const stats = _volunteer.getStatistics();
    try std.testing.expect(stats.total_volunteers > 0);
    try std.testing.expect(stats.active_opportunities >= 4);
    try std.testing.expect(stats.total_hours > 0);
    try std.testing.expect(stats.certificates_issued > 0);
}

// Volunteer additional tests

test "Volunteer addOpportunity and getOpportunities" {
    try _volunteer.init(std.testing.allocator);
    defer _volunteer.deinit();

    const initial_count = _volunteer.getOpportunities().len;

    // Prepare dummy id (empty mutable slice)
    var id_buf: [1]u8 = undefined;
    const dummy_id = id_buf[0..0];

    const new_opp = _volunteer.VolunteerOpportunity{
        .id = dummy_id,
        .title = "Test Activity",
        .organization = "Test Org",
        .location = "Test Location",
        .date = "2026-01-01",
        .volunteers_needed = 10,
        .volunteers_joined = 0,
        .category = "Testing",
    };
    try _volunteer.addOpportunity(new_opp);

    const opps = _volunteer.getOpportunities();
    try std.testing.expect(opps.len == initial_count + 1);
    const added = opps[opps.len - 1];
    try std.testing.expectEqualStrings("Test Activity", added.title);
    try std.testing.expect(added.id.len > 0);
}

test "Volunteer addRecord and getRecords" {
    try _volunteer.init(std.testing.allocator);
    defer _volunteer.deinit();

    const initial_count = _volunteer.getRecords().len;

    var id_buf: [1]u8 = undefined;
    const dummy_id = id_buf[0..0];

    const new_rec = _volunteer.VolunteerRecord{
        .id = dummy_id,
        .citizen = "Test Citizen",
        .opportunity = "Test Activity",
        .hours = 5,
        .date = "2026-01-02",
        .status = "Planned",
    };
    try _volunteer.addRecord(new_rec);

    const recs = _volunteer.getRecords();
    try std.testing.expect(recs.len == initial_count + 1);
    const added = recs[recs.len - 1];
    try std.testing.expectEqualStrings("Test Citizen", added.citizen);
    try std.testing.expect(added.id.len > 0);
}

test "Volunteer deleteOpportunity" {
    try _volunteer.init(std.testing.allocator);
    defer _volunteer.deinit();

    const before = _volunteer.getOpportunities().len;

    var id_buf: [1]u8 = undefined;
    const dummy_id = id_buf[0..0];

    const new_opp = _volunteer.VolunteerOpportunity{
        .id = dummy_id,
        .title = "ToDelete",
        .organization = "Org",
        .location = "Loc",
        .date = "2026-01-03",
        .volunteers_needed = 1,
        .volunteers_joined = 0,
        .category = "Temp",
    };
    try _volunteer.addOpportunity(new_opp);

    const opps_after_add = _volunteer.getOpportunities();
    const new_id = opps_after_add[opps_after_add.len - 1].id;

    const deleted = _volunteer.deleteOpportunity(new_id);
    try std.testing.expect(deleted);

    const opps_final = _volunteer.getOpportunities();
    try std.testing.expect(opps_final.len == before);
    try std.testing.expect(_volunteer.getOpportunity(new_id) == null);
}

// UserProfile tests added in Round 89
test "userprofile hash_password and verify_password" {
    const gpa = std.testing.allocator;

    const password = "s3cr3t!";
    const hash = try userprofile.hash_password(gpa, password);
    defer gpa.free(hash);

    const ok = try userprofile.verify_password(gpa, password, hash);
    try std.testing.expect(ok);

    const wrong = try userprofile.verify_password(gpa, "wrong", hash);
    try std.testing.expect(!wrong);
}

test "userprofile create_user" {
    const gpa = std.testing.allocator;
    const service = userprofile.init(gpa);

    const email = "user@example.com";
    const password = "password123";
    const user = try userprofile.create_user(@constCast(&service), email, password);
    try std.testing.expectEqualStrings(email, user.email);
    try std.testing.expectEqualStrings(email, user.id);
    try std.testing.expect(user.password_hash.len > 0);
    gpa.free(user.password_hash);
}

test "userprofile validate_email" {
    try std.testing.expect(userprofile.validate_email("a@b.co"));
    try std.testing.expect(!userprofile.validate_email("invalid"));
}

test "userprofile validate_password" {
    const p8 = "12345678";
    const valid8 = try userprofile.validate_password(p8);
    try std.testing.expect(valid8);
    const p7 = "1234567";
    const valid7 = try userprofile.validate_password(p7);
    try std.testing.expect(!valid7);
}

test "userprofile authenticate_user returns UserNotFound" {
    const gpa = std.testing.allocator;
    const service = userprofile.init(gpa);
    try std.testing.expectError(userprofile.AuthError.UserNotFound, userprofile.authenticate_user(@constCast(&service), "email", "pass"));
}


test "appcore ModuleRegistry init/deinit" {
    const gpa = std.testing.allocator;
    var registry = appcore.ModuleRegistry.init(gpa);
    defer registry.deinit();
}

// EventDispatcher tests

test "EventDispatcher init/deinit" {
    const gpa = std.testing.allocator;
    var dispatcher = appcore.EventDispatcher.init(gpa, false, 3);
    dispatcher.deinit();
}

test "EventDispatcher addListener and dispatch single listener" {
    ed_test_called = false;
    ed_test_call_count = 0;

    const gpa = std.testing.allocator;
    var dispatcher = appcore.EventDispatcher.init(gpa, false, 3);
    defer dispatcher.deinit();

    _ = try dispatcher.addListener("test.event", ed_test_listener, 0);

    var event = appcore.Event.init(gpa, "test.event");
    try dispatcher.dispatch(&event);
    event.deinit();

    try std.testing.expect(ed_test_called);
    try std.testing.expectEqual(@as(u32, 1), ed_test_call_count);
}

test "EventDispatcher multiple listeners priority order" {
    ed_order_len = 0;

    const gpa = std.testing.allocator;
    var dispatcher = appcore.EventDispatcher.init(gpa, false, 3);
    defer dispatcher.deinit();

    _ = try dispatcher.addListener("test.multi", ed_listener_low, 0);
    _ = try dispatcher.addListener("test.multi", ed_listener_high, 100);

    var event = appcore.Event.init(gpa, "test.multi");
    try dispatcher.dispatch(&event);
    event.deinit();

    try std.testing.expectEqual(@as(usize, 2), ed_order_len);
    try std.testing.expectEqualStrings("high", ed_call_order[0]);
    try std.testing.expectEqualStrings("low", ed_call_order[1]);
}

test "EventDispatcher wildcard listener" {
    ed_order_len = 0;

    const gpa = std.testing.allocator;
    var dispatcher = appcore.EventDispatcher.init(gpa, false, 3);
    defer dispatcher.deinit();

    _ = try dispatcher.addListener("specific.event", ed_listener_low, 0);
    _ = try dispatcher.addListener("*", ed_wildcard_listener, 0);

    var event = appcore.Event.init(gpa, "specific.event");
    try dispatcher.dispatch(&event);
    event.deinit();

    try std.testing.expectEqual(@as(usize, 2), ed_order_len);
    try std.testing.expectEqualStrings("low", ed_call_order[0]);
    try std.testing.expectEqualStrings("wildcard", ed_call_order[1]);
}

test "EventDispatcher dispatch with no listeners" {
    const gpa = std.testing.allocator;
    var dispatcher = appcore.EventDispatcher.init(gpa, false, 3);
    defer dispatcher.deinit();

    var event = appcore.Event.init(gpa, "any.event");
    try dispatcher.dispatch(&event);
    event.deinit();
}

test "Event init/deinit" {
    const gpa = std.testing.allocator;
    var event = appcore.Event.init(gpa, "test.name");
    event.deinit();
}

// Additional tests for ModuleRegistry to improve coverage

test "appcore ModuleRegistry discover and load" {
    const gpa = std.testing.allocator;
    var registry = appcore.ModuleRegistry.init(gpa);
    defer registry.deinit();

    // Discover two modules
    try registry.discover("modA", "/path/to/modA");
    try registry.discover("modB", "/path/to/modB");

    // Load modA
    try registry.load("modA");
    try std.testing.expect(registry.isLoaded("modA"));
    try std.testing.expect(!registry.isLoaded("modB"));

    // Get module instance for modA
    const instance_a = registry.getModule("modA");
    try std.testing.expect(instance_a != null);

    // Load modB
    try registry.load("modB");
    try std.testing.expect(registry.isLoaded("modB"));

    // Get dependencies for modA (should be empty slice)
    const deps_a = registry.getDependencies("modA");
    try std.testing.expect(deps_a.len == 0);

    // Get load order
    var order = try registry.getLoadOrder();
    try std.testing.expect(order.len >= 2);
    var found_a = false;
    var found_b = false;
    for (order) |name| {
        if (std.mem.eql(u8, name, "modA")) found_a = true;
        if (std.mem.eql(u8, name, "modB")) found_b = true;
    }
    try std.testing.expect(found_a);
    try std.testing.expect(found_b);
}

test "appcore ModuleRegistry load non-existent module" {
    const gpa = std.testing.allocator;
    var registry = appcore.ModuleRegistry.init(gpa);
    defer registry.deinit();

    try std.testing.expectError(error.ModuleNotFound, registry.load("nonexistent"));
}


