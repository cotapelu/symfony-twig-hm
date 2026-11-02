use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct AuditLog {
    pub id: String,
    pub user_id: Option<String>,
    pub action: String,
    pub resource_type: String,
    pub resource_id: Option<String>,
    pub ip_address: Option<String>,
    pub user_agent: Option<String>,
    pub status: String,
    pub error_message: Option<String>,
    pub metadata: Option<serde_json::Value>,
    pub created_at: DateTime<Utc>,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SecurityEvent {
    pub id: String,
    pub event_type: String,
    pub severity: String,
    pub description: String,
    pub source: Option<String>,
    pub affected_user_id: Option<String>,
    pub ip_address: Option<String>,
    pub status: String,
    pub resolved_at: Option<DateTime<Utc>>,
    pub metadata: Option<serde_json::Value>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[cfg(test)]
mod tests {
    use super::*;
    use serde_json;

    #[test]
    fn test_audit_log_creation() {
        let log = AuditLog {
            id: "audit_1".to_string(),
            user_id: Some("user1".to_string()),
            action: "login".to_string(),
            resource_type: "session".to_string(),
            resource_id: Some("sess1".to_string()),
            ip_address: Some("127.0.0.1".to_string()),
            user_agent: Some("test-agent".to_string()),
            status: "success".to_string(),
            error_message: None,
            metadata: None,
            created_at: Utc::now(),
        };
        assert_eq!(log.action, "login");
        assert_eq!(log.status, "success");
    }

    #[test]
    fn test_security_event_creation() {
        let event = SecurityEvent {
            id: "sec_1".to_string(),
            event_type: "intrusion_attempt".to_string(),
            severity: "high".to_string(),
            description: "Suspicious activity".to_string(),
            source: Some("firewall".to_string()),
            affected_user_id: Some("user2".to_string()),
            ip_address: Some("10.0.0.1".to_string()),
            status: "open".to_string(),
            resolved_at: None,
            metadata: None,
            created_at: Utc::now(),
            updated_at: Utc::now(),
        };
        assert_eq!(event.severity, "high");
        assert_eq!(event.status, "open");
    }

    #[test]
    fn test_audit_log_serde() {
        let log = AuditLog {
            id: "serde_audit".to_string(),
            user_id: None,
            action: "test".to_string(),
            resource_type: "test_type".to_string(),
            resource_id: None,
            ip_address: None,
            user_agent: None,
            status: "ok".to_string(),
            error_message: None,
            metadata: None,
            created_at: Utc::now(),
        };
        let json = serde_json::to_string(&log).unwrap();
        let decoded: AuditLog = serde_json::from_str(&json).unwrap();
        assert_eq!(log.id, decoded.id);
        assert_eq!(log.action, decoded.action);
    }

    #[test]
    fn test_security_event_serde() {
        let event = SecurityEvent {
            id: "serde_sec".to_string(),
            event_type: "test_event".to_string(),
            severity: "low".to_string(),
            description: "test desc".to_string(),
            source: None,
            affected_user_id: None,
            ip_address: None,
            status: "closed".to_string(),
            resolved_at: Some(Utc::now()),
            metadata: None,
            created_at: Utc::now(),
            updated_at: Utc::now(),
        };
        let json = serde_json::to_string(&event).unwrap();
        let decoded: SecurityEvent = serde_json::from_str(&json).unwrap();
        assert_eq!(event.id, decoded.id);
        assert_eq!(event.event_type, decoded.event_type);
    }
}
