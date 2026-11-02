package Domain

import (
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
)

func TestNewSampleEntity(t *testing.T) {
	tests := []struct {
		name        string
		inputName   string
		inputValue  int
		inputDesc   string
		wantErr     bool
		errContains string
	}{
		{
			name:        "valid input",
			inputName:   "Test",
			inputValue:  10,
			inputDesc:   "A test entity",
			wantErr:     false,
		},
		{
			name:        "empty name",
			inputName:   "",
			inputValue:  5,
			inputDesc:   "Should fail",
			wantErr:     true,
			errContains: "name cannot be empty",
		},
		{
			name:        "negative value",
			inputName:   "Valid",
			inputValue:  -1,
			inputDesc:   "Should fail",
			wantErr:     true,
			errContains: "value must be non-negative",
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			entity, err := NewSampleEntity(tt.inputName, tt.inputValue, tt.inputDesc)
			if tt.wantErr {
				assert.Error(t, err)
				assert.Contains(t, err.Error(), tt.errContains)
				assert.Nil(t, entity)
			} else {
				assert.NoError(t, err)
				assert.NotNil(t, entity)
				assert.Equal(t, tt.inputName, entity.Name)
				assert.Equal(t, tt.inputValue, entity.Value)
				assert.Equal(t, tt.inputDesc, entity.Description)
				assert.True(t, !entity.CreatedAt.IsZero(), "CreatedAt should be set")
			}
		})
	}
}

func TestSampleEntity_Update(t *testing.T) {
	orig := &SampleEntity{
		Name:        "Old",
		Value:       1,
		Description: "Old desc",
		CreatedAt:   time.Now(),
	}

	tests := []struct {
		name        string
		newName     string
		newValue    int
		newDesc     string
		wantErr     bool
		errContains string
	}{
		{
			name:        "valid update",
			newName:     "New",
			newValue:    2,
			newDesc:     "New desc",
			wantErr:     false,
		},
		{
			name:        "empty name",
			newName:     "",
			newValue:    2,
			newDesc:     "Should fail",
			wantErr:     true,
				errContains: "name cannot be empty",
		},
		{
			name:        "negative value",
			newName:     "Valid",
			newValue:    -5,
			newDesc:     "Should fail",
			wantErr:     true,
			errContains: "value must be non-negative",
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			// Clone the original entity for each subtest
			entity := *orig
			err := entity.Update(tt.newName, tt.newValue, tt.newDesc)
			if tt.wantErr {
				assert.Error(t, err)
				assert.Contains(t, err.Error(), tt.errContains)
				// Entity should remain unchanged
				assert.Equal(t, orig.Name, entity.Name)
				assert.Equal(t, orig.Value, entity.Value)
				assert.Equal(t, orig.Description, entity.Description)
			} else {
				assert.NoError(t, err)
				assert.Equal(t, tt.newName, entity.Name)
				assert.Equal(t, tt.newValue, entity.Value)
				assert.Equal(t, tt.newDesc, entity.Description)
			}
		})
	}
}
