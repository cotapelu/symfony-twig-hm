FROM gradle:9.2.1-jdk21 AS builder
WORKDIR /app
COPY . .
RUN gradle build --no-daemon -x test

FROM eclipse-temurin:21-jre-alpine
WORKDIR /app
COPY --from=builder /app/build/install/*/bin/*.sh .
RUN apk add --no-cache bash
EXPOSE 8080
CMD ["./community-kotlin/bin/community-kotlin"]
