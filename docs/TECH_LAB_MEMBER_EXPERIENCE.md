# Tech Lab Miami member experience

## Product contract

- A person has one `users` identity and can hold multiple tenant memberships in `ecosystem_memberships`.
- Joining Tech Lab Miami creates an active `miamitechlab` membership with role level 2. The legacy `users.level` is retained for compatibility, but tenant authorization is decided by the membership record.
- Membership grants Ophyra access without starting the clock. The workspace is provisioned lazily on the first activation and receives 12 months from that moment.
- Expired Ophyra access becomes read-only; data is not deleted. Renewal reminders are intentionally shown only in the last three months.
- Tech Lab member events are an explicit publication layer over Level 1 `venue_events`. Only events belonging to owner 2 venues can enter `tech_lab_events`; VNV Events inventory never appears automatically.
- Ticket checkout continues to use the existing ticket engine. Authenticated Tech Lab members are restricted to explicitly published Tech Lab events.
- Blog articles, recordings, software, requests and saved tool results are read from their canonical tables. Empty dashboard blocks collapse instead of displaying invented content.
- The event calculators inherited from the VNV codebase require an authenticated Tech Lab membership. They are not public routes on this site.

## Administration

Level 1 manages member publication and intake at `/panel/miami-tech-lab/member-experience`:

- publish or withdraw a future event and set RSVP capacity;
- move guest, sponsor and consultant requests through the review queue;
- select and order featured articles;
- inspect membership and Ophyra entitlement state.

The lifecycle command is:

```bash
php src/cron/tech-lab-license-reminders.php
```

Schedule it once daily. It expires elapsed licenses, moves workspaces to read-only and sends the 90-, 30- and 7-day notices once.

## Deployment and verification

Run `db/20260905_tech_lab_member_ecosystem.sql` once against the shared application database after deploying both repositories. It is safe to rerun.

Local or production-safe structural checks:

```bash
php tools/verify-tech-lab-member-ecosystem.php
php tools/verify-tech-lab-integration.php
```

Use `--exercise` only in a non-production environment. It creates and removes a reversible membership fixture to test enrollment, dashboard queries, lazy provisioning and SSO token generation.
