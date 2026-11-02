# Build stage
FROM rust:1.75-slim AS builder

WORKDIR /app

# Install dependencies
RUN apt-get update && apt-get install -y \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Copy manifests
COPY Cargo.toml ./

# Build dependencies (cache layer)
RUN cargo build --release --bin phuongxa-rust --locked || true

# Copy source and build
COPY src ./src
RUN cargo build --release --bin phuongxa-rust --locked

# Runtime stage
FROM debian:bullseye-slim AS runtime

WORKDIR /app

# Install ca-certificates for HTTPS
RUN apt-get update && apt-get install -y \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Copy binary from builder
COPY --from=builder /app/target/release/phuongxa-rust /usr/local/bin/

# Expose port
EXPOSE 8080

# Run
CMD ["phuongxa-rust"]
