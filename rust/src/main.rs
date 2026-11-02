use axum::{routing::{get, post}, Router};
use tower::ServiceBuilder;
use std::net::SocketAddr;
use tower_http::cors::CorsLayer;
use tracing::info;
use tokio::net::TcpListener;

mod health;
mod logging;
mod metrics;
mod middleware;
mod utils;
mod banking;
mod legacy_modules;

#[cfg(test)]
mod utils_test;
#[cfg(test)]
mod logging_test;
#[cfg(test)]
mod metrics_test;
#[cfg(test)]
mod middleware_integration_test;

use health::{live, ready};
use metrics::metrics_handler;
use middleware::metrics_middleware;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    // Initialize logging
    logging::init();

    // Build routes
    let app = Router::new()
        .route("/health/live", get(live))
        .route("/health/ready", get(ready))
        .route("/metrics", get(metrics_handler))
        .layer(CorsLayer::permissive())
        .layer(tower::ServiceBuilder::new().layer(axum::middleware::from_fn(metrics_middleware)));

    // Run server
    let addr = SocketAddr::from(([127, 0, 0, 1], 8080));
    info!("Rust backend listening on {}", addr);
    let listener = TcpListener::bind(addr).await?;
    axum::serve(listener, app).await?;
    Ok(())
}
