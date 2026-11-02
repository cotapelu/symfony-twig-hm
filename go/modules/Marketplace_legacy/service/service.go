package service
import (
    "context"
    "errors"
    "community.local/modules/Marketplace_legacy/internal/models"
)
var ErrNotImplemented = errors.New("not implemented")
type Service struct{}
func NewService() *Service { return &Service{} }
func (s *Service) Get(ctx context.Context, id string) (*models.Marketplace, error) { return nil, ErrNotImplemented }
func (s *Service) List(ctx context.Context, limit, offset int) ([]*models.Marketplace, error) { return nil, ErrNotImplemented }
func (s *Service) Create(ctx context.Context, ent *models.Marketplace) error { return ErrNotImplemented }
func (s *Service) Update(ctx context.Context, ent *models.Marketplace) error { return ErrNotImplemented }
func (s *Service) Delete(ctx context.Context, id string) error { return ErrNotImplemented }
