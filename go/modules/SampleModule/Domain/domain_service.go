package Domain

// SampleDomainService is a placeholder domain service for the Sample module.
// It currently contains no operations but is required by the Application service
// to satisfy the dependency on the domain layer.
type SampleDomainService struct{}

// NewSampleDomainService creates a new instance.
func NewSampleDomainService() *SampleDomainService {
	return &SampleDomainService{}
}
