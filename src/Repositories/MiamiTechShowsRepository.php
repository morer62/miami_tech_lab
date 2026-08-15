<?php

namespace App\Repositories;

use App\Utils\SiteContext;

final class MiamiTechShowsRepository
{
    public Connection $db;

    public function __construct(?Connection $db = null)
    {
        $this->db = $db ?? new Connection();
    }

    public function publishedShows(): array
    {
        $this->db->query("SELECT s.*,
            (SELECT COUNT(*) FROM mtl_show_episodes e WHERE e.show_id=s.id AND e.site_key=s.site_key AND e.status='PUBLISHED') episode_count,
            (SELECT MIN(r.starts_at) FROM mtl_show_recordings r WHERE r.show_id=s.id AND r.site_key=s.site_key AND r.starts_at >= NOW() AND r.status IN ('PLANNED','CONFIRMED')) next_recording_at
            FROM mtl_shows s WHERE s.site_key=:site_key AND s.status='PUBLISHED' AND (s.published_at IS NULL OR s.published_at <= NOW())
            ORDER BY COALESCE(s.published_at,s.created_at) DESC");
        $this->db->bind(':site_key', SiteContext::siteKey());
        return $this->db->fetchAll();
    }

    public function publishedShow(string $slug): object|bool
    {
        $this->db->query("SELECT * FROM mtl_shows WHERE site_key=:site_key AND slug=:slug AND status='PUBLISHED' AND (published_at IS NULL OR published_at <= NOW()) LIMIT 1");
        $this->db->bind(':site_key', SiteContext::siteKey());
        $this->db->bind(':slug', $slug);
        $show = $this->db->fetchOne();
        if (!$show) return false;
        $show->episodes = $this->children('mtl_show_episodes', $show->id, "status='PUBLISHED'", 'COALESCE(published_at,created_at) DESC');
        $show->guests = $this->children('mtl_show_guests', $show->id, 'is_published=1', 'name');
        $show->recordings = $this->children('mtl_show_recordings', $show->id, "starts_at>=NOW() AND status IN ('PLANNED','CONFIRMED')", 'starts_at');
        return $show;
    }

    public function adminData(): array
    {
        $site = SiteContext::siteKey();
        $result = [];
        foreach (['shows'=>'mtl_shows','episodes'=>'mtl_show_episodes','guests'=>'mtl_show_guests','recordings'=>'mtl_show_recordings'] as $key=>$table) {
            $order = $key === 'recordings' ? 'starts_at DESC' : 'id DESC';
            $this->db->query("SELECT * FROM {$table} WHERE site_key=:site_key ORDER BY {$order}");
            $this->db->bind(':site_key', $site);
            $result[$key] = $this->db->fetchAll();
        }
        return $result;
    }

    public function save(string $type, array $input, int $userId): void
    {
        $map = [
            'show'=>['mtl_shows',['slug','title','tagline','description','cover_image_url','status','published_at']],
            'episode'=>['mtl_show_episodes',['show_id','guest_id','slug','title','summary','episode_number','duration_seconds','media_url','thumbnail_url','transcript_text','transcript_status','status','published_at']],
            'guest'=>['mtl_show_guests',['show_id','name','role_title','company','bio','profile_url','photo_url','is_published']],
            'recording'=>['mtl_show_recordings',['show_id','episode_id','title','starts_at','ends_at','timezone','location_name','location_address','access_level','capacity','status','notes']],
        ];
        if (!isset($map[$type])) throw new \InvalidArgumentException('Unknown record type.');
        [$table,$fields] = $map[$type];
        $data=['site_key'=>SiteContext::siteKey()];
        foreach ($fields as $field) $data[$field] = (($input[$field] ?? '') === '') ? null : $input[$field];
        foreach (['published_at','starts_at','ends_at'] as $dateField) {
            if (isset($data[$dateField]) && is_string($data[$dateField])) $data[$dateField] = str_replace('T', ' ', $data[$dateField]);
        }
        if (in_array($type,['show','episode'],true)) {
            $data['slug'] = strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',(string)($data['slug'] ?: $data['title'])),'-'));
            if ($data['slug']==='') throw new \InvalidArgumentException('A valid slug is required.');
        }
        if (empty($data[$type==='guest'?'name':'title']) || ($type!=='show' && empty($data['show_id']))) throw new \InvalidArgumentException('Required fields are missing.');
        if ($type==='show') { $data['created_by']=$userId; $data['updated_by']=$userId; }
        $columns=array_keys($data); $quoted=implode(',',array_map(fn($v)=>"`$v`",$columns)); $params=implode(',',array_map(fn($v)=>":$v",$columns));
        $this->db->query("INSERT INTO {$table} ({$quoted}) VALUES ({$params})");
        foreach ($data as $key=>$value) $this->db->bind(':'.$key,$value);
        $this->db->execute();
    }

    private function children(string $table, int $showId, string $extra, string $order): array
    {
        $this->db->query("SELECT * FROM {$table} WHERE site_key=:site_key AND show_id=:show_id AND {$extra} ORDER BY {$order}");
        $this->db->bind(':site_key', SiteContext::siteKey()); $this->db->bind(':show_id',$showId);
        return $this->db->fetchAll();
    }
}
