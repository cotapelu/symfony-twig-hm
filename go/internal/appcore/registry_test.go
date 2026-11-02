package appcore

import (
	"fmt"
	"strings"
	"testing"
)

type mockModule struct {
	name    string
	initErr error
}

func (m *mockModule) Name() string { return m.name }
func (m *mockModule) Init() error { return m.initErr }

func TestNewRegistry(t *testing.T) {
	r := NewRegistry()
	if r == nil {
		t.Fatal("NewRegistry returned nil")
	}
	if r.modules == nil {
		t.Error("modules map not initialized")
	}
}

func TestRegistry_RegisterAndGet(t *testing.T) {
	r := NewRegistry()
	mod := &mockModule{name: "mod1"}
	r.Register(mod)
	got := r.Get("mod1")
	if got != mod {
		t.Errorf("Get() = %v, want %p", got, mod)
	}
}

func TestRegistry_Get_NotExists(t *testing.T) {
	r := NewRegistry()
	if r.Get("unknown") != nil {
		t.Error("Get() should return nil for unknown module")
	}
}

func TestRegistry_InitAll_Success(t *testing.T) {
	r := NewRegistry()
	mod1 := &mockModule{name: "m1"}
	mod2 := &mockModule{name: "m2"}
	r.Register(mod1)
	r.Register(mod2)
	if err := r.InitAll(); err != nil {
		t.Errorf("InitAll() error = %v", err)
	}
}

func TestRegistry_InitAll_Error(t *testing.T) {
	r := NewRegistry()
	mod1 := &mockModule{name: "m1", initErr: fmt.Errorf("init failed")}
	mod2 := &mockModule{name: "m2"}
	r.Register(mod1)
	r.Register(mod2)
	err := r.InitAll()
	if err == nil {
		t.Fatal("expected error from InitAll")
	}
	if !strings.Contains(err.Error(), "init failed") {
		t.Errorf("error = %v", err)
	}
}

func TestRegistry_InitAll_NoModules(t *testing.T) {
	r := NewRegistry()
	if err := r.InitAll(); err != nil {
		t.Errorf("InitAll() error = %v", err)
	}
}
