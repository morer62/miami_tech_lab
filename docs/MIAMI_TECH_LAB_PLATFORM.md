# Miami Tech Lab Platform

## Product position

Miami Tech Lab is a serious operating platform, not a generic social community. Its loop is: discover a show or event, meet credible people, use a practical resource or application, create a free account, and return for new activity.

Public language is English. The production domain is `miamitechlab.com`.

## Public architecture

- `/` — live homepage and primary conversion path.
- `/now` — current activity command center.
- `/shows` — show properties, episodes, guests and recording schedule.
- `/shows/local-tech-lab` — practical AI and technology for local business.
- `/events` — upcoming, calendar, networking, workshops, past events and galleries.
- `/software` — app marketplace, releases and access levels.
- `/community` — members, companies, discussions and opportunities.
- `/learn` — courses, tutorials, workshops and guides.
- `/resources` — prompts, templates, reports, calculators and tools.
- `/news` — original platform announcements and recaps.
- `/people` — founders, team and advisors.
- `/directory`, `/benefits`, `/partners`, `/speakers`, `/membership`, `/media`.

Nested paths are routed through the Miami Tech Lab content engine and remain clean/indexable. Ophyra content must use the `miamitechlab` site key so another property can never leak into this site.

## Identity and data

The login identity and database are shared with the Ophyra ecosystem. A known email can authenticate across properties; authorization still depends on roles and permissions. Do not duplicate passwords or create a second identity table.

The operational estimate, invoice, payment and project modules remain available because Miami Tech Lab may issue consulting proposals. They must be progressively re-skinned, but their accounting behavior must not be forked without a business requirement.

## Brand system

- Midnight: `#070b18`
- Deep navy: `#0c1230`
- Electric cyan: `#02d7ea`
- Ultraviolet: `#6d4aff`
- Coral signal: `#ff6d5e`
- Display type: Manrope
- Body type: DM Sans

The emblem is stored at `public/assets/miami-tech-lab/mark.png`. It was generated as an original minimalist M/network symbol, then converted to transparent PNG. Text remains an HTML wordmark for accessibility and sharp rendering.

## Environment

```env
APP_URL=https://miamitechlab.com
APP_NAME="Miami Tech Lab"
APP_DOMAIN=miamitechlab.com
SITE_KEY=miamitechlab
SITE_NAME="Miami Tech Lab"
SITE_PUBLIC_BASE_URL=https://miamitechlab.com
PUBLIC_BASE_URL=https://miamitechlab.com
OPHYRA_GROWTH_SITE_KEY=miamitechlab
AI_CONTENT_SITE_KEY=miamitechlab
```

Database credentials and owner IDs remain the shared production values. Never commit `.env`.

## Launch checklist

- Configure the production document root/rewrite rules.
- Run `composer install --no-dev --optimize-autoloader`.
- Configure the environment above and shared authentication secrets.
- Add the `miamitechlab` site in Ophyra Growth Hub before publishing CMS content.
- Verify sign-in, sign-up, password reset and role routing.
- Replace shared transactional email templates with Miami Tech Lab branding where applicable.
- Add real event dates, show episodes, member profiles and partner records through the CMS/admin layer.
- Configure analytics using a Miami Tech Lab-specific property/container.
- Test responsive navigation, metadata, canonical URLs, structured data and 404 behavior.

## Continuation prompt

Continue development in the `miami_tech_lab` repository. Read `README.md`, `docs/MIAMI_TECH_LAB_PLATFORM.md`, `CLAUDE.md`, and all other documentation before editing. Preserve shared authentication/database compatibility with Ophyra and VNV, but keep `SITE_KEY`, content, public metadata and branding isolated to Miami Tech Lab. Public UI and copy must remain English. Begin by auditing the current route, responsive and authentication tests; then build real CMS-backed models and admin workflows for shows, episodes, events/RSVPs, software/releases/licenses, members/companies, resources, news, benefits, partners and speakers. Do not commit uploads, renders, cache, logs, secrets or `.env`. Validate every completed vertical slice in Chromium at desktop and mobile widths before committing.
