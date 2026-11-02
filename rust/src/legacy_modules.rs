// Legacy modules from the original modules/ directory
// These are included via path to avoid moving files
// Only data models are included to keep dependencies minimal

#[path = "../modules/Access/models/models.rs"]
mod access_models;

#[path = "../modules/Audit/models/models.rs"]
mod audit_models;

#[path = "../modules/Security/models/models.rs"]
mod security_models;
