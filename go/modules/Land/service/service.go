package service
import (
    "context"
    "errors"
    "community.local/modules/Land/internal/models"
)
var ErrNotImplemented = errors.New("not implemented")
type Service struct{}
func NewService() *Service { return &Service{} }
func (s *Service) Get(ctx context.Context, id string) (*models.Land, error) { return nil, ErrNotImplemented }
func (s *Service) List(ctx context.Context, limit, offset int) ([]*models.Land, error) { return nil, ErrNotImplemented }
func (s *Service) Create(ctx context.Context, ent *models.Land) error { return ErrNotImplemented }
func (s *Service) Update(ctx context.Context, ent *models.Land) error { return ErrNotImplemented }
func (s *Service) Delete(ctx context.Context, id string) error { return ErrNotImplemented }
