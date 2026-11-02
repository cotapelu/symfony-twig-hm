import uvicorn
from app.main import app as main_app

if __name__ == "__main__":
    port = 8000
    host = "0.0.0.0"
    workers = 4

    print(f"Starting Python Modular Monolith on {host}:{port}")
    uvicorn.run(
        "app.main:app",
        host=host,
        port=port,
        workers=workers,
        log_level="info"
    )
