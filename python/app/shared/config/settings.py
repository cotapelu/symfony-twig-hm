import os
from typing import List, Optional
from dataclasses import dataclass, field


@dataclass
class ServerSettings:
    host: str = "127.0.0.1"
    port: int = 8000
    workers: int = 4


@dataclass
class RedisSettings:
    host: str = "localhost"
    port: int = 6379
    db: int = 0
    password: str = ""


@dataclass
class OpenAISettings:
    api_key: str = ""
    model: str = "gpt-3.5-turbo"
    max_tokens: int = 1000


@dataclass
class MediaSettings:
    upload_dir: str = "./uploads"
    max_file_size: int = 10485760
    allowed_formats: List[str] = field(default_factory=lambda: ["jpg", "jpeg", "png", "gif", "webp", "mp4", "mov"])


@dataclass
class EventBusSettings:
    enabled: bool = True
    backend: str = "redis"


@dataclass
class LogSettings:
    level: str = "INFO"
    format: str = "json"


@dataclass
class DatabaseSettings:
    host: str = "localhost"
    port: int = 5432
    name: str = "qcoder_community"
    user: str = "qcoder"
    password: str = ""
    pool_size: int = 20
    max_overflow: int = 10
    pool_recycle: int = 3600


@dataclass
class CORSSettings:
    allowed_origins: List[str] = field(default_factory=list)
    allow_credentials: bool = True
    allowed_methods: List[str] = field(default_factory=lambda: ["GET", "POST", "PUT", "DELETE", "OPTIONS"])
    allowed_headers: List[str] = field(default_factory=lambda: ["Content-Type", "Authorization", "X-Requested-With"])


@dataclass
class JWTSettings:
    secret_key: str = ""
    algorithm: str = "HS256"
    access_token_expire_minutes: int = 30
    refresh_token_expire_days: int = 7


@dataclass
class Settings:
    app_name: str = "community-python-services"
    debug: bool = True
    server: Optional[ServerSettings] = None
    redis: Optional[RedisSettings] = None
    openai: Optional[OpenAISettings] = None
    media: Optional[MediaSettings] = None
    eventbus: Optional[EventBusSettings] = None
    log: Optional[LogSettings] = None
    database: Optional[DatabaseSettings] = None
    cors: Optional[CORSSettings] = None
    jwt: Optional[JWTSettings] = None

    def __post_init__(self):
        if self.server is None:
            self.server = ServerSettings(
                host=os.getenv("SERVER_HOST", "127.0.0.1"),
                port=int(os.getenv("SERVER_PORT", "8000")),
                workers=int(os.getenv("SERVER_WORKERS", "4"))
            )
        if self.redis is None:
            self.redis = RedisSettings(
                host=os.getenv("REDIS_HOST", "localhost"),
                port=int(os.getenv("REDIS_PORT", "6379")),
                db=int(os.getenv("REDIS_DB", "0")),
                password=os.getenv("REDIS_PASSWORD", "")
            )
        if self.openai is None:
            self.openai = OpenAISettings(
                api_key=os.getenv("OPENAI_API_KEY", ""),
                model=os.getenv("OPENAI_MODEL", "gpt-3.5-turbo"),
                max_tokens=int(os.getenv("OPENAI_MAX_TOKENS", "1000"))
            )
        if self.media is None:
            self.media = MediaSettings(
                upload_dir=os.getenv("MEDIA_UPLOAD_DIR", "./uploads"),
                max_file_size=int(os.getenv("MEDIA_MAX_FILE_SIZE", "10485760"))
            )
        if self.eventbus is None:
            self.eventbus = EventBusSettings(
                enabled=os.getenv("EVENTBUS_ENABLED", "true").lower() == "true",
                backend=os.getenv("EVENTBUS_BACKEND", "redis")
            )
        if self.log is None:
            self.log = LogSettings(
                level=os.getenv("LOG_LEVEL", "INFO"),
                format=os.getenv("LOG_FORMAT", "json")
            )
        if self.cors is None:
            self.cors = CORSSettings(
                allowed_origins=os.getenv("CORS_ALLOWED_ORIGINS", "").split(",") if os.getenv("CORS_ALLOWED_ORIGINS") else [],
                allow_credentials=os.getenv("CORS_ALLOW_CREDENTIALS", "true").lower() == "true"
            )
        if self.database is None:
            self.database = DatabaseSettings(
                host=os.getenv("DB_HOST", os.getenv("POSTGRES_HOST", "localhost")),
                port=int(os.getenv("DB_PORT", os.getenv("POSTGRES_PORT", "5432"))),
                name=os.getenv("DB_NAME", os.getenv("POSTGRES_DB", "qcoder_community")),
                user=os.getenv("DB_USER", os.getenv("POSTGRES_USER", "qcoder")),
                password=os.getenv("DB_PASSWORD", os.getenv("POSTGRES_PASSWORD", "")),
                pool_size=int(os.getenv("DB_POOL_SIZE", "20")),
                max_overflow=int(os.getenv("DB_MAX_OVERFLOW", "10")),
                pool_recycle=int(os.getenv("DB_POOL_RECYCLE", "3600"))
            )
        if self.jwt is None:
            secret_key = os.getenv("JWT_SECRET_KEY")
            if not secret_key and not self.debug:
                raise ValueError("JWT_SECRET_KEY environment variable is required in production")
            # In debug mode, use a default secret (DO NOT use in production!)
            self.jwt = JWTSettings(
                secret_key=secret_key or ("debug-secret-key" if self.debug else ""),
                algorithm=os.getenv("JWT_ALGORITHM", "HS256"),
                access_token_expire_minutes=int(os.getenv("JWT_ACCESS_TOKEN_EXPIRE_MINUTES", "30")),
                refresh_token_expire_days=int(os.getenv("JWT_REFRESH_TOKEN_EXPIRE_DAYS", "7"))
            )


settings = Settings()
