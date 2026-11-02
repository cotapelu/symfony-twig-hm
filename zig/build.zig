const std = @import("std");

pub fn build(b: *std.Build) void {
    const target = b.standardTargetOptions(.{});
    const optimize = b.standardOptimizeOption(.{});

    // Main executable
    const exe = b.addExecutable(.{
        .name = "zig-app",
        .root_module = b.createModule(.{
            .root_source_file = b.path("main.zig"),
            .target = target,
            .optimize = optimize,
        }),
    });

    // Add modules
    const appcore_module = b.addModule("appcore", .{
        .root_source_file = b.path("modules/appcore.zig"),
    });
    exe.root_module.addImport("appcore", appcore_module);

    // Install
    b.installArtifact(exe);

    // Run command
    const run_cmd = b.addRunArtifact(exe);
    run_cmd.step.dependOn(b.getInstallStep());
    if (b.args) |args| {
        run_cmd.addArgs(args);
    }
    const run_step = b.step("run", "Run the app");
    run_step.dependOn(&run_cmd.step);

    // Run tests
    const run_tests = b.step("test", "Run all tests");
    const test_exe = b.addTest(.{
        .root_module = b.createModule(.{
            .root_source_file = b.path("main_test.zig"),
            .target = target,
            .optimize = optimize,
        }),
    });
    test_exe.root_module.addImport("appcore", appcore_module);

    // Enable sanitizer coverage instrumentation when -Dcoverage is passed
    const enable_coverage = b.option(bool, "coverage", "Enable coverage") orelse false;
    if (enable_coverage) {
        test_exe.sanitize_coverage_trace_pc_guard = true;
    }

    const run_test_cmd = b.addRunArtifact(test_exe);
    // Set LLVM_PROFILE_FILE env var for profraw generation
    if (enable_coverage) {
        run_test_cmd.setEnvironmentVariable("LLVM_PROFILE_FILE", "coverage-%m.profraw");
    }
    run_tests.dependOn(&run_test_cmd.step);
}
