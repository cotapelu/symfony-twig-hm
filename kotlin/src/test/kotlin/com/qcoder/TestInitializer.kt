package com.qcoder

import org.junit.jupiter.api.extension.BeforeAllCallback
import org.junit.jupiter.api.extension.ExtensionContext

/**
 * JUnit 5 extension that sets up test environment before all tests.
 * Automatically registered via junit-platform.properties.
 */
class TestInitializer : BeforeAllCallback {
    override fun beforeAll(context: ExtensionContext) {
        // Set system properties for test environment
        System.setProperty("APP_ENV", "test")
        // Default JWT secret for tests (can be overridden by CI)
        System.setProperty("JWT_SECRET_KEY", System.getProperty("jwt.secret", "test-secret-key"))
        // Add additional setup as needed (e.g., start test containers)
    }
}
