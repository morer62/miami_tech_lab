# Tech Lab Miami — authoritative repository scope

This repository serves **Tech Lab Miami** at `techlabmiami.com`. This file overrides inherited brand guidance under `docs/`.

- Public brand: `Tech Lab Miami` (not Miami Tech Lab, VNV Events, Avomeal or Ophyra).
- Content scope: `id_owner=2`, `site_key=miamitechlab`.
- Public Growth Hub content, media and agents must read and write only within that scope.
- Public signup creates customer/member accounts (`level=5`) only.
- VNV store, products, event-service pages, venues, vendors, forums and legacy VNV location records are not public here.
- Research and internal links come from this site's homepage, public pages, CMS content, sitemap and configured services.
- Blog and location routes expose only published `miamitechlab` records.
- Canonicals, schema, Open Graph data and sitemaps use `https://techlabmiami.com` and contain only reachable routes.

The database and Level 1 platform are shared. Ownership does not grant cross-brand visibility: preserve `(id_owner, site_key)` in every query. Test homepage, authentication, blog, locations, sitemap and blocked inherited routes before handoff.
