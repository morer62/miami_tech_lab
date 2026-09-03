-- Tech Lab Miami Growth Hub foundation for the shared Ophyra database.
-- Safe to run repeatedly. It creates no VNV Events or Pasta Station content.
SET NAMES utf8mb4;

START TRANSACTION;

UPDATE growth_sites
SET site_name='Tech Lab Miami',
    brand_voice='Clear, practical, technically rigorous and locally grounded. Help South Florida founders and small-business operators make useful technology decisions without hype.',
    main_services=JSON_ARRAY(
      JSON_OBJECT('label','AI consulting','url','/services/ai-consulting/'),
      JSON_OBJECT('label','Business automation','url','/services/business-automation/'),
      JSON_OBJECT('label','Software strategy','url','/software/'),
      JSON_OBJECT('label','Technology education','url','/learn/')
    ),
    default_cta_label='Request Technology Support',
    default_cta_url='/support/',
    cloudinary_folder='ophyra-growth-hub/miamitechlab',
    public_base_url='https://miamitechlab.com',
    domain='miamitechlab.com',
    status='active'
WHERE id_owner=2 AND site_key='miamitechlab';

INSERT INTO cms_categories
  (id_owner,site_key,name,slug,description,applies_to_pages,applies_to_blog,applies_to_locations,is_active,content_origin,origin_site_key)
VALUES
  (2,'miamitechlab','AI for Business','ai-for-business','Practical AI decisions, adoption and responsible use for operators.',1,1,1,1,'miamitechlab','miamitechlab'),
  (2,'miamitechlab','Automation & Operations','automation-operations','Workflows, integrations and systems that reduce repetitive work.',1,1,1,1,'miamitechlab','miamitechlab'),
  (2,'miamitechlab','Software & Data','software-data','Software strategy, analytics, security and digital infrastructure.',1,1,1,1,'miamitechlab','miamitechlab'),
  (2,'miamitechlab','Founders & Community','founders-community','South Florida builders, lessons, events and community opportunities.',1,1,1,1,'miamitechlab','miamitechlab')
ON DUPLICATE KEY UPDATE
  name=VALUES(name),description=VALUES(description),applies_to_pages=1,
  applies_to_blog=1,applies_to_locations=1,is_active=1,
  content_origin='miamitechlab',origin_site_key='miamitechlab';

INSERT INTO cms_templates
  (id_owner,site_key,name,template_key,description,type,template_structure_json,css_text,metadata_json,status)
VALUES
  (2,'miamitechlab','Tech Lab Editorial','tech-lab-editorial','Long-form editorial layout for Tech Lab Miami insights.','blog',
   JSON_OBJECT('sections',JSON_ARRAY('hero','article','sources','related','cta')),
   '.tech-lab-editorial{--tlm-ink:#070b18;--tlm-cyan:#02d7ea;--tlm-violet:#6d4aff}.tech-lab-editorial article{max-width:820px;margin:auto;line-height:1.75}.tech-lab-editorial h2{color:var(--tlm-ink)}.tech-lab-editorial a{color:#5140c8}',
   JSON_OBJECT('brand','Tech Lab Miami','content_type','blog'),'ACTIVE'),
  (2,'miamitechlab','Tech Lab Location Service','tech-lab-location-service','Local service page with Service schema and city context.','location',
   JSON_OBJECT('sections',JSON_ARRAY('hero','local-context','services','process','faq','map','cta')),
   '.tech-lab-location-service{--tlm-ink:#070b18;--tlm-cyan:#02d7ea;--tlm-violet:#6d4aff}.tech-lab-location-service .location-hero{background:var(--tlm-ink);color:#fff}.tech-lab-location-service a{color:#5140c8}',
   JSON_OBJECT('brand','Tech Lab Miami','schema_type','Service','map','city-embed'),'ACTIVE'),
  (2,'miamitechlab','Tech Lab Service Landing','tech-lab-service-landing','Conversion-focused technology service page.','page',
   JSON_OBJECT('sections',JSON_ARRAY('hero','problem','outcomes','process','proof','faq','cta')),
   '.tech-lab-service-landing{--tlm-ink:#070b18;--tlm-cyan:#02d7ea;--tlm-violet:#6d4aff}.tech-lab-service-landing .service-hero{background:linear-gradient(135deg,var(--tlm-ink),#171345);color:#fff}.tech-lab-service-landing a{color:#5140c8}',
   JSON_OBJECT('brand','Tech Lab Miami','content_type','page'),'ACTIVE')
ON DUPLICATE KEY UPDATE
  site_key='miamitechlab',name=VALUES(name),description=VALUES(description),type=VALUES(type),
  template_structure_json=VALUES(template_structure_json),css_text=VALUES(css_text),metadata_json=VALUES(metadata_json),status='ACTIVE';

COMMIT;

SELECT site_key,name,slug,is_active FROM cms_categories WHERE id_owner=2 AND site_key='miamitechlab' ORDER BY name;
SELECT site_key,name,template_key,type,status FROM cms_templates WHERE id_owner=2 AND site_key='miamitechlab' ORDER BY name;
