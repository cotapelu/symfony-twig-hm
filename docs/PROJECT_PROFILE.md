# Project Profile

**Generated**: 2026-11-01T14:53:18.636Z
**Source**: Auto-detected at startup

## Size
- Category: **SMALL**
- Evidence:
  - LOC: 0
  - Files: 0
  - Tests: 0
  - Dependencies: 0

## Risk
- Category: **LOW**
- Evidence:
  - High-risk keywords: 0
  - Medium-risk keywords: 0
  - Low-risk keywords: 849


## Deployment
- Category: **CLOUD**
- Cloud provider: detected



- Cost Optimization: Required

## Team
- Category: **SOLO**
- Evidence:
  - Authors (30d): 1
  - Commits (30d): 4
  - PR estimate: 0
  - CODEOWNERS: No
- Process: Self-merge allowed

## Applied Thresholds

| Metric | Standard | Adjusted | Reason |
|--------|----------|----------|--------|
| Max Function Lines | 20 | 25 | small size |
| Min Coverage | 80% | 75% | small size |
| Complexity Limit | 10 | 10 | Default |
| Security Scan | HIGH | HIGH | low risk |
| Performance Gates | Required | No | low risk |
| Compliance Matrix | Full | No | low risk |
| PR Reviews Required | 1 | 0 | solo team |
| SLA Initial Review | <24h | <168h | solo team |

## Manual Override

To override auto-detected profile, create `docs/PROJECT_PROFILE_OVERRIDE.md`:

```yaml
size: large
risk: high
deployment: cloud
team: large-org
```

Agent will use override values on next startup.

## Notes

- Profile auto-updates on agent startup (if evidence changes significantly)
- Adjustments applied to quality gates, testing pipeline, compliance scanning
- For questions, see GOAL.md §20.2 and docs/PROJECT_PROFILE.md
