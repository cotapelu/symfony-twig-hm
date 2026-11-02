package models

type Fraud struct {
	ID        string
	UserID    string
	Type      string
	RiskScore float64
	Status    string
	Details   string
	CreatedAt string
}
