-- Migration: Create PHP Schema (Fixed)
-- Description: Create php schema with ONLY 20 business logic tables (no shared entities)
-- Shared entities moved to common schema

-- Create php schema
CREATE SCHEMA IF NOT EXISTS php;
SET search_path TO php;

-- Government tables
CREATE TABLE IF NOT EXISTS citizen (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    citizen_id VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    birth_date DATE,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_citizen_user_id ON php.citizen(user_id);
CREATE INDEX IF NOT EXISTS idx_citizen_citizen_id ON php.citizen(citizen_id);

CREATE TABLE IF NOT EXISTS household (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    head_citizen_id UUID NOT NULL,
    household_code VARCHAR(100) UNIQUE NOT NULL,
    address TEXT NOT NULL,
    total_members INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_household_head_citizen_id ON php.household(head_citizen_id);
CREATE INDEX IF NOT EXISTS idx_household_code ON php.household(household_code);

CREATE TABLE IF NOT EXISTS gov_officer (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    officer_id VARCHAR(100) UNIQUE NOT NULL,
    department VARCHAR(255) NOT NULL,
    position VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_gov_officer_user_id ON php.gov_officer(user_id);
CREATE INDEX IF NOT EXISTS idx_gov_officer_officer_id ON php.gov_officer(officer_id);

CREATE TABLE IF NOT EXISTS administrative_procedure (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    required_documents JSONB,
    processing_time INTEGER, -- in days
    fee DECIMAL(20,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_administrative_procedure_code ON php.administrative_procedure(code);

CREATE TABLE IF NOT EXISTS procedure_application (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    citizen_id UUID NOT NULL,
    procedure_id UUID NOT NULL REFERENCES php.administrative_procedure(id),
    status VARCHAR(50) DEFAULT 'pending',
    application_data JSONB,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_procedure_application_citizen_id ON php.procedure_application(citizen_id);
CREATE INDEX IF NOT EXISTS idx_procedure_application_procedure_id ON php.procedure_application(procedure_id);
CREATE INDEX IF NOT EXISTS idx_procedure_application_status ON php.procedure_application(status);

CREATE TABLE IF NOT EXISTS procedure_approval (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    application_id UUID NOT NULL REFERENCES php.procedure_application(id) ON DELETE CASCADE,
    officer_id UUID NOT NULL,
    approval_type VARCHAR(50) NOT NULL,
    comments TEXT,
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_procedure_approval_application_id ON php.procedure_approval(application_id);
CREATE INDEX IF NOT EXISTS idx_procedure_approval_officer_id ON php.procedure_approval(officer_id);

CREATE TABLE IF NOT EXISTS citizen_incident (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    citizen_id UUID NOT NULL,
    incident_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    location JSONB,
    status VARCHAR(50) DEFAULT 'reported',
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_citizen_incident_citizen_id ON php.citizen_incident(citizen_id);
CREATE INDEX IF NOT EXISTS idx_citizen_incident_status ON php.citizen_incident(status);
CREATE INDEX IF NOT EXISTS idx_citizen_incident_reported_at ON php.citizen_incident(reported_at DESC);

-- Delivery tables
CREATE TABLE IF NOT EXISTS delivery_store (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    owner_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_delivery_store_owner_id ON php.delivery_store(owner_id);
CREATE INDEX IF NOT EXISTS idx_delivery_store_active ON php.delivery_store(is_active);

CREATE TABLE IF NOT EXISTS delivery_product (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    store_id UUID NOT NULL REFERENCES php.delivery_store(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(20,2) NOT NULL,
    stock_quantity INTEGER DEFAULT 0,
    is_available BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_delivery_product_store_id ON php.delivery_product(store_id);
CREATE INDEX IF NOT EXISTS idx_delivery_product_available ON php.delivery_product(is_available);

CREATE TABLE IF NOT EXISTS delivery_order (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_number VARCHAR(100) UNIQUE NOT NULL,
    customer_id UUID NOT NULL,
    store_id UUID NOT NULL REFERENCES php.delivery_store(id),
    total_amount DECIMAL(20,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    delivery_address TEXT NOT NULL,
    delivery_phone VARCHAR(20),
    notes TEXT,
    ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_delivery_order_customer_id ON php.delivery_order(customer_id);
CREATE INDEX IF NOT EXISTS idx_delivery_order_store_id ON php.delivery_order(store_id);
CREATE INDEX IF NOT EXISTS idx_delivery_order_status ON php.delivery_order(status);
CREATE INDEX IF NOT EXISTS idx_delivery_order_ordered_at ON php.delivery_order(ordered_at DESC);

CREATE TABLE IF NOT EXISTS delivery_order_item (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID NOT NULL REFERENCES php.delivery_order(id) ON DELETE CASCADE,
    product_id UUID NOT NULL REFERENCES php.delivery_product(id),
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(20,2) NOT NULL,
    total_price DECIMAL(20,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_delivery_order_item_order_id ON php.delivery_order_item(order_id);
CREATE INDEX IF NOT EXISTS idx_delivery_order_item_product_id ON php.delivery_order_item(product_id);

CREATE TABLE IF NOT EXISTS delivery_person (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    vehicle_type VARCHAR(100) NOT NULL,
    vehicle_plate VARCHAR(50),
    is_available BOOLEAN DEFAULT true,
    current_lat DECIMAL(10,8),
    current_lon DECIMAL(11,8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_delivery_person_user_id ON php.delivery_person(user_id);
CREATE INDEX IF NOT EXISTS idx_delivery_person_available ON php.delivery_person(is_available);

CREATE TABLE IF NOT EXISTS delivery_vehicle (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    delivery_person_id UUID NOT NULL REFERENCES php.delivery_person(id) ON DELETE CASCADE,
    vehicle_type VARCHAR(100) NOT NULL,
    plate_number VARCHAR(50) UNIQUE NOT NULL,
    model VARCHAR(255),
    year INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_delivery_vehicle_delivery_person_id ON php.delivery_vehicle(delivery_person_id);
CREATE INDEX IF NOT EXISTS idx_delivery_vehicle_plate ON php.delivery_vehicle(plate_number);

-- Ecommerce tables
CREATE TABLE IF NOT EXISTS ecommerce_product (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    sku VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id UUID,
    price DECIMAL(20,2) NOT NULL,
    compare_price DECIMAL(20,2),
    cost_price DECIMAL(20,2),
    stock_quantity INTEGER DEFAULT 0,
    track_inventory BOOLEAN DEFAULT true,
    is_active BOOLEAN DEFAULT true,
    weight DECIMAL(10,2),
    dimensions JSONB,
    images JSONB,
    attributes JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_ecommerce_product_sku ON php.ecommerce_product(sku);
CREATE INDEX IF NOT EXISTS idx_ecommerce_product_category ON php.ecommerce_product(category_id);
CREATE INDEX IF NOT EXISTS idx_ecommerce_product_active ON php.ecommerce_product(is_active);

CREATE TABLE IF NOT EXISTS ecommerce_order (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_number VARCHAR(100) UNIQUE NOT NULL,
    customer_id UUID NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    payment_status VARCHAR(50) DEFAULT 'unpaid',
    fulfillment_status VARCHAR(50) DEFAULT 'unfulfilled',
    subtotal DECIMAL(20,2) NOT NULL,
    tax_amount DECIMAL(20,2) DEFAULT 0,
    shipping_amount DECIMAL(20,2) DEFAULT 0,
    total_amount DECIMAL(20,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    shipping_address JSONB NOT NULL,
    billing_address JSONB,
    notes TEXT,
    ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP,
    shipped_at TIMESTAMP,
    delivered_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_ecommerce_order_customer_id ON php.ecommerce_order(customer_id);
CREATE INDEX IF NOT EXISTS idx_ecommerce_order_status ON php.ecommerce_order(status);
CREATE INDEX IF NOT EXISTS idx_ecommerce_order_ordered_at ON php.ecommerce_order(ordered_at DESC);
CREATE INDEX IF NOT EXISTS idx_ecommerce_order_number ON php.ecommerce_order(order_number);

CREATE TABLE IF NOT EXISTS ecommerce_transaction (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID NOT NULL REFERENCES php.ecommerce_order(id),
    transaction_id VARCHAR(255) UNIQUE NOT NULL,
    payment_method VARCHAR(100) NOT NULL,
    amount DECIMAL(20,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    status VARCHAR(50) DEFAULT 'pending',
    gateway_response JSONB,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_ecommerce_transaction_order_id ON php.ecommerce_transaction(order_id);
CREATE INDEX IF NOT EXISTS idx_ecommerce_transaction_transaction_id ON php.ecommerce_transaction(transaction_id);
CREATE INDEX IF NOT EXISTS idx_ecommerce_transaction_status ON php.ecommerce_transaction(status);

-- Business tables
CREATE TABLE IF NOT EXISTS business (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    owner_id UUID NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    category_id UUID,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(500),
    logo_url VARCHAR(500),
    is_verified BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    settings JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_business_owner_id ON php.business(owner_id);
CREATE INDEX IF NOT EXISTS idx_business_slug ON php.business(slug);
CREATE INDEX IF NOT EXISTS idx_business_category ON php.business(category_id);
CREATE INDEX IF NOT EXISTS idx_business_active ON php.business(is_active);

CREATE TABLE IF NOT EXISTS business_catalog (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    business_id UUID NOT NULL REFERENCES php.business(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(20,2),
    duration INTEGER, -- in minutes
    is_available BOOLEAN DEFAULT true,
    metadata JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_business_catalog_business_id ON php.business_catalog(business_id);
CREATE INDEX IF NOT EXISTS idx_business_catalog_available ON php.business_catalog(is_available);

CREATE TABLE IF NOT EXISTS service_booking (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    booking_number VARCHAR(100) UNIQUE NOT NULL,
    customer_id UUID NOT NULL,
    business_id UUID NOT NULL REFERENCES php.business(id),
    service_id UUID NOT NULL REFERENCES php.business_catalog(id),
    staff_id UUID,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    status VARCHAR(50) DEFAULT 'confirmed',
    notes TEXT,
    special_requests TEXT,
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,
    cancelled_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_service_booking_customer_id ON php.service_booking(customer_id);
CREATE INDEX IF NOT EXISTS idx_service_booking_business_id ON php.service_booking(business_id);
CREATE INDEX IF NOT EXISTS idx_service_booking_status ON php.service_booking(status);
CREATE INDEX IF NOT EXISTS idx_service_booking_scheduled_date ON php.service_booking(scheduled_date);
CREATE INDEX IF NOT EXISTS idx_service_booking_booking_number ON php.service_booking(booking_number);

CREATE TABLE IF NOT EXISTS service_review (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    booking_id UUID NOT NULL UNIQUE REFERENCES php.service_booking(id),
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_verified BOOLEAN DEFAULT true,
    helpful_votes INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_service_review_booking_id ON php.service_review(booking_id);
CREATE INDEX IF NOT EXISTS idx_service_review_rating ON php.service_review(rating);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION php.update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Apply triggers to all tables
CREATE TRIGGER update_citizen_updated_at BEFORE UPDATE ON citizen
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_household_updated_at BEFORE UPDATE ON household
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_gov_officer_updated_at BEFORE UPDATE ON gov_officer
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_administrative_procedure_updated_at BEFORE UPDATE ON administrative_procedure
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_procedure_application_updated_at BEFORE UPDATE ON procedure_application
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_procedure_approval_updated_at BEFORE UPDATE ON procedure_approval
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_citizen_incident_updated_at BEFORE UPDATE ON citizen_incident
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_delivery_store_updated_at BEFORE UPDATE ON delivery_store
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_delivery_product_updated_at BEFORE UPDATE ON delivery_product
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_delivery_order_updated_at BEFORE UPDATE ON delivery_order
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_delivery_order_item_updated_at BEFORE UPDATE ON delivery_order_item
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_delivery_person_updated_at BEFORE UPDATE ON delivery_person
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_delivery_vehicle_updated_at BEFORE UPDATE ON delivery_vehicle
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_ecommerce_product_updated_at BEFORE UPDATE ON ecommerce_product
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_ecommerce_order_updated_at BEFORE UPDATE ON ecommerce_order
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_ecommerce_transaction_updated_at BEFORE UPDATE ON ecommerce_transaction
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_business_updated_at BEFORE UPDATE ON business
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_business_catalog_updated_at BEFORE UPDATE ON business_catalog
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_service_booking_updated_at BEFORE UPDATE ON service_booking
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();

CREATE TRIGGER update_service_review_updated_at BEFORE UPDATE ON service_review
    FOR EACH ROW EXECUTE FUNCTION php.update_updated_at_column();
