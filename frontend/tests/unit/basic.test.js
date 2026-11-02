import { describe, it, expect } from "vitest";

describe("Basic Math", () => {
  it("adds numbers correctly", () => {
    expect(2 + 2).toBe(4);
  });

  it("multiplies numbers correctly", () => {
    expect(3 * 3).toBe(9);
  });
});

describe("String utilities", () => {
  it("reverses strings", () => {
    const reversed = "hello".split("").reverse().join("");
    expect(reversed).toBe("olleh");
  });
});
