package service
import (
    "context"
    "errors"
    "community.local/modules/Crypto/internal/models"
)
var ErrNotImplemented = errors.New("not implemented")
type Service struct{}
func NewService() *Service { return &Service{} }
func (s *Service) Get(ctx context.Context, id string) (*models.Crypto, error) { return nil, ErrNotImplemented }
func (s *Service) List(ctx context.Context, limit, offset int) ([]*models.Crypto, error) { return nil, ErrNotImplemented }
func (s *Service) Create(ctx context.Context, ent *models.Crypto) error { return ErrNotImplemented }
func (s *Service) Update(ctx context.Context, ent *models.Crypto) error { return ErrNotImplemented }
func (s *Service) Delete(ctx context.Context, id string) error { return ErrNotImplemented }
