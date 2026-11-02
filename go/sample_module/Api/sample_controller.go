// Sample Controller for API layer
package api

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

// SampleController handles HTTP requests for the sample module
type SampleController struct {
	applicationService *ApplicationService
}

// NewSampleController creates a new SampleController
func NewSampleController(applicationService *ApplicationService) *SampleController {
	return &SampleController{
		applicationService: applicationService,
	}
}

// GetSample handles GET requests to retrieve a sample by ID
func (c *SampleController) GetSample(ctx *gin.Context) {
	id := ctx.Param("id")
	// In a real implementation, we would convert id to int and call applicationService
	ctx.JSON(http.StatusOK, gin.H{
		"message": "Get sample not implemented",
		"id":      id,
	})
}

// CreateSample handles POST requests to create a new sample
func (c *SampleController) CreateSample(ctx *gin.Context) {
	// In a real implementation, we would bind JSON to a DTO and call applicationService
	ctx.JSON(http.StatusCreated, gin.H{
		"message": "Create sample not implemented",
	})
}