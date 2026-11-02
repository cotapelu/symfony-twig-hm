use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use uuid::Uuid;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SecurityKey {
    pub id: String,
    pub key_name: String,
    pub key_type: String,
    pub key_value: String,
    pub algorithm: String,
    pub key_size: i32,
    pub status: String,
    pub expires_at: Option<DateTime<Utc>>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SecurityCertificate {
    pub id: String,
    pub certificate_name: String,
    pub certificate_type: String,
    pub certificate_data: String,
    pub private_key_id: Option<String>,
    pub issuer: Option<String>,
    pub subject: Option<String>,
    pub valid_from: DateTime<Utc>,
    pub valid_until: DateTime<Utc>,
    pub status: String,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct EncryptionKey {
    pub id: String,
    pub key_name: String,
    pub key_type: String,
    pub key_value: String,
    pub algorithm: String,
    pub key_size: i32,
    pub iv: Option<String>,
    pub status: String,
    pub expires_at: Option<DateTime<Utc>>,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Signature {
    pub id: String,
    pub signature_name: String,
    pub data_hash: String,
    pub signature_value: String,
    pub algorithm: String,
    pub key_id: Option<String>,
    pub signed_at: DateTime<Utc>,
    pub verified: bool,
    pub created_at: DateTime<Utc>,
}

#[cfg(test)]
mod tests {
    use super::*;
    use serde_json;

    #[test]
    fn test_security_key_creation() {
        let key = SecurityKey {
            id: "key1".to_string(),
            key_name: "master".to_string(),
            key_type: "symmetric".to_string(),
            key_value: "secret".to_string(),
            algorithm: "AES".to_string(),
            key_size: 256,
            status: "active".to_string(),
            expires_at: None,
            created_at: Utc::now(),
            updated_at: Utc::now(),
        };
        assert_eq!(key.key_type, "symmetric");
        assert_eq!(key.key_size, 256);
    }

    #[test]
    fn test_security_certificate_creation() {
        let cert = SecurityCertificate {
            id: "cert1".to_string(),
            certificate_name: "root".to_string(),
            certificate_type: "x509".to_string(),
            certificate_data: "data".to_string(),
            private_key_id: Some("key1".to_string()),
            issuer: Some("CA".to_string()),
            subject: Some("CN=test".to_string()),
            valid_from: Utc::now(),
            valid_until: Utc::now(),
            status: "valid".to_string(),
            created_at: Utc::now(),
            updated_at: Utc::now(),
        };
        assert_eq!(cert.certificate_type, "x509");
        assert!(cert.private_key_id.is_some());
    }

    #[test]
    fn test_encryption_key_creation() {
        let enc = EncryptionKey {
            id: "ek1".to_string(),
            key_name: "data_key".to_string(),
            key_type: "AES".to_string(),
            key_value: "keymat".to_string(),
            algorithm: "GCM".to_string(),
            key_size: 128,
            iv: Some("iv123".to_string()),
            status: "active".to_string(),
            expires_at: None,
            created_at: Utc::now(),
            updated_at: Utc::now(),
        };
        assert_eq!(enc.key_type, "AES");
        assert_eq!(enc.key_size, 128);
        assert!(enc.iv.is_some());
    }

    #[test]
    fn test_signature_creation() {
        let sig = Signature {
            id: "sig1".to_string(),
            signature_name: "digsig".to_string(),
            data_hash: "hash".to_string(),
            signature_value: "sigvalue".to_string(),
            algorithm: "RSA-PSS".to_string(),
            key_id: Some("key1".to_string()),
            signed_at: Utc::now(),
            verified: true,
            created_at: Utc::now(),
        };
        assert!(sig.verified);
        assert_eq!(sig.algorithm, "RSA-PSS");
    }

    #[test]
    fn test_security_key_serde() {
        let key = SecurityKey {
            id: "serde_key".to_string(),
            key_name: "test".to_string(),
            key_type: "test_type".to_string(),
            key_value: "value".to_string(),
            algorithm: "ALGO".to_string(),
            key_size: 512,
            status: "inactive".to_string(),
            expires_at: None,
            created_at: Utc::now(),
            updated_at: Utc::now(),
        };
        let json = serde_json::to_string(&key).unwrap();
        let decoded: SecurityKey = serde_json::from_str(&json).unwrap();
        assert_eq!(key.id, decoded.id);
        assert_eq!(key.key_type, decoded.key_type);
    }

    #[test]
    fn test_encryption_key_serde() {
        let key = EncryptionKey {
            id: "serde_ek".to_string(),
            key_name: "test".to_string(),
            key_type: "test_type".to_string(),
            key_value: "val".to_string(),
            algorithm: "ALGO".to_string(),
            key_size: 256,
            iv: Some("iv".to_string()),
            status: "active".to_string(),
            expires_at: None,
            created_at: Utc::now(),
            updated_at: Utc::now(),
        };
        let json = serde_json::to_string(&key).unwrap();
        let decoded: EncryptionKey = serde_json::from_str(&json).unwrap();
        assert_eq!(key.algorithm, decoded.algorithm);
    }

    #[test]
    fn test_signature_serde() {
        let sig = Signature {
            id: "serde_sig".to_string(),
            signature_name: "test_sig".to_string(),
            data_hash: "hash".to_string(),
            signature_value: "value".to_string(),
            algorithm: "ALGO".to_string(),
            key_id: None,
            signed_at: Utc::now(),
            verified: false,
            created_at: Utc::now(),
        };
        let json = serde_json::to_string(&sig).unwrap();
        let decoded: Signature = serde_json::from_str(&json).unwrap();
        assert_eq!(sig.id, decoded.id);
        assert_eq!(sig.verified, decoded.verified);
    }
}

