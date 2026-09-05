<?php

namespace App\Services;

use App\Repositories\Connection;

final class TechLabMembershipService
{
    public const TENANT = 'miamitechlab';
    public const ROLE_LEVEL = 2;

    public function __construct(private ?Connection $db = null)
    {
        $this->db ??= new Connection();
    }

    public function membershipFor(int $userId): ?object
    {
        $this->db->query("SELECT * FROM ecosystem_memberships WHERE user_id=:user AND tenant_key=:tenant AND status='ACTIVE' LIMIT 1");
        $this->db->bind(':user', $userId);
        $this->db->bind(':tenant', self::TENANT);
        return $this->db->fetchOne() ?: null;
    }

    public function membershipsFor(int $userId): array
    {
        $this->db->query("SELECT * FROM ecosystem_memberships WHERE user_id=:user AND status='ACTIVE' ORDER BY last_selected_at DESC,joined_at");
        $this->db->bind(':user', $userId);
        return $this->db->fetchAll();
    }

    public function enroll(int $userId): object
    {
        $this->db->beginTransaction();
        try {
            $this->db->query("INSERT INTO ecosystem_memberships(user_id,tenant_key,role_level,status,joined_at) VALUES(:user,:tenant,:role,'ACTIVE',NOW()) ON DUPLICATE KEY UPDATE role_level=VALUES(role_level),status='ACTIVE',updated_at=NOW()");
            $this->db->bind(':user', $userId);
            $this->db->bind(':tenant', self::TENANT);
            $this->db->bind(':role', self::ROLE_LEVEL);
            $this->db->execute();

            $membership = $this->membershipFor($userId);
            if (!$membership) {
                throw new \RuntimeException('Tech Lab membership could not be created.');
            }

            $this->db->query("INSERT INTO ecosystem_entitlements(membership_id,user_id,product_key,source_tenant,granted_at,status) VALUES(:membership,:user,'ophyra',:tenant,NOW(),'GRANTED') ON DUPLICATE KEY UPDATE status=IF(status='REVOKED','GRANTED',status),updated_at=NOW()");
            $this->db->bind(':membership', (int)$membership->id);
            $this->db->bind(':user', $userId);
            $this->db->bind(':tenant', self::TENANT);
            $this->db->execute();
            $this->db->commit();
            return $membership;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function dashboardData(int $userId): array
    {
        $membership = $this->membershipFor($userId);
        if (!$membership) {
            throw new \RuntimeException('Tech Lab Miami membership required.');
        }

        $this->expireEntitlements((int)$membership->id);
        $this->db->query("SELECT s.*,e.status entitlement_status,e.activated_at,e.expires_at FROM ecosystem_software_registry s LEFT JOIN ecosystem_entitlements e ON e.membership_id=:membership AND e.product_key=s.product_key WHERE s.tenant_key=:tenant AND s.is_active=1 ORDER BY s.sort_order,s.id");
        $this->db->bind(':membership', (int)$membership->id);
        $this->db->bind(':tenant', self::TENANT);
        $software = $this->db->fetchAll();
        $noticeThreshold = new \DateTimeImmutable('+3 months');
        foreach ($software as $product) {
            $product->show_expiry = false;
            $product->days_remaining = null;
            if (!empty($product->expires_at)) {
                $expiry = new \DateTimeImmutable((string) $product->expires_at);
                $product->show_expiry = $expiry <= $noticeThreshold;
                $product->days_remaining = max(0, (int) (new \DateTimeImmutable('today'))->diff($expiry)->format('%r%a'));
            }
        }

        $recording = $this->one("SELECT r.*,s.title show_title FROM mtl_show_recordings r JOIN mtl_shows s ON s.id=r.show_id AND s.site_key=r.site_key WHERE r.site_key=:tenant AND r.starts_at>=NOW() AND r.status IN ('PLANNED','CONFIRMED') ORDER BY r.starts_at LIMIT 1", [':tenant'=>self::TENANT]);
        $episode = $this->one("SELECT e.*,s.title show_title FROM mtl_show_episodes e JOIN mtl_shows s ON s.id=e.show_id AND s.site_key=e.site_key WHERE e.site_key=:tenant AND e.status='PUBLISHED' AND (e.published_at IS NULL OR e.published_at<=NOW()) ORDER BY COALESCE(e.published_at,e.created_at) DESC LIMIT 1", [':tenant'=>self::TENANT]);

        $events = $this->all("SELECT ve.id,ve.name,ve.start_date,ve.end_date,v.address location,vet.ticket_sales_enabled,te.capacity,COALESCE(SUM(CASE WHEN r.status='GOING' THEN 1 ELSE 0 END),0) rsvp_count,MAX(CASE WHEN r.membership_id=:membership AND r.status='GOING' THEN 1 ELSE 0 END) is_registered FROM tech_lab_events te INNER JOIN venue_events ve ON ve.id=te.venue_event_id INNER JOIN venues v ON v.id=ve.venue_id AND v.user_id=2 LEFT JOIN venue_events_tickets vet ON vet.id_venue_event=ve.id LEFT JOIN tech_lab_event_rsvps r ON r.venue_event_id=ve.id WHERE te.tenant_key=:tenant AND te.status='PUBLISHED' AND ve.start_date>=NOW() GROUP BY ve.id,ve.name,ve.start_date,ve.end_date,v.address,vet.ticket_sales_enabled,te.capacity ORDER BY ve.start_date LIMIT 3", [':membership'=>(int)$membership->id,':tenant'=>self::TENANT]);

        $articles = $this->all("SELECT c.title,c.slug,c.excerpt,c.featured_image_url,cat.name category_name FROM cms_contents c LEFT JOIN cms_categories cat ON cat.id=c.id_cms_category LEFT JOIN tech_lab_featured_content f ON f.cms_content_id=c.id AND f.tenant_key=c.site_key AND f.is_active=1 WHERE c.id_owner=2 AND c.site_key=:tenant AND c.status='PUBLISHED' AND COALESCE(c.content_type,IF(c.type='post','blog',c.type))='blog' ORDER BY (f.id IS NOT NULL) DESC,f.sort_order,COALESCE(c.published_at,c.created_at) DESC LIMIT 3", [':tenant'=>self::TENANT]);
        $tickets = $this->all("SELECT ts.*,tt.name ticket_type_name,vet.id_venue_event venue_event_id,ve.name event_name,ve.start_date FROM ticket_sales ts JOIN ticket_types tt ON tt.id=ts.id_ticket_type JOIN venue_events_tickets vet ON vet.id=tt.id_venue_event_tickets JOIN venue_events ve ON ve.id=vet.id_venue_event WHERE LOWER(ts.buyer_email)=LOWER((SELECT email FROM users WHERE id=:user)) ORDER BY ts.created_at DESC LIMIT 20", [':user'=>$userId]);
        $requestDrafts = $this->all("SELECT request_type,current_step,payload_json FROM tech_lab_member_requests WHERE membership_id=:membership AND status='DRAFT' ORDER BY updated_at DESC", [':membership'=>(int)$membership->id]);

        return compact('membership','software','recording','episode','events','articles','tickets','requestDrafts');
    }

    public function rsvp(int $userId, int $eventId): void
    {
        $membership = $this->membershipFor($userId);
        if (!$membership) throw new \RuntimeException('Membership required.');
        $event = $this->one("SELECT ve.id,te.capacity,(SELECT COUNT(*) FROM tech_lab_event_rsvps rr WHERE rr.venue_event_id=ve.id AND rr.status='GOING') rsvp_count FROM tech_lab_events te INNER JOIN venue_events ve ON ve.id=te.venue_event_id INNER JOIN venues v ON v.id=ve.venue_id AND v.user_id=2 WHERE ve.id=:event AND te.tenant_key=:tenant AND te.status='PUBLISHED' AND ve.start_date>=NOW() LIMIT 1", [':event'=>$eventId,':tenant'=>self::TENANT]);
        if (!$event) throw new \RuntimeException('This event is not available.');
        $rsvpStatus = $event->capacity !== null && (int) $event->rsvp_count >= (int) $event->capacity ? 'WAITLISTED' : 'GOING';
        $this->db->query("INSERT INTO tech_lab_event_rsvps(membership_id,user_id,venue_event_id,status) VALUES(:membership,:user,:event,:status) ON DUPLICATE KEY UPDATE status=VALUES(status),updated_at=NOW()");
        foreach([':membership'=>(int)$membership->id,':user'=>$userId,':event'=>$eventId,':status'=>$rsvpStatus] as $key=>$value) $this->db->bind($key,$value);
        $this->db->execute();
    }

    public function saveRequest(int $userId, string $type, int $step, array $payload, bool $submit): void
    {
        $membership = $this->membershipFor($userId);
        $type = strtoupper($type);
        if (!$membership || !in_array($type,['GUEST','SPONSOR','CONSULTANT'],true)) throw new \RuntimeException('Invalid request.');
        $status = $submit ? 'SUBMITTED' : 'DRAFT';
        $draft=$this->one("SELECT id FROM tech_lab_member_requests WHERE membership_id=:membership AND request_type=:type AND status='DRAFT' ORDER BY id DESC LIMIT 1",[':membership'=>(int)$membership->id,':type'=>$type]);
        if($draft){
            $this->db->query("UPDATE tech_lab_member_requests SET current_step=:step,payload_json=:payload,status=:status,submitted_at=".($submit?'NOW()':'NULL').",updated_at=NOW() WHERE id=:id AND membership_id=:membership");
            foreach([':step'=>max(1,$step),':payload'=>json_encode($payload,JSON_UNESCAPED_SLASHES),':status'=>$status,':id'=>(int)$draft->id,':membership'=>(int)$membership->id] as $key=>$value)$this->db->bind($key,$value);
            $this->db->execute();
        }else{
            $this->db->query("INSERT INTO tech_lab_member_requests(membership_id,user_id,request_type,current_step,payload_json,status,submitted_at) VALUES(:membership,:user,:type,:step,:payload,:status,".($submit?'NOW()':'NULL').")");
            foreach([':membership'=>(int)$membership->id,':user'=>$userId,':type'=>$type,':step'=>max(1,$step),':payload'=>json_encode($payload,JSON_UNESCAPED_SLASHES),':status'=>$status] as $key=>$value)$this->db->bind($key,$value);
            $this->db->execute();
        }
    }

    public function saveToolResult(int $userId, string $tool, array $input, array $result): void
    {
        $membership=$this->membershipFor($userId);
        if(!$membership || !in_array($tool,['event-budget','space-capacity'],true)) throw new \RuntimeException('Invalid tool.');
        $this->db->query("INSERT INTO tech_lab_saved_tool_results(membership_id,user_id,tool_key,input_json,result_json) VALUES(:membership,:user,:tool,:input,:result)");
        foreach([':membership'=>(int)$membership->id,':user'=>$userId,':tool'=>$tool,':input'=>json_encode($input),':result'=>json_encode($result)] as $key=>$value)$this->db->bind($key,$value);
        $this->db->execute();
    }

    public function activateOphyra(int $userId): string
    {
        $membership=$this->membershipFor($userId);
        if(!$membership) throw new \RuntimeException('Membership required.');
        $this->db->beginTransaction();
        try {
            $workspace=$this->one("SELECT * FROM ecosystem_workspaces WHERE membership_id=:membership LIMIT 1",[':membership'=>(int)$membership->id]);
            if(!$workspace){
                $this->db->query("INSERT INTO ecosystem_workspaces(membership_id,owner_user_id,tenant_key,status) VALUES(:membership,:user,:tenant,'ACTIVE')");
                foreach([':membership'=>(int)$membership->id,':user'=>$userId,':tenant'=>self::TENANT] as $k=>$v)$this->db->bind($k,$v); $this->db->execute();
                $workspace=(object)['id'=>(int)$this->db->lastId()];
                $this->db->query("UPDATE ecosystem_memberships SET account_id=:account,last_selected_at=NOW() WHERE id=:membership");
                $this->db->bind(':account',(int)$workspace->id);$this->db->bind(':membership',(int)$membership->id);$this->db->execute();
                $this->db->query("UPDATE ecosystem_entitlements SET activated_at=NOW(),expires_at=DATE_ADD(NOW(),INTERVAL 12 MONTH),status='ACTIVE' WHERE membership_id=:membership AND product_key='ophyra' AND activated_at IS NULL");
                $this->db->bind(':membership',(int)$membership->id);$this->db->execute();
            }
            $raw=bin2hex(random_bytes(32));
            $this->db->query("INSERT INTO ecosystem_sso_tokens(token_hash,membership_id,user_id,account_id,audience,expires_at) VALUES(:hash,:membership,:user,:account,'ophyra',DATE_ADD(NOW(),INTERVAL 5 MINUTE))");
            foreach([':hash'=>hash('sha256',$raw),':membership'=>(int)$membership->id,':user'=>$userId,':account'=>(int)$workspace->id] as $k=>$v)$this->db->bind($k,$v);$this->db->execute();
            $this->db->commit();
            return rtrim($_ENV['OPHYRA_APP_URL'] ?? 'https://ophyra.com','/').'/ecosystem/sso?token='.urlencode($raw);
        } catch(\Throwable $e){$this->db->rollback();throw $e;}
    }

    private function expireEntitlements(int $membershipId): void
    {
        $this->db->query("UPDATE ecosystem_entitlements e JOIN ecosystem_workspaces w ON w.membership_id=e.membership_id SET e.status='EXPIRED',w.status='READ_ONLY' WHERE e.membership_id=:membership AND e.status='ACTIVE' AND e.expires_at<NOW()");
        $this->db->bind(':membership',$membershipId);$this->db->execute();
    }

    private function one(string $sql,array $params): ?object { $this->db->query($sql);foreach($params as $k=>$v)$this->db->bind($k,$v);return $this->db->fetchOne()?:null; }
    private function all(string $sql,array $params): array { $this->db->query($sql);foreach($params as $k=>$v)$this->db->bind($k,$v);return $this->db->fetchAll(); }
}
