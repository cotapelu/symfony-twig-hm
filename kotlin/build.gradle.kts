plugins {
    kotlin("jvm") version "2.0.0"
    kotlin("plugin.serialization") version "2.0.0"
    jacoco
    application
}

group = "com.qcoder.kotlin"
version = "1.0.0"

repositories {
    mavenCentral()
    maven { url = uri("https://maven.pkg.jetbrains.space/public/p/kotlinx-html/maven") }
}

dependencies {
    // Kotlin stdlib
    implementation("org.jetbrains.kotlin:kotlin-stdlib:2.0.0")

    // Coroutines
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-core:1.8.0")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-jdk8:1.8.0")

    // Serialization
    implementation("org.jetbrains.kotlinx:kotlinx-serialization-json:1.6.3")

    // HTTP Server (Ktor)
    implementation("io.ktor:ktor-server-core:3.0.0")
    implementation("io.ktor:ktor-server-netty:3.0.0")
    implementation("io.ktor:ktor-server-content-negotiation:3.0.0")
    implementation("io.ktor:ktor-server-auth:3.0.0")
    implementation("io.ktor:ktor-server-auth-jwt:3.0.0")
    implementation("io.ktor:ktor-serialization-kotlinx-json:3.0.0")

    // HTTP Client (Ktor)
    implementation("io.ktor:ktor-client-core:3.0.0")
    implementation("io.ktor:ktor-client-cio:3.0.0")
    implementation("io.ktor:ktor-client-content-negotiation:3.0.0")
    implementation("io.ktor:ktor-serialization-kotlinx-json:3.0.0")

    // Logging
    implementation("ch.qos.logback:logback-classic:1.5.3")
    implementation("net.logstash.logback:logstash-logback-encoder:7.4")

    // Database
    implementation("org.jetbrains.exposed:exposed-core:0.50.1")
    implementation("org.jetbrains.exposed:exposed-dao:0.50.1")
    implementation("org.jetbrains.exposed:exposed-jdbc:0.50.1")
    implementation("org.jetbrains.exposed:exposed-java-time:0.50.1")
    implementation("org.postgresql:postgresql:42.7.3")

    // JWT
    implementation("com.auth0:java-jwt:4.4.0")

    // Testing
    testImplementation(kotlin("test"))
    testImplementation("org.jetbrains.kotlin:kotlin-test-junit5:2.0.0")
    testImplementation("org.junit.jupiter:junit-jupiter:5.11.0")
    testImplementation("org.junit.vintage:junit-vintage-engine:5.11.0")
    testImplementation("org.xerial:sqlite-jdbc:3.45.3.0")
    testImplementation("com.tngtech.archunit:archunit-junit5:1.2.2")
    testImplementation("io.ktor:ktor-server-test-host:3.0.0")
}


sourceSets {
    test {
        kotlin {
            // Exclude broken/legacy tests that reference missing modules
            exclude("**/carrental/**")
            exclude("**/restaurantbooking/**")
            exclude("**/samplemodule/**")
            exclude("**/travelpackages/**")
            exclude("**/peertopeer/**")
            exclude("**/eventsbooking/**")
            exclude("**/com/monolith/**")
            exclude("**/com/qcoder/**")
            exclude("**/experiences/**")
            exclude("**/flighttickets/**")
            exclude("**/hotelbooking/**")
            exclude("**/utils/**")
            exclude("**/com/example/businesslisting/**")
            exclude("**/com/example/parking/**")
        }
    }
}

application {
    mainClass.set("app.ApplicationKt")
}

tasks.test {
    useJUnitPlatform()
    finalizedBy(tasks.jacocoTestReport)
}

kotlin {
    jvmToolchain(21)
    compilerOptions {
        jvmTarget.set(org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_21)
    }
}

sourceSets {
    main {
        kotlin.srcDirs("src/main/kotlin", "modules/Alert", "modules/Queue", "modules/Metric", "modules/SampleModule", "modules/emailservice", "modules/identitymanagement")
    }
    test {
        kotlin.srcDirs("src/test/kotlin")
    }
}

jacoco {
    toolVersion = "0.8.11"
    reportsDirectory.set(layout.buildDirectory.dir("reports/jacoco"))
}

tasks.jacocoTestReport {
    dependsOn(tasks.test)
    reports {
        xml.required.set(true)
        html.required.set(true)
        csv.required.set(false)
        xml.outputLocation.set(layout.buildDirectory.file("reports/jacoco/jacoco.xml"))
        html.outputLocation.set(layout.buildDirectory.dir("reports/jacoco/html"))
    }
    // Include all classes
    classDirectories.setFrom(
        fileTree(layout.buildDirectory.dir("classes/kotlin/main")) {
            include("**/*.class")
            exclude("**/META-INF/**")
            exclude("**/module-info.class")
        }
    )
    sourceDirectories.setFrom(
        fileTree("src/main/kotlin") {
            include("**/*.kt")
        }
    )
}
