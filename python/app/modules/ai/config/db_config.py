"""Configuration for PostgreSQL database connection"""
import os

class DBConfig:
    POSTGRES_HOST = os.getenv('POSTGRES_HOST', 'localhost')
    POSTGRES_PORT = int(os.getenv('POSTGRES_PORT', '5432'))
    POSTGRES_DB = os.getenv('POSTGRES_DB', 'qcoder_ai')
    POSTGRES_USER = os.getenv('POSTGRES_USER', 'qcoder')
    POSTGRES_PASSWORD = os.getenv('POSTGRES_PASSWORD')

    @staticmethod
    def get_database_url() -> str:
        if not DBConfig.POSTGRES_PASSWORD:
            raise ValueError("POSTGRES_PASSWORD environment variable is required")
        return f"postgresql://{DBConfig.POSTGRES_USER}:{DBConfig.POSTGRES_PASSWORD}@{DBConfig.POSTGRES_HOST}:{DBConfig.POSTGRES_PORT}/{DBConfig.POSTGRES_DB}"
