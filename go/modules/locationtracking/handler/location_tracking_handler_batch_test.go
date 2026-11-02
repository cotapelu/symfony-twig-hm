package handler

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
	"time"

	"community.local/modules/locationtracking/entity"
	"community.local/modules/locationtracking/service"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestLocationTrackingHandler_BatchUpdateLocations(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tmpFile := "/tmp/location_batch_test.ets"
	defer os.Remove(tmpFile)

	svc := service.NewLocationTrackingService(tmpFile)
	h := NewLocationTrackingHandler(svc)

	router := gin.New()
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)

	// Prepare valid locations
	now := time.Now()
	loc1 := entity.Location{
		EntityID:   "user1",
		EntityType: "user",
		Latitude:   10.0,
		Longitude:  20.0,
		Accuracy:   5.0,
		Timestamp:  now,
		UpdatedAt:  now,
	}
	loc2 := entity.Location{
		EntityID:   "user1",
		EntityType: "user",
		Latitude:   11.0,
		Longitude:  21.0,
		Accuracy:   6.0,
		Timestamp:  now,
		UpdatedAt:  now,
	}
	reqBody := map[string][]entity.Location{"locations": []entity.Location{loc1, loc2}}
	bodyBytes, _ := json.Marshal(reqBody)

	t.Run("successful batch update", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodPost, "/api/locationtracking/batch", bytes.NewReader(bodyBytes))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")

		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)

		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		json.Unmarshal(w.Body.Bytes(), &resp)
		assert.Equal(t, "locations batch updated", resp["status"])
		assert.Equal(t, float64(2), resp["count"])
		updatedList, ok := resp["updated"].([]interface{})
		assert.True(t, ok)
		assert.Len(t, updatedList, 2)
	})

	t.Run("validation error malformed JSON", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodPost, "/api/locationtracking/batch", bytes.NewReader([]byte(`invalid json`)))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")

		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)

		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}
