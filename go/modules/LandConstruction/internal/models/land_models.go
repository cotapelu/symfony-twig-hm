package landmodels

import (
	"time"
)

type LandRecord struct {
	ID         int64     `json:"id"`
	PlotNumber string    `json:"plotNumber"`
	Owner      string    `json:"owner"`
	Area       float64   `json:"area"`
	Address    string    `json:"address"`
	Type       string    `json:"type"`
	Status     string    `json:"status"`
	CreatedAt  time.Time `json:"createdAt"`
	UpdatedAt  time.Time `json:"updatedAt"`
}

type LandStatistics struct {
	TotalRecords     int     `json:"totalRecords"`
	TotalArea        float64 `json:"totalArea"`
	ByType           map[string]int `json:"byType"`
	ByStatus         map[string]int `json:"byStatus"`
}

type ConstructionPermit struct {
	ID             int64      `json:"id"`
	Applicant      string     `json:"applicant"`
	Address        string     `json:"address"`
	PermitType     string     `json:"permitType"`
	Area           float64    `json:"area"`
	Description    string     `json:"description"`
	Status         string     `json:"status"`
	AssignedTo     *string    `json:"assignedTo"`
	SubmittedDate  time.Time  `json:"submittedDate"`
	ProcessedDate  *time.Time `json:"processedDate"`
	CreatedAt      time.Time  `json:"createdAt"`
	UpdatedAt      time.Time  `json:"updatedAt"`
}

type PermitStatistics struct {
	TotalPermits      int     `json:"totalPermits"`
	ByStatus          map[string]int `json:"byStatus"`
	ByPermitType      map[string]int `json:"byPermitType"`
	AverageProcessing float64 `json:"averageProcessingDays"`
}