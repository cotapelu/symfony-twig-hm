package service
import (
    "context"
    "testing"
    "github.com/stretchr/testify/assert"
    "community.local/modules/Order/internal/models"
)
func TestGet(t *testing.T) {
    svc := NewService()
    _, err := svc.Get(context.Background(), "123")
    assert.Error(t, err)
}
func TestList(t *testing.T) {
    svc := NewService()
    _, err := svc.List(context.Background(), 10, 0)
    assert.Error(t, err)
}
func TestCreate(t *testing.T) {
    svc := NewService()
    err := svc.Create(context.Background(), &models.Order{})
    assert.Error(t, err)
}
func TestUpdate(t *testing.T) {
    svc := NewService()
    err := svc.Update(context.Background(), &models.Order{ID:"123"})
    assert.Error(t, err)
}
func TestDelete(t *testing.T) {
    svc := NewService()
    err := svc.Delete(context.Background(), "123")
    assert.Error(t, err)
}
