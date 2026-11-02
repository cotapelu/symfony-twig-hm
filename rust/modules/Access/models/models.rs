use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct AccessControl {
    pub id: String,
    pub resource_type: String,
    pub resource_id: String,
    pub access_level: String,
    pub conditions: Option<serde_json::Value>,
    pub status: String,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Permission {
    pub id: String,
    pub user_id: String,
    pub resource_type: String,
    pub resource_id: Option<String>,
    pub permission_type: String,
    pub granted_by: Option<String>,
    pub granted_at: DateTime<Utc>,
    pub expires_at: Option<DateTime<Utc>>,
    pub status: String,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[cfg(test)]
mod tests {
    use super::*;
    use serde_json;

    #[test]
    fn test_access_control_creation() {
        let ac = AccessControl {
            id: "test_id".to_string(),
            resource_type: "test_resource".to_string(),
            resource_id: "test_res_id".to_string(),
            access_level: "read".to_string(),
            conditions: None,
            status: "active".to_string(),
            created_at: Utc::now(),
            updated_at: Utc::now(),
        };
        assert_eq!(ac.id, "test_id");
        assert_eq!(ac.resource_type, "test_resource");
        assert_eq!(ac.status, "active");
    }

    #[test]
    fn test_permission_creation() {
        let p = Permission {
            id: "perm_id".to_string(),
            user_id: "user123".to_string(),
            resource_type: "resource_type".to_string(),
            resource_id: Some("res123".to_string()),
            permission_type: "read".to_string(),
            granted_by: Some("admin".to_string()),
            granted_at: Utc::now(),
            expires_at: None,
            status: "granted".to_string(),
            created_at: Utc::now(),
            updated_at: Utc::now(),
        };
        assert_eq!(p.user_id, "user123");
        assert_eq!(p.permission_type, "read");
        assert_eq!(p.status, "granted");
    }

    #[test]
    fn test_access_control_serde() {
        let ac = AccessControl {
            id: "serde_test".to_string(),
            resource_type: "type".to_string(),
            resource_id: "res".to_string(),
            access_level: "level".to_string(),
            conditions: None,
            status: "active".to_string(),
            created_at: Utc::now(),
            updated_at: Utc::now(),
        };
        let json = serde_json::to_string(&ac).expect("serialization failed");
        let decoded: AccessControl = serde_json::from_str(&json).expect("deserialization failed");
        assert_eq!(ac.id, decoded.id);
        assert_eq!(ac.resource_type, decoded.resource_type);
    }

    #[test]
    fn test_permission_serde() {
        let p = Permission {
            id: "serde_perm".to_string(),
            user_id: "user456".to_string(),
            resource_type: "type2".to_string(),
            resource_id: Some("res456".to_string()),
            permission_type: "write".to_string(),
            granted_by: Some("system".to_string(),),
            granted_at: Utc::now(),
            expires_at: None,
            status: "active".to_string(),
            created_at: Utc::now(),
            updated_at: Utc::now(),
        };
        let json = serde_json::to_string(&p).expect("serialization failed");
        let decoded: Permission = serde_json::from_str(&json).expect("deserialization failed");
        assert_eq!(p.id, decoded.id);
        assert_eq!(p.permission_type, decoded.permission_type);
    }
}
