// Sample Data Transfer Object for API layer
package api

// SampleDto represents the data transfer object for the sample module
type SampleDto struct {
	Name        string `json:"name"`
	Value       int    `json:"value"`
	Description string `json:"description,omitempty"`
}