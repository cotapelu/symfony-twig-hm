package service
import (
    "context"
    "errors"
    "community.local/modules/Inventory/internal/models"
)
var ErrNotImplemented = errors.New("not implemented")
type Service struct{}
func NewService() *Service { return &Service{} }
func (s *Service) Get(ctx context.Context, id string) (*models.Inventory, error) { return nil, ErrNotImplemented }
func (s *Service) List(ctx context.Context, limit, offset int) ([]*models.Inventory, error) { return nil, ErrNotImplemented }
func (s *Service) Create(ctx context.Context, ent *models.Inventory) error { return ErrNotImplemented }
func (s *Service) Update(ctx context.Context, ent *models.Inventory) error { return ErrNotImplemented }
func (s *Service) Delete(ctx context.Context, id string) error { return ErrNotImplemented }
