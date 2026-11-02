plugins {
    kotlin("jvm")
    `java-library`
}

group = "com.qcoder.kotlin"
version = "1.0.0"

repositories {
    mavenCentral()
}

dependencies {
    // Ktor server core (Application, Call, Pipeline)
    implementation("io.ktor:ktor-server-core:3.0.0")

    // Testing
    testImplementation(kotlin("test"))
    testImplementation("org.junit.jupiter:junit-jupiter:5.11.0")
}

kotlin {
    jvmToolchain(21)
}

java {
    sourceCompatibility = JavaVersion.VERSION_21
    targetCompatibility = JavaVersion.VERSION_21
}
