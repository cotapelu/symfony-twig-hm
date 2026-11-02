use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use sqlx::PgPool;
use uuid::Uuid;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SecurityKey {
    pub id: Uuid,
    pub user_id: String,
    pub key_type: String,
    pub key_value: String,
    pub expires_at: Option<DateTime<Utc>>,
    pub is_active: bool,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

pub struct SecurityKeyRepository {
    pool: PgPool,
}

impl SecurityKeyRepository {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }

    pub async fn create(&self, key: &SecurityKey) -> Result<SecurityKey, sqlx::Error> {
        sqlx::query_as::<_, SecurityKey>(
            r#"
            INSERT INTO security_keys (id, user_id, key_type, key_value, expires_at, is_active, created_at, updated_at)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
            RETURNING *
            "#
        )
        .bind(key.id)
        .bind(&key.user_id)
        .bind(&key.key_type)
        .bind(&key.key_value)
        .bind(key.expires_at)
        .bind(key.is_active)
        .bind(key.created_at)
        .bind(key.updated_at)
        .fetch_one(&self.pool)
        .await
    }

    pub async fn find_by_id(&self, id: Uuid) -> Result<Option<SecurityKey>, sqlx::Error> {
        sqlx::query_as::<_, SecurityKey>(
            r#"
            SELECT * FROM security_keys WHERE id = $1
            "#
        )
        .bind(id)
        .fetch_optional(&self.pool)
        .await
    }

    pub async fn find_by_user_id(&self, user_id: &str) -> Result<Vec<SecurityKey>, sqlx::Error> {
        sqlx::query_as::<_, SecurityKey>(
            r#"
            SELECT * FROM security_keys WHERE user_id = $1 ORDER BY created_at DESC
            "#
        )
        .bind(user_id)
        .fetch_all(&self.pool)
        .await
    }

    pub async fn find_active_by_user_id(&self, user_id: &str) -> Result<Vec<SecurityKey>, sqlx::Error> {
        sqlx::query_as::<_, SecurityKey>(
            r#"
            SELECT * FROM security_keys WHERE user_id = $1 AND is_active = true ORDER BY created_at DESC
            "#
        )
        .bind(user_id)
        .fetch_all(&self.pool)
        .await
    }

    pub async fn update_status(&self, id: Uuid, is_active: bool) -> Result<(), sqlx::Error> {
        sqlx::query(
            r#"
            UPDATE security_keys SET is_active = $1, updated_at = NOW() WHERE id = $2
            "#
        )
        .bind(is_active)
        .bind(id)
        .execute(&self.pool)
        .await?;
        Ok(())
    }

    pub async fn delete(&self, id: Uuid) -> Result<(), sqlx::Error> {
        sqlx::query(
            r#"
            DELETE FROM security_keys WHERE id = $1
            "#
        )
        .bind(id)
        .execute(&self.pool)
        .await?;
        Ok(())
    }
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SecurityCertificate {
    pub id: Uuid,
    pub user_id: String,
    pub certificate_type: String,
    pub certificate_data: String,
    pub expires_at: Option<DateTime<Utc>>,
    pub is_active: bool,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

pub struct SecurityCertificateRepository {
    pool: PgPool,
}

impl SecurityCertificateRepository {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }

    pub async fn create(&self, certificate: &SecurityCertificate) -> Result<SecurityCertificate, sqlx::Error> {
        sqlx::query_as::<_, SecurityCertificate>(
            r#"
            INSERT INTO security_certificates (id, user_id, certificate_type, certificate_data, expires_at, is_active, created_at, updated_at)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
            RETURNING *
            "#
        )
        .bind(certificate.id)
        .bind(&certificate.user_id)
        .bind(&certificate.certificate_type)
        .bind(&certificate.certificate_data)
        .bind(certificate.expires_at)
        .bind(certificate.is_active)
        .bind(certificate.created_at)
        .bind(certificate.updated_at)
        .fetch_one(&self.pool)
        .await
    }

    pub async fn find_by_id(&self, id: Uuid) -> Result<Option<SecurityCertificate>, sqlx::Error> {
        sqlx::query_as::<_, SecurityCertificate>(
            r#"
            SELECT * FROM security_certificates WHERE id = $1
            "#
        )
        .bind(id)
        .fetch_optional(&self.pool)
        .await
    }

    pub async fn find_by_user_id(&self, user_id: &str) -> Result<Vec<SecurityCertificate>, sqlx::Error> {
        sqlx::query_as::<_, SecurityCertificate>(
            r#"
            SELECT * FROM security_certificates WHERE user_id = $1 ORDER BY created_at DESC
            "#
        )
        .bind(user_id)
        .fetch_all(&self.pool)
        .await
    }

    pub async fn update_status(&self, id: Uuid, is_active: bool) -> Result<(), sqlx::Error> {
        sqlx::query(
            r#"
            UPDATE security_certificates SET is_active = $1, updated_at = NOW() WHERE id = $2
            "#
        )
        .bind(is_active)
        .bind(id)
        .execute(&self.pool)
        .await?;
        Ok(())
    }

    pub async fn delete(&self, id: Uuid) -> Result<(), sqlx::Error> {
        sqlx::query(
            r#"
            DELETE FROM security_certificates WHERE id = $1
            "#
        )
        .bind(id)
        .execute(&self.pool)
        .await?;
        Ok(())
    }
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct EncryptionKey {
    pub id: Uuid,
    pub user_id: String,
    pub key_type: String,
    pub key_value: String,
    pub algorithm: String,
    pub expires_at: Option<DateTime<Utc>>,
    pub is_active: bool,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

pub struct EncryptionKeyRepository {
    pool: PgPool,
}

impl EncryptionKeyRepository {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }

    pub async fn create(&self, key: &EncryptionKey) -> Result<EncryptionKey, sqlx::Error> {
        sqlx::query_as::<_, EncryptionKey>(
            r#"
            INSERT INTO encryption_keys (id, user_id, key_type, key_value, algorithm, expires_at, is_active, created_at, updated_at)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
            RETURNING *
            "#
        )
        .bind(key.id)
        .bind(&key.user_id)
        .bind(&key.key_type)
        .bind(&key.key_value)
        .bind(&key.algorithm)
        .bind(key.expires_at)
        .bind(key.is_active)
        .bind(key.created_at)
        .bind(key.updated_at)
        .fetch_one(&self.pool)
        .await
    }

    pub async fn find_by_id(&self, id: Uuid) -> Result<Option<EncryptionKey>, sqlx::Error> {
        sqlx::query_as::<_, EncryptionKey>(
            r#"
            SELECT * FROM encryption_keys WHERE id = $1
            "#
        )
        .bind(id)
        .fetch_optional(&self.pool)
        .await
    }

    pub async fn find_by_user_id(&self, user_id: &str) -> Result<Vec<EncryptionKey>, sqlx::Error> {
        sqlx::query_as::<_, EncryptionKey>(
            r#"
            SELECT * FROM encryption_keys WHERE user_id = $1 ORDER BY created_at DESC
            "#
        )
        .bind(user_id)
        .fetch_all(&self.pool)
        .await
    }

    pub async fn find_active_by_user_id(&self, user_id: &str) -> Result<Vec<EncryptionKey>, sqlx::Error> {
        sqlx::query_as::<_, EncryptionKey>(
            r#"
            SELECT * FROM encryption_keys WHERE user_id = $1 AND is_active = true ORDER BY created_at DESC
            "#
        )
        .bind(user_id)
        .fetch_all(&self.pool)
        .await
    }

    pub async fn update_status(&self, id: Uuid, is_active: bool) -> Result<(), sqlx::Error> {
        sqlx::query(
            r#"
            UPDATE encryption_keys SET is_active = $1, updated_at = NOW() WHERE id = $2
            "#
        )
        .bind(is_active)
        .bind(id)
        .execute(&self.pool)
        .await?;
        Ok(())
    }

    pub async fn delete(&self, id: Uuid) -> Result<(), sqlx::Error> {
        sqlx::query(
            r#"
            DELETE FROM encryption_keys WHERE id = $1
            "#
        )
        .bind(id)
        .execute(&self.pool)
        .await?;
        Ok(())
    }
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Signature {
    pub id: Uuid,
    pub user_id: String,
    pub signature_type: String,
    pub signature_value: String,
    pub algorithm: String,
    pub expires_at: Option<DateTime<Utc>>,
    pub is_active: bool,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

pub struct SignatureRepository {
    pool: PgPool,
}

impl SignatureRepository {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }

    pub async fn create(&self, signature: &Signature) -> Result<Signature, sqlx::Error> {
        sqlx::query_as::<_, Signature>(
            r#"
            INSERT INTO signatures (id, user_id, signature_type, signature_value, algorithm, expires_at, is_active, created_at, updated_at)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
            RETURNING *
            "#
        )
        .bind(signature.id)
        .bind(&signature.user_id)
        .bind(&signature.signature_type)
        .bind(&signature.signature_value)
        .bind(&signature.algorithm)
        .bind(signature.expires_at)
        .bind(signature.is_active)
        .bind(signature.created_at)
        .bind(signature.updated_at)
        .fetch_one(&self.pool)
        .await
    }

    pub async fn find_by_id(&self, id: Uuid) -> Result<Option<Signature>, sqlx::Error> {
        sqlx::query_as::<_, Signature>(
            r#"
            SELECT * FROM signatures WHERE id = $1
            "#
        )
        .bind(id)
        .fetch_optional(&self.pool)
        .await
    }

    pub async fn find_by_user_id(&self, user_id: &str) -> Result<Vec<Signature>, sqlx::Error> {
        sqlx::query_as::<_, Signature>(
            r#"
            SELECT * FROM signatures WHERE user_id = $1 ORDER BY created_at DESC
            "#
        )
        .bind(user_id)
        .fetch_all(&self.pool)
        .await
    }

    pub async fn update_status(&self, id: Uuid, is_active: bool) -> Result<(), sqlx::Error> {
        sqlx::query(
            r#"
            UPDATE signatures SET is_active = $1, updated_at = NOW() WHERE id = $2
            "#
        )
        .bind(is_active)
        .bind(id)
        .execute(&self.pool)
        .await?;
        Ok(())
    }

    pub async fn delete(&self, id: Uuid) -> Result<(), sqlx::Error> {
        sqlx::query(
            r#"
            DELETE FROM signatures WHERE id = $1
            "#
        )
        .bind(id)
        .execute(&self.pool)
        .await?;
        Ok(())
    }
}