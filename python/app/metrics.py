"""Business metrics for monitoring system health and business KPIs."""
from prometheus_client import Counter, Histogram, Summary

# --- Business Metrics ---
# Order-related metrics
orders_created_total = Counter(
    'orders_created_total',
    'Total number of orders created',
    ['order_type', 'status']
)

order_amount_total = Counter(
    'order_amount_total',
    'Total monetary value of orders',
    ['currency']
)

# Payment/Transaction metrics
transactions_processed_total = Counter(
    'transactions_processed_total',
    'Total number of payment transactions processed',
    ['transaction_type', 'status']
)

transaction_amount_total = Counter(
    'transaction_amount_total',
    'Total monetary value of transactions',
    ['currency']
)

# User activity metrics
user_registrations_total = Counter(
    'user_registrations_total',
    'Total number of user registrations'
)

# API business errors
business_errors_total = Counter(
    'business_errors_total',
    'Total number of business logic errors',
    ['error_type', 'module']
)

# Latency for key operations (order creation, payment processing)
order_creation_latency = Histogram(
    'order_creation_latency_seconds',
    'Latency of order creation process',
    ['order_type']
)

payment_processing_latency = Histogram(
    'payment_processing_latency_seconds',
    'Latency of payment processing'
)

# Summary for cart/checkout funnel
checkout_funnel_duration = Summary(
    'checkout_funnel_duration_seconds',
    'Time spent in checkout funnel',
    ['step']
)
