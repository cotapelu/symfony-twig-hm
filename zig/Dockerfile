FROM zig:0.16.0-musl AS builder
WORKDIR /app
COPY . .
RUN zig build -Drelease --fetch

FROM alpine:latest
RUN apk --no-cache add ca-certificates
COPY --from=builder /app/zig-out/bin/zig-app /usr/local/bin/zig-app
EXPOSE 3000
CMD ["zig-app"]
