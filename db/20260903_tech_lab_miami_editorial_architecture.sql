-- Tech Lab Miami editorial architecture and P0 excerpt cleanup.
-- Strict scope: owner 2, site_key miamitechlab. Safe to rerun.
SET NAMES utf8mb4;
DELIMITER $$
DROP PROCEDURE IF EXISTS tech_lab_editorial_preflight$$
CREATE PROCEDURE tech_lab_editorial_preflight()
BEGIN
  IF DATABASE() <> 'ophyra_vnv_venue' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Wrong database: expected ophyra_vnv_venue';
  END IF;
  IF NOT EXISTS (SELECT 1 FROM growth_sites WHERE id_owner=2 AND site_key='miamitechlab') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Missing Tech Lab Miami growth site';
  END IF;
END$$
CALL tech_lab_editorial_preflight()$$
DROP PROCEDURE tech_lab_editorial_preflight$$
DELIMITER ;

DELIMITER $$
DROP PROCEDURE IF EXISTS tech_lab_rename_software_category$$
CREATE PROCEDURE tech_lab_rename_software_category()
BEGIN
  DECLARE old_id INT DEFAULT NULL;
  DECLARE new_id INT DEFAULT NULL;
  SELECT id INTO old_id FROM cms_categories WHERE id_owner=2 AND site_key='miamitechlab' AND slug='software-data' LIMIT 1;
  SELECT id INTO new_id FROM cms_categories WHERE id_owner=2 AND site_key='miamitechlab' AND slug='software-tools' LIMIT 1;
  IF old_id IS NOT NULL AND new_id IS NOT NULL THEN
    UPDATE cms_contents SET id_cms_category=new_id,updated_by=2 WHERE id_owner=2 AND site_key='miamitechlab' AND id_cms_category=old_id;
    UPDATE cms_categories SET is_active=0,updated_by=2 WHERE id=old_id AND id_owner=2 AND site_key='miamitechlab';
  ELSEIF old_id IS NOT NULL THEN
    UPDATE cms_categories SET name='Software & Tools',slug='software-tools',updated_by=2 WHERE id=old_id AND id_owner=2 AND site_key='miamitechlab';
  END IF;
END$$
CALL tech_lab_rename_software_category()$$
DROP PROCEDURE tech_lab_rename_software_category$$
DELIMITER ;

START TRANSACTION;

INSERT INTO cms_categories
  (id_owner,site_key,name,slug,description,applies_to_pages,applies_to_blog,applies_to_locations,is_active,content_origin,origin_site_key,created_by,updated_by,origin_metadata_json)
VALUES
  (2,'miamitechlab','AI for Business','ai-for-business','Practical guidance for using artificial intelligence inside real South Florida businesses. This section covers opportunity assessment, responsible adoption, prompt and knowledge workflows, customer operations, marketing support and the decisions teams should make before buying or building an AI system. Articles begin with an operating problem rather than a fashionable tool. They explain who owns the process, what information is required, where human review belongs and how a team can evaluate usefulness without inventing results. The audience includes founders, managers, service-business operators and technical leaders across Broward, Miami-Dade and Palm Beach. Tech Lab Miami uses this category to separate durable methods from hype and to connect public education, original conversations and software experiments. Claims, examples and recommendations must remain verifiable, current and clear about their limits.',0,1,0,1,'miamitechlab','miamitechlab',2,2,'{"architecture":"2026-09"}'),
  (2,'miamitechlab','Automation & Operations','automation-operations','A field guide to reducing repetitive work without creating fragile operations. This category explores workflow mapping, integrations, approvals, handoffs, documentation, measurement and the human judgment that should remain inside an automated process. The emphasis is not automation for its own sake. Each article should help a South Florida operator identify the bottleneck, understand the systems involved, decide what can safely change and define a realistic way to verify the result. Topics may include lead intake, scheduling, customer communication, reporting, content operations and internal knowledge. Examples must protect private business information and avoid unsupported savings claims. Together, these articles form an operational reference for founders, managers and technical teams that want simpler systems, clearer ownership and technology that supports the work instead of obscuring it.',0,1,0,1,'miamitechlab','miamitechlab',2,2,'{"architecture":"2026-09"}'),
  (2,'miamitechlab','Software & Tools','software-tools','Independent reviews, implementation notes and operating guidance for software used by South Florida teams. This section examines what a tool actually helps a business accomplish, the workflow it changes, the people who need to adopt it and the tradeoffs that matter after the demo. Coverage includes business software, data systems, cybersecurity, analytics, integrations and focused applications built by Tech Lab Miami. Articles favor practical evaluation over trend summaries: readers should understand the problem, the setup, the limits and the next useful action. When a release belongs to Tech Lab Miami, the relationship is stated clearly. When evidence is still developing, the article avoids unsupported performance claims. The goal is a durable library for founders, operators and technical leaders choosing systems that must work in real organizations across Broward, Miami-Dade and Palm Beach.',0,1,0,1,'miamitechlab','miamitechlab',2,2,'{"architecture":"2026-09"}'),
  (2,'miamitechlab','Service Business Tech','service-business-tech','Technology guidance for the local service businesses that keep South Florida moving. This category focuses on the practical digital systems behind professional services, hospitality, home services, events, health and wellness, creative work and other appointment- or project-based companies. Articles may examine lead response, estimates, scheduling, payments, customer follow-up, reviews, local discovery, data quality and team coordination. The purpose is to translate technical choices into operating consequences without pretending that every company needs the same stack. Content should account for small teams, mobile work, seasonal demand and the realities of serving customers across Broward, Miami-Dade and Palm Beach. Recommendations remain vendor-aware, evidence-based and honest about implementation effort. Readers should leave with a clearer question to ask, a workflow to inspect or a manageable next step.',0,1,0,1,'miamitechlab','miamitechlab',2,2,'{"architecture":"2026-09"}'),
  (2,'miamitechlab','Field Notes','field-notes','Documented lessons from building, testing, recording and convening through Tech Lab Miami. Field Notes turn real activity into a useful public record: what was attempted, what could be verified, what changed and what remains unresolved. The category can include show takeaways, event recaps, software release notes, studio observations and operational experiments, but it must not manufacture attendance, customer outcomes, partnerships or performance metrics. Names and organizations appear only when they are public and authorized. The writing should be specific enough to help another builder while protecting private information and avoiding promotional exaggeration. Over time, this section provides continuity between original shows, community programs, software work and the broader South Florida technology ecosystem. It is where readers can follow the work as it develops rather than seeing only polished announcements.',0,1,0,1,'miamitechlab','miamitechlab',2,2,'{"architecture":"2026-09"}'),
  (2,'miamitechlab','Founders & Community','founders-community','Stories, guides and conversations about the people building technology and businesses across South Florida. This category covers founder decisions, technical leadership, community infrastructure, collaboration, events and the practical work of creating stronger connections between Broward, Miami-Dade and Palm Beach. It is not a directory of unverified claims or a stream of generic profiles. Every feature should have a clear reason to exist, public facts that can be checked and lessons readers can apply. Community reporting may connect guests, shows, releases and programs while respecting consent and accurately describing Tech Lab Miami’s role. The goal is to make useful local work easier to discover, preserve institutional memory and show how relationships translate into learning and opportunity without overstating impact.',0,1,0,1,'miamitechlab','miamitechlab',2,2,'{"architecture":"2026-09"}')
ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),applies_to_pages=0,applies_to_blog=1,applies_to_locations=0,is_active=1,content_origin='miamitechlab',origin_site_key='miamitechlab',updated_by=2,origin_metadata_json=VALUES(origin_metadata_json);

UPDATE cms_contents
SET excerpt = CASE
  WHEN meta_description IS NOT NULL AND TRIM(meta_description)<>'' AND LOWER(meta_description) NOT LIKE '%article direction%'
    THEN LEFT(TRIM(meta_description),155)
  ELSE NULL
END,
updated_by=2
WHERE id_owner=2 AND site_key='miamitechlab'
  AND LOWER(COALESCE(excerpt,'')) LIKE '%use this manually entered title as the article direction%';

COMMIT;

SELECT slug,name,is_active FROM cms_categories WHERE id_owner=2 AND site_key='miamitechlab' AND applies_to_blog=1 ORDER BY slug;
SELECT COUNT(*) AS remaining_placeholder_excerpts FROM cms_contents WHERE id_owner=2 AND site_key='miamitechlab' AND LOWER(COALESCE(excerpt,'')) LIKE '%article direction%';
