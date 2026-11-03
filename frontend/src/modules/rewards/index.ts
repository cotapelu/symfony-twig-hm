// STUB MODULE — not implemented. Zero importers (app registry/tests/routes).
// Previous exports pointed at files that exist nowhere (state/, services/,
// contracts/, events/), plus a mis-pathed ui/components/Rewards.svelte
// (actual component: ui/RewardsCenter.svelte). Removed per Cycle 21 (dead
// scaffold; nothing invented per §5.6).
// Planned layout: ui/RewardsCenter.svelte + state/rewardsStore.ts +
// services/rewardsService.ts + contracts/ + events/
// See docs/AUTONOMOUS_TASK_QUEUE.md (P2 MISSING-MODULE debt).

// Registry imports this barrel (modules/index.ts) — an explicit empty export
// keeps it a valid module without inventing unimplemented surface.
export {};
