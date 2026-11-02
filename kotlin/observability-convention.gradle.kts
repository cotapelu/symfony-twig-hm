// Observability Convention Plugin for Kotlin Modules
// Automatically applies observability infrastructure to all backend modules

plugins {
    // Kotlin plugin already applied
}

// Add observability dependencies to all modules (except excluded)
dependencies {
    implementation(project(":observability"))
}

// Configure logging for all modules
configurations {
    all {
        exclude(group = "org.slf4j", module = "slf4j-api") // Use logback instead
    }
}

// Ensure modules use the shared observability configuration
tasks.withType<Test> {
    systemProperty("logback.configurationFile", "../src/main/resources/logback-observability.xml")
}

// Print reminder during build
afterEvaluate {
    println("Module ${project.name}: Observability infrastructure enabled")
}
