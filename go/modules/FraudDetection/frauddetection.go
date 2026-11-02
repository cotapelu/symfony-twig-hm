//go:build ignore

package FraudDetection

// NewHandler creates a new FraudDetectionHandler
func NewHandler(db *sql.DB) *handler.FraudDetectionHandler {
	svc := fd.NewFraudDetectionService(db)
	return handler.NewFraudDetectionHandler(svc)
}
