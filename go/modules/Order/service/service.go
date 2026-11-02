package service
import (
    "context"
    "errors"
    "community.local/modules/Order/internal/models"
)
var ErrNotImplemented = errors.New("not implemented")
type Service struct{}
func NewService() *Service { return &Service{} }
func (s *Service) Get(ctx context.Context, id string) (*models.Order, error) { return nil, ErrNotImplemented }
func (s *Service) List(ctx context.Context, limit, offset int) ([]*models.Order, error) { return nil, ErrNotImplemented }
func (s *Service) Create(ctx context.Context, ent *models.Order) error { return ErrNotImplemented }
func (s *Service) Update(ctx context.Context, ent *models.Order) error { return ErrNotImplemented }
func (s *Service) Delete(ctx context.Context, id string) error { return ErrNotImplemented }
