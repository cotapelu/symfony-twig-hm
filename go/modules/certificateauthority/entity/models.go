package entity

import "time"

type Certificate struct {
	ID               string     `json:"id"`
	CommonName       string     `json:"common_name"`
	SubjectAltNames  []string   `json:"subject_alt_names"`
	SerialNumber     string     `json:"serial_number"`
	CertPEM          string     `json:"cert_pem"`
	PrivateKeyPEM    string     `json:"private_key_pem,omitempty"`
	IssuerID         string     `json:"issuer_id"`
	CAID             string     `json:"ca_id"`
	NotBefore        time.Time  `json:"not_before"`
	NotAfter         time.Time  `json:"not_after"`
	IsCA             bool       `json:"is_ca"`
	IsRevoked        bool       `json:"is_revoked"`
	RevokedAt        *time.Time `json:"revoked_at,omitempty"`
	RevocationReason string     `json:"revocation_reason,omitempty"`
	KeyUsage         string     `json:"key_usage"`
	CreatedAt        time.Time  `json:"created_at"`
}

type CertificateAuthority struct {
	ID               string    `json:"id"`
	Name             string    `json:"name"`
	CertPEM          string    `json:"cert_pem"`
	PrivateKeyPEM    string    `json:"private_key_pem"`
	ParentCAID       string    `json:"parent_ca_id,omitempty"`
	IsRoot           bool      `json:"is_root"`
	NotBefore        time.Time `json:"not_before"`
	NotAfter         time.Time `json:"not_after"`
	KeyUsage         string    `json:"key_usage"`
	PathLength       int       `json:"path_length"`
	ChildrenCount    int       `json:"children_count"`
	IssuedCertsCount int       `json:"issued_certs_count"`
	CreatedAt        time.Time `json:"created_at"`
}

type CertificateSigningRequest struct {
	ID              string     `json:"id"`
	CommonName      string     `json:"common_name"`
	SubjectAltNames []string   `json:"subject_alt_names"`
	CSRPEM          string     `json:"csr_pem"`
	KeyUsage        string     `json:"key_usage"`
	ExtKeyUsage     []string   `json:"ext_key_usage"`
	RequestedBy     string     `json:"requested_by"`
	CAID            string     `json:"ca_id"`
	Status          string     `json:"status"`
	RejectedReason  string     `json:"rejected_reason,omitempty"`
	CertID          string     `json:"cert_id,omitempty"`
	CreatedAt       time.Time  `json:"created_at"`
	ProcessedAt     *time.Time `json:"processed_at,omitempty"`
}

type SignCertificateRequest struct {
	CAID            string   `json:"ca_id"`
	CommonName      string   `json:"common_name"`
	SubjectAltNames []string `json:"subject_alt_names"`
	CSRPEM          string   `json:"csr_pem"`
	KeyUsage        string   `json:"key_usage"`
	ExtKeyUsage     []string `json:"ext_key_usage"`
	ValidityDays    int      `json:"validity_days"`
}

type CreateCARequest struct {
	Name       string `json:"name"`
	CommonName string `json:"common_name"`
	PathLength int    `json:"path_length"`
}

type IssueCertificateResponse struct {
	CertificateID string    `json:"certificate_id"`
	CertPEM       string    `json:"cert_pem"`
	PrivateKeyPEM string    `json:"private_key_pem"`
	NotBefore     time.Time `json:"not_before"`
	NotAfter      time.Time `json:"not_after"`
}

type RevokeCertificateRequest struct {
	Reason string `json:"reason"`
}
