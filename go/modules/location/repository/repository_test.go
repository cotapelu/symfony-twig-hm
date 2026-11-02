package repository

import (
	"context"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"community.local/modules/location/entity"
)

func newNow() time.Time {
	return time.Now().Truncate(time.Microsecond)
}

// LocationRepository tests

func TestInMemoryLocationRepository_SaveAndGet(t *testing.T) {
	repo := NewInMemoryLocationRepository()
	now := newNow()
	loc := &entity.UserLocation{
		ID:        "loc-1",
		UserID:    "user-1",
		Location:  entity.Location{Latitude: 10.0, Longitude: 20.0},
		UpdatedAt: now,
	}
	require.NoError(t, repo.Save(context.Background(), loc))

	retrieved, err := repo.GetByUserID(context.Background(), "user-1")
	require.NoError(t, err)
	assert.Equal(t, loc, retrieved)
}

func TestInMemoryLocationRepository_GetByUserID_NotFound(t *testing.T) {
	repo := NewInMemoryLocationRepository()
	retrieved, err := repo.GetByUserID(context.Background(), "unknown")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
}

func TestInMemoryLocationRepository_Delete(t *testing.T) {
	repo := NewInMemoryLocationRepository()
	now := newNow()
	loc := &entity.UserLocation{
		ID:        "loc-1",
		UserID:    "user-1",
		Location:  entity.Location{Latitude: 10, Longitude: 20},
		UpdatedAt: now,
	}
	require.NoError(t, repo.Save(context.Background(), loc))

	err := repo.Delete(context.Background(), "user-1")
	require.NoError(t, err)

	retrieved, err := repo.GetByUserID(context.Background(), "user-1")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
}

func TestInMemoryLocationRepository_GetAll(t *testing.T) {
	repo := NewInMemoryLocationRepository()
	now := newNow()
	locs := []*entity.UserLocation{
		{ID: "l1", UserID: "u1", Location: entity.Location{Latitude: 1, Longitude: 1}, UpdatedAt: now},
		{ID: "l2", UserID: "u2", Location: entity.Location{Latitude: 2, Longitude: 2}, UpdatedAt: now},
	}
	for _, l := range locs {
		require.NoError(t, repo.Save(context.Background(), l))
	}
	all, err := repo.GetAll(context.Background())
	require.NoError(t, err)
	assert.ElementsMatch(t, locs, all)
}

func TestInMemoryLocationRepository_GetWithinBounds(t *testing.T) {
	repo := NewInMemoryLocationRepository()
	now := newNow()
	locs := []*entity.UserLocation{
		{ID: "l1", UserID: "u1", Location: entity.Location{Latitude: 10, Longitude: 10}, UpdatedAt: now},
		{ID: "l2", UserID: "u2", Location: entity.Location{Latitude: 20, Longitude: 20}, UpdatedAt: now},
		{ID: "l3", UserID: "u3", Location: entity.Location{Latitude: 5, Longitude: 5}, UpdatedAt: now},
	}
	for _, l := range locs {
		require.NoError(t, repo.Save(context.Background(), l))
	}
	// Bounding box: lat 8-25, lon 8-25 -> should include l1 and l2
	found, err := repo.GetWithinBounds(context.Background(), 8, 25, 8, 25)
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.UserLocation{locs[0], locs[1]}, found)
}

// LocationHistoryRepository tests

func TestInMemoryLocationHistoryRepository_AddAndGet(t *testing.T) {
	repo := NewInMemoryLocationHistoryRepository()
	now := newNow()
	loc := &entity.UserLocation{
		ID:        "h1",
		UserID:    "user-1",
		Location:  entity.Location{Latitude: 10, Longitude: 20},
		UpdatedAt: now,
	}
	require.NoError(t, repo.Add(context.Background(), loc))

	history, err := repo.GetByUserID(context.Background(), "user-1", 10)
	require.NoError(t, err)
	assert.Equal(t, []*entity.UserLocation{loc}, history)
}

func TestInMemoryLocationHistoryRepository_GetByUserID_Limit(t *testing.T) {
	repo := NewInMemoryLocationHistoryRepository()
	now := newNow()
	for i := 0; i < 5; i++ {
		loc := &entity.UserLocation{
			ID:        "h" + string(rune('0'+i)),
			UserID:    "user-1",
			Location:  entity.Location{Latitude: float64(i), Longitude: float64(i)},
			UpdatedAt: now.Add(time.Duration(i) * time.Second),
		}
		require.NoError(t, repo.Add(context.Background(), loc))
	}
	// get last 3
	history, err := repo.GetByUserID(context.Background(), "user-1", 3)
	require.NoError(t, err)
	assert.Len(t, history, 3)
	// Should be last three entries
	assert.Equal(t, "h2", history[0].ID)
	assert.Equal(t, "h3", history[1].ID)
	assert.Equal(t, "h4", history[2].ID)
}

func TestInMemoryLocationHistoryRepository_GetByUserIDTimeRange(t *testing.T) {
	repo := NewInMemoryLocationHistoryRepository()
	base := newNow()
	locations := []*entity.UserLocation{
		{ID: "h1", UserID: "u1", Location: entity.Location{Latitude: 1, Longitude: 1}, UpdatedAt: base.Add(-2 * time.Hour)},
		{ID: "h2", UserID: "u1", Location: entity.Location{Latitude: 2, Longitude: 2}, UpdatedAt: base.Add(-1 * time.Hour)},
		{ID: "h3", UserID: "u1", Location: entity.Location{Latitude: 3, Longitude: 3}, UpdatedAt: base},
		{ID: "h4", UserID: "u1", Location: entity.Location{Latitude: 4, Longitude: 4}, UpdatedAt: base.Add(1 * time.Hour)},
	}
	for _, l := range locations {
		require.NoError(t, repo.Add(context.Background(), l))
	}
	from := base.Add(-90 * time.Minute)
	to := base.Add(30 * time.Minute)
	filtered, err := repo.GetByUserIDTimeRange(context.Background(), "u1", from, to)
	require.NoError(t, err)
	// Should include h2, h3 (maybe h4 if within to? h4 at +1h > to so no)
	assert.ElementsMatch(t, []*entity.UserLocation{locations[1], locations[2]}, filtered)
}

func TestInMemoryLocationHistoryRepository_Count(t *testing.T) {
	repo := NewInMemoryLocationHistoryRepository()
	now := newNow()
	for i := 0; i < 5; i++ {
		loc := &entity.UserLocation{
			ID:        "h" + string(rune('0'+i)),
			UserID:    "user-1",
			Location:  entity.Location{Latitude: float64(i), Longitude: float64(i)},
			UpdatedAt: now,
		}
		require.NoError(t, repo.Add(context.Background(), loc))
	}
	count, err := repo.Count(context.Background(), "user-1")
	require.NoError(t, err)
	assert.Equal(t, int64(5), count)
}

func TestInMemoryLocationHistoryRepository_DeleteOlderThan(t *testing.T) {
	repo := NewInMemoryLocationHistoryRepository()
	now := newNow()
	old := &entity.UserLocation{
		ID:        "old",
		UserID:    "user-1",
		Location:  entity.Location{Latitude: 1, Longitude: 1},
		UpdatedAt: now.Add(-2 * time.Hour),
	}
	newer := &entity.UserLocation{
		ID:        "new",
		UserID:    "user-1",
		Location:  entity.Location{Latitude: 2, Longitude: 2},
		UpdatedAt: now.Add(-1 * time.Hour),
	}
	require.NoError(t, repo.Add(context.Background(), old))
	require.NoError(t, repo.Add(context.Background(), newer))

	deleted, err := repo.DeleteOlderThan(context.Background(), now.Add(-90*time.Minute))
	require.NoError(t, err)
	assert.Equal(t, int64(1), deleted)

	// Check history now
	remaining, err := repo.GetByUserID(context.Background(), "user-1", 10)
	require.NoError(t, err)
	assert.Len(t, remaining, 1)
	assert.Equal(t, "new", remaining[0].ID)
}

// GeofenceRepository tests

func TestInMemoryGeofenceRepository_SaveAndGet(t *testing.T) {
	repo := NewInMemoryGeofenceRepository()
	now := newNow()
	geo := &entity.Geofence{
		ID:          "geo-1",
		Name:        "Test Geofence",
		Radius:      0.5,
		Points:      []entity.Location{{Latitude: 0, Longitude: 0}},
		CreatedAt:   now,
	}
	require.NoError(t, repo.Save(context.Background(), geo))

	retrieved, err := repo.GetByID(context.Background(), "geo-1")
	require.NoError(t, err)
	assert.Equal(t, geo, retrieved)
}

func TestInMemoryGeofenceRepository_GetByID_NotFound(t *testing.T) {
	repo := NewInMemoryGeofenceRepository()
	_, err := repo.GetByID(context.Background(), "unknown")
	require.NoError(t, err)
	// returns nil, not an error
	// We'll check that returned value is nil
	// In the implementation, it returns nil, nil if not found.
	// Since GetByID returns (*Geofence, error), we need to capture both.
	retrieved, err := repo.GetByID(context.Background(), "unknown")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
}

func TestInMemoryGeofenceRepository_Delete(t *testing.T) {
	repo := NewInMemoryGeofenceRepository()
	now := newNow()
	geo := &entity.Geofence{
		ID:        "geo-1",
		Name:      "Test",
		Radius:    1,
		Points:    []entity.Location{{Latitude: 0, Longitude: 0}},
		CreatedAt: now,
	}
	require.NoError(t, repo.Save(context.Background(), geo))

	err := repo.Delete(context.Background(), "geo-1")
	require.NoError(t, err)

	_, err = repo.GetByID(context.Background(), "geo-1")
	assert.ErrorIs(t, err, nil) // Actually GetByID returns nil,nil; we can check nil
	retrieved, _ := repo.GetByID(context.Background(), "geo-1")
	assert.Nil(t, retrieved)
}

func TestInMemoryGeofenceRepository_List(t *testing.T) {
	repo := NewInMemoryGeofenceRepository()
	now := newNow()
	geos := []*entity.Geofence{
		{ID: "g1", Name: "A", Radius: 0.5, Points: []entity.Location{{Latitude: 0, Longitude: 0}}, CreatedAt: now},
		{ID: "g2", Name: "B", Radius: 1.0, Points: []entity.Location{{Latitude: 1, Longitude: 1}}, CreatedAt: now},
		{ID: "g3", Name: "C", Radius: 0.8, Points: []entity.Location{{Latitude: 2, Longitude: 2}}, CreatedAt: now},
	}
	for _, g := range geos {
		require.NoError(t, repo.Save(context.Background(), g))
	}
	all, err := repo.List(context.Background())
	require.NoError(t, err)
	assert.ElementsMatch(t, geos, all)
}

func TestInMemoryGeofenceRepository_FindByName(t *testing.T) {
	repo := NewInMemoryGeofenceRepository()
	now := newNow()
	geos := []*entity.Geofence{
		{ID: "g1", Name: "Shopping Mall", Radius: 0.5, Points: []entity.Location{{Latitude: 0, Longitude: 0}}, CreatedAt: now},
		{ID: "g2", Name: "Mall Plaza", Radius: 1.0, Points: []entity.Location{{Latitude: 1, Longitude: 1}}, CreatedAt: now},
		{ID: "g3", Name: "Warehouse", Radius: 0.8, Points: []entity.Location{{Latitude: 2, Longitude: 2}}, CreatedAt: now},
	}
	for _, g := range geos {
		require.NoError(t, repo.Save(context.Background(), g))
	}
	// Exact match
	exact, err := repo.FindByName(context.Background(), "Shopping Mall")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Geofence{geos[0]}, exact)

	// Substring match "Mall"
	partial, err := repo.FindByName(context.Background(), "Mall")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Geofence{geos[0], geos[1]}, partial)

	// No match
	none, err := repo.FindByName(context.Background(), "Park")
	require.NoError(t, err)
	assert.Empty(t, none)
}
