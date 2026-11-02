// +build ignore

package main

import (
	"fmt"
	"go/parser"
	"go/token"
	"os"
	"path/filepath"
	"strings"
)

// Module structure expected:
// modules/{ModuleName}/
//   ├── Api/           # Controllers, DTOs, HTTP handling
//   ├── Application/   # Services, Use Cases, business logic orchestration
//   ├── Domain/        # Entities, ValueObjects, Repository interfaces, Domain Events
//   └── Infrastructure/ # Database repos, external API clients, file storage

// Current structure in codebase:
// modules/{ModuleName}/
//   ├── handler/       # API layer
//   ├── service/       # Application layer
//   ├── internal/models/  # Domain layer
//   └── repository/    # Infrastructure layer

type ArchitectureChecker struct {
	rootDir string
	errors  []string
	warnings []string
}

func NewArchitectureChecker(rootDir string) *ArchitectureChecker {
	return &ArchitectureChecker{
		rootDir: rootDir,
		errors:  []string{},
		warnings: []string{},
	}
}

func (c *ArchitectureChecker) Check() error {
	modulesDir := filepath.Join(c.rootDir, "modules")
	entries, err := os.ReadDir(modulesDir)
	if err != nil {
		return fmt.Errorf("failed to read modules directory: %w", err)
	}

	for _, entry := range entries {
		if !entry.IsDir() {
			continue
		}
		moduleName := entry.Name()
		if moduleName == "SampleModule" || strings.HasSuffix(moduleName, "_legacy") {
			continue
		}
		c.checkModule(moduleName, filepath.Join(modulesDir, moduleName))
	}

	if len(c.errors) > 0 {
		fmt.Println("=== ARCHITECTURE VIOLATIONS ===")
		for _, err := range c.errors {
			fmt.Printf("ERROR: %s\n", err)
		}
	}
	
	if len(c.warnings) > 0 {
		fmt.Println("\n=== WARNINGS ===")
		for _, warn := range c.warnings {
			fmt.Printf("WARN: %s\n", warn)
		}
	}

	if len(c.errors) > 0 {
		return fmt.Errorf("found %d architecture violations", len(c.errors))
	}
	
	fmt.Println("\n✓ All modules comply with architecture rules")
	return nil
}

func (c *ArchitectureChecker) checkModule(moduleName, modulePath string) {
	// Check for required layers
	layers := map[string]string{
		"Api":            "handler",
		"Application":    "service",
		"Domain":         filepath.Join("internal", "models"),
		"Infrastructure": "repository",
	}

	hasLayers := make(map[string]bool)
	for layer, dir := range layers {
		layerPath := filepath.Join(modulePath, dir)
		if _, err := os.Stat(layerPath); err == nil {
			hasLayers[layer] = true
		}
	}

	// Check if module has at least some structure
	if len(hasLayers) == 0 {
		c.warnings = append(c.warnings, fmt.Sprintf("Module %s: no recognizable layer structure", moduleName))
		return
	}

	// Check imports within module
	c.checkModuleImports(moduleName, modulePath, hasLayers)
}

func (c *ArchitectureChecker) checkModuleImports(moduleName, modulePath string, hasLayers map[string]bool) {
	err := filepath.Walk(modulePath, func(path string, info os.FileInfo, err error) error {
		if err != nil {
			return nil // Continue walking
		}
		
		if info.IsDir() || !strings.HasSuffix(info.Name(), ".go") || strings.HasSuffix(info.Name(), "_test.go") {
			return nil
		}

		c.checkFileImports(moduleName, path, hasLayers)
		return nil
	})
	
	if err != nil {
		c.warnings = append(c.warnings, fmt.Sprintf("Module %s: error walking files: %v", moduleName, err))
	}
}

func (c *ArchitectureChecker) checkFileImports(moduleName, filePath string, hasLayers map[string]bool) {
	fset := token.NewFileSet()
	node, err := parser.ParseFile(fset, filePath, nil, parser.ImportsOnly)
	if err != nil {
		return // Skip files that can't be parsed
	}

	// Determine which layer this file belongs to
	relPath, _ := filepath.Rel(filepath.Join(c.rootDir, "modules", moduleName), filePath)
	fileLayer := c.getLayerFromPath(relPath)
	
	for _, imp := range node.Imports {
		importPath := strings.Trim(imp.Path.Value, `"`)
		
		// Check for cross-module imports (only allowed via shared packages)
		if strings.HasPrefix(importPath, "community.local/modules/") {
			c.checkCrossModuleImport(moduleName, fileLayer, importPath, filePath)
		}
		
		// Check layer boundary violations
		c.checkLayerBoundary(moduleName, fileLayer, importPath, filePath)
	}
}

func (c *ArchitectureChecker) getLayerFromPath(relPath string) string {
	parts := strings.Split(relPath, string(filepath.Separator))
	if len(parts) < 1 {
		return "Unknown"
	}
	
	switch parts[0] {
	case "handler":
		return "Api"
	case "service":
		return "Application"
	case "internal":
		if len(parts) > 1 && parts[1] == "models" {
			return "Domain"
		}
		return "Internal"
	case "repository":
		return "Infrastructure"
	case "Api":
		return "Api"
	case "Application":
		return "Application"
	case "Domain":
		return "Domain"
	case "Infrastructure":
		return "Infrastructure"
	case "Contracts":
		return "Domain" // Contracts are part of Domain layer
	case "entity":
		return "Domain" // entity is Domain layer
	case "models":
		return "Domain" // models at root is Domain layer
	default:
		// Check for facade.go at module root - it's a wiring file, not a layer
		if parts[0] == "facade.go" || strings.HasSuffix(parts[0], "_test.go") {
			return "Unknown"
		}
		return "Unknown"
	}
}

func (c *ArchitectureChecker) checkCrossModuleImport(moduleName, fileLayer, importPath, filePath string) {
	// Extract target module from import path
	// e.g., "community.local/modules/Banking/service" -> "Banking"
	parts := strings.Split(importPath, "/")
	if len(parts) < 3 {
		return
	}
	
	// Module name is at index 2 (community.local/modules/Banking/...)
	targetModule := parts[2]
	if targetModule == moduleName {
		return // Same module, allowed
	}
	
	// Cross-module imports should only be to shared/common packages or domain contracts
	if strings.Contains(importPath, "/shared/") || strings.Contains(importPath, "/common/") {
		return // Shared packages allowed
	}
	
	// Allow importing Domain layer of other modules (for contracts)
	if strings.Contains(importPath, "/internal/models/") || strings.Contains(importPath, "/domain/") {
		return // Domain imports allowed
	}
	
	c.errors = append(c.errors, 
		fmt.Sprintf("%s (%s): cross-module import to %s (import: %s)", 
			filePath, fileLayer, targetModule, importPath))
}

func (c *ArchitectureChecker) checkLayerBoundary(moduleName, fileLayer, importPath, filePath string) {
	if !strings.HasPrefix(importPath, "community.local/modules/") {
		return // External imports allowed
	}
	
	// Extract target layer from import path
	targetLayer := c.getLayerFromImportPath(importPath)
	if targetLayer == "Unknown" {
		return
	}
	
	// Check forbidden dependencies
	forbidden := c.getForbiddenDependencies(fileLayer)
	for _, f := range forbidden {
		if f == targetLayer {
			c.errors = append(c.errors,
				fmt.Sprintf("%s (%s): forbidden import of %s layer (import: %s)",
					filePath, fileLayer, targetLayer, importPath))
			break
		}
	}
}

func (c *ArchitectureChecker) getLayerFromImportPath(importPath string) string {
	if strings.Contains(importPath, "/handler/") {
		return "Api"
	}
	if strings.Contains(importPath, "/service/") || strings.Contains(importPath, "/usecase/") || strings.Contains(importPath, "/application/") {
		return "Application"
	}
	if strings.Contains(importPath, "/internal/models/") || strings.Contains(importPath, "/domain/") || strings.Contains(importPath, "/entity/") {
		return "Domain"
	}
	if strings.Contains(importPath, "/repository/") || strings.Contains(importPath, "/internal/") || strings.Contains(importPath, "/platform/") || strings.Contains(importPath, "/middleware/") {
		return "Infrastructure"
	}
	if strings.Contains(importPath, "/shared/") || strings.Contains(importPath, "/common/") {
		return "Shared"
	}
	return "Unknown"
}

func (c *ArchitectureChecker) getForbiddenDependencies(fromLayer string) []string {
	switch fromLayer {
	case "Api":
		// Api can import: Application, Domain, Infrastructure, Shared
		// Cannot import: Api (self)
		return []string{"Api"}
	case "Application":
		// Application can import: Domain, Infrastructure, Shared
		// Cannot import: Application (self), Api
		return []string{"Application", "Api"}
	case "Domain":
		// Domain can import: Domain, Shared
		// Cannot import: Application, Infrastructure, Api
		return []string{"Application", "Infrastructure", "Api"}
	case "Infrastructure":
		// Infrastructure can import: Domain, Shared
		// Cannot import: Application, Api
		return []string{"Application", "Api"}
	case "Shared":
		// Shared can import: Shared, Domain
		// Cannot import: Application, Infrastructure, Api
		return []string{"Application", "Infrastructure", "Api"}
	default:
		return []string{}
	}
}

func main() {
	rootDir := "/home/quangtynu/Qcoder/symfony-twig-hm/go"
	if len(os.Args) > 1 {
		rootDir = os.Args[1]
	}
	
	checker := NewArchitectureChecker(rootDir)
	if err := checker.Check(); err != nil {
		fmt.Printf("\n✗ Architecture check failed: %v\n", err)
		os.Exit(1)
	}
}