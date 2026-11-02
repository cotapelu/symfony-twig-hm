use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use sqlx::PgPool;
use uuid::Uuid;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct AccessControl {
    pub id: Uuid,
    pub user_id: String,
    pub resource_type: String,
    pub resource_id: String,
    pub permission: String,
    pub granted_by: String,
    pub expires_at: Option<DateTime<Utc>>,
    pub is_active: bool,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

pub struct AccessControlRepository {
    pool: PgPool,
}

impl AccessControlRepository {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }

    pub async fn create(&self, access_control: &AccessControl) -> Result<AccessControl, sqlx::Error> {
        sqlx::query_as::<_, AccessControl>(
            r#"
            INSERT INTO access_controls (id, user_id, resource_type, resource_id, permission, granted_by, expires_at, is_active, created_at, updated_at)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
            RETURNING *
            "#
        )
        .bind(access_control.id)
        .bind(&access_control.user_id)
        .bind(&access_control.resource_type)
        .bind(&access_control.resource_id)
        .bind(&access_control.permission)
        .bind(&access_control.granted_by)
        .bind(access_control.expires_at)
        .bind(access_control.is_active)
        .bind(access_control.created_at)
        .bind(access_control.updated_at)
        .fetch_one(&self.pool)
        .await
    }

    pub async fn find_by_id(&self, id: Uuid) -> Result<Option<AccessControl>, sqlx::Error> {
        sqlx::query_as::<_, AccessControl>(
            r#"
            SELECT * FROM access_controls WHERE id = $1
            "#
        )
        .bind(id)
        .fetch_optional(&self.pool)
        .await
    }

    pub async fn find_by_user_id(&self, user_id: &str) -> Result<Vec<AccessControl>, sqlx::Error> {
        sqlx::query_as::<_, AccessControl>(
            r#"
            SELECT * FROM access_controls WHERE user_id = $1 ORDER BY created_at DESC
            "#
        )
        .bind(user_id)
        .fetch_all(&self.pool)
        .await
    }

    pub async fn find_by_resource(&self, resource_type: &str, resource_id: &str) -> Result<Vec<AccessControl>, sqlx::Error> {
        sqlx::query_as::<_, AccessControl>(
            r#"
            SELECT * FROM access_controls WHERE resource_type = $1 AND resource_id = $2 ORDER BY created_at DESC
            "#
        )
        .bind(resource_type)
        .bind(resource_id)
        .fetch_all(&self.pool)
        .await
    }

    pub async fn find_active_by_user_id(&self, user_id: &str) -> Result<Vec<AccessControl>, sqlx::Error> {
        sqlx::query_as::<_, AccessControl>(
            r#"
            SELECT * FROM access_controls WHERE user_id = $1 AND is_active = true ORDER BY created_at DESC
            "#
        )
        .bind(user_id)
        .fetch_all(&self.pool)
        .await
    }

    pub async fn check_permission(&self, user_id: &str, resource_type: &str, resource_id: &str, permission: &str) -> Result<bool, sqlx::Error> {
        let result = sqlx::query_scalar::<_, bool>(
            r#"
            SELECT EXISTS(
                SELECT 1 FROM access_controls 
                WHERE user_id = $1 AND resource_type = $2 AND resource_id = $3 AND permission = $4 
                AND is_active = true AND (expires_at IS NULL OR expires_at > NOW())
            )
            "#
        )
        .bind(user_id)
        .bind(resource_type)
        .bind(resource_id)
        .bind(permission)
        .fetch_one(&self.pool)
        .await?;
        Ok(result)
    }

    pub async fn update_status(&self, id: Uuid, is_active: bool) -> Result<(), sqlx::Error> {
        sqlx::query(
            r#"
            UPDATE access_controls SET is_active = $1, updated_at = NOW() WHERE id = $2
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
            DELETE FROM access_controls WHERE id = $1
            "#
        )
        .bind(id)
        .execute(&self.pool)
        .await?;
        Ok(())
    }

    pub async fn revoke_by_user_id(&self, user_id: &str) -> Result<u64, sqlx::Error> {
        let result = sqlx::query(
            r#"
            UPDATE access_controls SET is_active = false, updated_at = NOW() WHERE user_id = $1
            "#
        )
        .bind(user_id)
        .execute(&self.pool)
        .await?;
        Ok(result.rows_affected())
    }
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Permission {
    pub id: Uuid,
    pub name: String,
    pub description: String,
    pub resource_type: String,
    pub is_active: bool,
    pub created_at: DateTime<Utc>,
    pub updated_at: DateTime<Utc>,
}

pub struct PermissionRepository {
    pool: PgPool,
}

impl PermissionRepository {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }

    pub async fn create(&self, permission: &Permission) -> Result<Permission, sqlx::Error> {
        sqlx::query_as::<_, Permission>(
            r#"
            INSERT INTO permissions (id, name, description, resource_type, is_active, created_at, updated_at)
            VALUES ($1, $2, $3, $4, $5, $6, $7)
            RETURNING *
            "#
        )
        .bind(permission.id)
        .bind(&permission.name)
        .bind(&permission.description)
        .bind(&permission.resource_type)
        .bind(permission.is_active)
        .bind(permission.created_at)
        .bind(permission.updated_at)
        .fetch_one(&self.pool)
        .await
    }

    pub async fn find_by_id(&self, id: Uuid) -> Result<Option<Permission>, sqlx::Error> {
        sqlx::query_as::<_, Permission>(
            r#"
            SELECT * FROM permissions WHERE id = $1
            "#
        )
        .bind(id)
        .fetch_optional(&self.pool)
        .await
    }

    pub async fn find_by_name(&self, name: &str) -> Result<Option<Permission>, sqlx::Error> {
        sqlx::query_as::<_, Permission>(
            r#"
            SELECT * FROM permissions WHERE name = $1
            "#
        )
        .bind(name)
        .fetch_optional(&self.pool)
        .await
    }

    pub async fn find_by_resource_type(&self, resource_type: &str) -> Result<Vec<Permission>, sqlx::Error> {
        sqlx::query_as::<_, Permission>(
            r#"
            SELECT * FROM permissions WHERE resource_type = $1 ORDER BY name
            "#
        )
        .bind(resource_type)
        .fetch_all(&self.pool)
        .await
    }

    pub async fn find_active_permissions(&self) -> Result<Vec<Permission>, sqlx::Error> {
        sqlx::query_as::<_, Permission>(
            r#"
            SELECT * FROM permissions WHERE is_active = true ORDER BY name
            "#
        )
        .fetch_all(&self.pool)
        .await
    }

    pub async fn update_status(&self, id: Uuid, is_active: bool) -> Result<(), sqlx::Error> {
        sqlx::query(
            r#"
            UPDATE permissions SET is_active = $1, updated_at = NOW() WHERE id = $2
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
            DELETE FROM permissions WHERE id = $1
            "#
        )
        .bind(id)
        .execute(&self.pool)
        .await?;
        Ok(())
    }
}