<?php $p = basename($_SERVER['PHP_SELF']); ?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <title>Esther Aroma — Staff Portal</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    body { font-family:'Kanit',sans-serif; }

    /* ── Sidebar custom ── */
    .sidebar {
      width: 240px;
      min-width: 240px;
      height: 100vh;
      position: sticky;
      top: 0;
      display: flex;
      flex-direction: column;
      background: #fff;
      border-right: 1px solid var(--tblr-border-color);
      transition: width .22s ease, min-width .22s ease;
      overflow: hidden;
      z-index: 100;
    }
    body.sb-collapsed .sidebar {
      width: 56px;
      min-width: 56px;
    }
    body.sb-collapsed .sb-label,
    body.sb-collapsed .nav-cat,
    body.sb-collapsed .sb-sub { display: none !important; }
    body.sb-collapsed .sidebar-footer-name { display: none !important; }

    .sidebar-scroll { 
      flex: 1; 
      overflow-y: auto; 
      overflow-x: hidden;
      padding: .5rem 0 .5rem;
    }
    .sidebar-footer {
      border-top: 1px solid var(--tblr-border-color);
      padding: .55rem .75rem;
      display: flex;
      align-items: center;
      gap: .6rem;
      flex-shrink: 0;
    }
    .sidebar-footer-name {
      flex: 1;
      min-width: 0;
      overflow: hidden;
    }

    /* nav items */
    .nav-cat {
      padding: .55rem 1rem .15rem;
      font-size: .6rem;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: #94a3b8;
    }
    .sb-item {
      display: flex;
      align-items: center;
      gap: .55rem;
      padding: .42rem .85rem;
      color: var(--tblr-body-color);
      text-decoration: none;
      border-radius: 6px;
      margin: .05rem .45rem;
      transition: background .15s, color .15s;
      white-space: nowrap;
    }
    .sb-item:hover { background: rgba(0,0,0,.05); color: inherit; }
    .sb-item.active {
      background: rgba(var(--tblr-primary-rgb),.12);
      color: var(--tblr-primary);
      font-weight: 500;
    }
    .sb-item.disabled { opacity: .40; pointer-events: none; }
    .sb-icon { flex-shrink: 0; width: 22px; text-align: center; }
    .sb-sub { font-size: .67rem; color: #94a3b8; display: block; line-height: 1.1; margin-top: 1px; }

    /* layout */
    .page-root { display: flex; min-height: 100vh; }
    .page-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }

    /* Mobile overlay */
    @media (max-width: 991px) {
      .sidebar {
        position: fixed;
        top: 0; left: 0;
        transform: translateX(-100%);
        z-index: 1050;
        box-shadow: 4px 0 20px rgba(0,0,0,.15);
      }
      body.sb-open .sidebar { transform: translateX(0); }
      .sb-backdrop {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,.35);
        z-index: 1049;
      }
      body.sb-open .sb-backdrop { display: block; }
      body.sb-collapsed .sidebar { width: 240px; min-width: 240px; }
      body.sb-collapsed .sb-label,
      body.sb-collapsed .nav-cat,
      body.sb-collapsed .sb-sub { display: block !important; }
    }

    /* ── Spotify-Style Player Bar ── */
    .bot-player-bar {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      height: 72px;
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-top: 1px solid rgba(0,0,0,0.08);
      z-index: 2000;
      display: flex;
      align-items: center;
      box-shadow: 0 -4px 12px rgba(0,0,0,0.04);
    }
    .player-icon {
      width: 40px; height: 40px;
      background: var(--tblr-primary);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 1.2rem;
      flex-shrink: 0;
    }
    .player-text { min-width: 0; flex: 1; }
    .player-title { font-weight: 600; font-size: .85rem; line-height: 1.2; }
    .player-subtitle { font-size: .72rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    .pulse-glow { position: relative; }
    .pulse-glow::after {
      content: '';
      position: absolute;
      inset: -4px;
      border-radius: 10px;
      background: var(--tblr-primary);
      opacity: 0.2;
      animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse-ring {
      0% { transform: scale(0.95); opacity: 0.4; }
      100% { transform: scale(1.15); opacity: 0; }
    }

    /* Make room for player */
    body { padding-bottom: 72px !important; }
    #sidebar { height: calc(100vh - 72px); }
  </style>
</head>
<body class="antialiased">

<!-- Mobile overlay -->
<div class="sb-backdrop" onclick="closeSidebar()"></div>

<div class="page-root">

  <!-- ═══════════════ SIDEBAR ═══════════════ -->
  <div class="sidebar" id="sidebar">

    <!-- Brand + collapse btn -->
    <div class="d-flex align-items-center px-3 py-3 border-bottom gap-2">
      <a href="index.php" class="text-decoration-none d-flex align-items-center gap-1" style="min-width:0;overflow:hidden;flex:1;">
        <span class="fw-bold text-primary fs-6 flex-shrink-0">E</span>
        <span class="fw-bold text-primary sb-label" style="font-size:1rem;white-space:nowrap;">sther</span>
        <span class="text-muted sb-label ms-1" style="font-size:.78rem;white-space:nowrap;">AI Staff</span>
      </a>
      <button class="btn btn-sm btn-ghost-secondary flex-shrink-0 d-none d-lg-inline-flex p-1" onclick="toggleSidebar()" title="Collapse / Expand sidebar">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>

    <!-- Scrollable nav -->
    <div class="sidebar-scroll">

      <!-- Dashboard -->
      <a href="index.php" class="sb-item <?= $p=='index.php'?'active':'' ?>">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="5 12 3 12 12 3 21 12 19 12"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/><path d="M9 21v-6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v6"/></svg></span>
        <span class="sb-label">Dashboard</span>
      </a>

      <!-- ── หมวด 1: รายชื่อลูกค้าเป้าหมาย ── -->
      <div class="nav-cat">🎯 หมวด 1 — รายชื่อลูกค้าเป้าหมาย</div>

      <a href="thai_page_leads.php" class="sb-item <?= $p=='thai_page_leads.php'?'active':'' ?>">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4" /><path d="M5 21l0 -10.15" /><path d="M19 21l0 -10.15" /><path d="M9 21l0 -4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2l0 4" /></svg></span>
        <span class="sb-label">เพจร้านนวดทั่วไทย<span class="sb-sub">คัดกรองเพจ Facebook ทั่วไทย</span></span>
      </a>

      <a href="fb_groups.php" class="sb-item <?= $p=='fb_groups.php'?'active':'' ?>">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M22 4.01c-1 .49-1.98 .689-3 .99c-1.121-1.265-2.783-1.335-4.38-.737s-2.643 2.06-2.62 3.737v1c-3.245.083-6.135-1.395-8-4c0 0-4.182 7.433 4 11c-1.872 1.247-3.739 2.088-6 2c3.308 1.803 6.913 2.423 10.034 1.517c3.58-1.04 6.522-3.723 7.651-7.742a13.84 13.84 0 0 0 .497-3.753c-.002-.249 1.51-2.772 1.818-4.013z"/></svg></span>
        <span class="sb-label">กลุ่มเฟชบุ๊คทั่วโลก<span class="sb-sub">บริหารกลุ่ม Facebook Worldwide</span></span>
      </a>

      <a href="leads.php" class="sb-item <?= in_array($p, ['leads.php','lead_detail.php','cities.php','import_cities.php']) ? 'active' : '' ?>">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="11" r="3"/><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"/></svg></span>
        <span class="sb-label">ร้านนวดทั่วโลกจากแผนที่<span class="sb-sub">ค้นหาจาก Google Maps</span></span>
      </a>

      <!-- ── หมวด 2: Creative & Content ── -->
      <div class="nav-cat">🎨 หมวด 2 — Creative &amp; Content</div>

      <a href="creative_manage.php" class="sb-item <?= $p=='creative_manage.php'?'active':'' ?>">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4h16v16H4z"/><path d="M4 15l4 -4a3 5 0 0 1 3 0l5 5"/><path d="M14 14l1 -1a3 5 0 0 1 3 0l2 2"/><circle cx="9" cy="9" r="2"/></svg></span>
        <span class="sb-label">คลังเนื้อหา &amp; รูปภาพ<span class="sb-sub">จัดการ Content Pool</span></span>
      </a>

      <a href="post_world_groups.php" class="sb-item <?= $p=='post_world_groups.php'?'active':'' ?>">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M22 4.01c-1 .49-1.98 .689-3 .99c-1.121-1.265-2.783-1.335-4.38-.737s-2.643 2.06-2.62 3.737v1c-3.245.083-6.135-1.395-8-4c0 0-4.182 7.433 4 11c-1.872 1.247-3.739 2.088-6 2c3.308 1.803 6.913 2.423 10.034 1.517c3.58-1.04 6.522-3.723 7.651-7.742a13.84 13.84 0 0 0 .497-3.753c-.002-.249 1.51-2.772 1.818-4.013z"/></svg></span>
        <span class="sb-label">โพสต์กลุ่มขายของทั่วโลก<span class="sb-sub">Worldwide FB Groups</span></span>
      </a>

      <a href="post_campaigns.php" class="sb-item <?= $p=='post_campaigns.php'?'active':'' ?>">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" class="icon text-primary" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M3 12h6" /><path d="M15 12h6" /><path d="M12 3v6" /><path d="M12 15v6" /></svg></span>
        <span class="sb-label text-primary font-weight-bold">ระบบแคมเปญโฆษณา<span class="sb-sub">FB Campaign / A/B Test</span></span>
      </a>

      <a href="#" class="sb-item disabled">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/><path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/><line x1="3" y1="6" x2="3" y2="19"/><line x1="12" y1="6" x2="12" y2="19"/><line x1="21" y1="6" x2="21" y2="19"/></svg></span>
        <span class="sb-label">โพสต์กลุ่มขายของทั่วไทย<span class="sb-sub">Coming Soon</span></span>
      </a>

      <a href="#" class="sb-item disabled">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4" /><path d="M5 21l0 -10.15" /><path d="M19 21l0 -10.15" /><path d="M9 21l0 -4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2l0 4" /></svg></span>
        <span class="sb-label">โพสต์เพจทั่วไทย<span class="sb-sub">#ยักษโพธิ์อโรม่า · Coming Soon</span></span>
      </a>

      <!-- ── หมวด 3: Retention & CRM ── -->
      <div class="nav-cat">🤝 หมวด 3 — Retention &amp; CRM</div>

      <a href="#" class="sb-item disabled">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19 8.268a2 2 0 0 1 1 1.732v8a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h3"/><rect x="8" y="4" width="8" height="4" rx="1"/><path d="M11 12h2"/><path d="M11 16h2"/></svg></span>
        <span class="sb-label">CRM &amp; Loyalty<span class="sb-sub">Coming Soon</span></span>
      </a>

      <a href="#" class="sb-item disabled">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="6" width="18" height="14" rx="2"/><polyline points="3 6 12 13 21 6"/></svg></span>
        <span class="sb-label">Automated Follow-up<span class="sb-sub">Coming Soon</span></span>
      </a>

      <a href="#" class="sb-item disabled">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 20h-7a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v4"/><path d="M16 19h6"/><path d="M19 16l3 3l-3 3"/></svg></span>
        <span class="sb-label">Loyalty Tiering<span class="sb-sub">Coming Soon</span></span>
      </a>

      <a href="#" class="sb-item disabled">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="4"/><path d="M3 12h1m8 -9v1m8 8h1m-15.4 -6.4l.7 .7m12.1 -.7l-.7 .7"/></svg></span>
        <span class="sb-label">Feedback Analysis<span class="sb-sub">Coming Soon</span></span>
      </a>

      <!-- ── หมวด 4: ฝ่ายบุคคล & แอดมิน ── -->
      <div class="nav-cat">👔 หมวด 4 — ฝ่ายบุคคล &amp; แอดมิน</div>

      <a href="bot_operations.php" class="sb-item <?= $p=='bot_operations.php'?'active':'' ?>">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37a1.724 1.724 0 0 0 2.572 -1.065z" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg></span>
        <span class="sb-label">ทำเนียบพนักงานเอไอ<span class="sb-sub">ตรวจสอบสถานะการทำงาน</span></span>
      </a>

      <a href="bot_staff_manage.php" class="sb-item <?= $p=='bot_staff_manage.php'?'active':'' ?>">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h2.5" /><path d="M19.001 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M19.001 15.5v1.5" /><path d="M19.001 21v1.5" /><path d="M22.032 17.25l-1.299 .75" /><path d="M17.27 20l-1.3 .75" /><path d="M15.97 17.25l1.3 .75" /><path d="M20.733 20l1.3 .75" /></svg></span>
        <span class="sb-label">จัดการข้อมูลพนักงาน (HR)<span class="sb-sub">เพิ่ม แก้ไข ลบพนักงาน</span></span>
      </a>

      <a href="users.php" class="sb-item <?= $p=='users.php'?'active':'' ?>">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.85"/></svg></span>
        <span class="sb-label">แอดมินเข้าใช้ระบบ<span class="sb-sub">จัดการสิทธิ์ผู้ใช้งาน</span></span>
      </a>

      <a href="fb_settings.php" class="sb-item <?= $p=='fb_settings.php'?'active':'' ?>">
        <span class="sb-icon"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-settings-automation" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37a1.724 1.724 0 0 0 2.572 -1.065z" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg></span>
        <span class="sb-label text-primary fw-bold">ระบบตั้งค่าบอท<span class="sb-sub text-primary">Persona / Cookies / Keywords</span></span>
      </a>

    </div><!-- /.sidebar-scroll -->

    <!-- ── Footer always at bottom ── -->
    <div class="sidebar-footer">
      <span class="avatar avatar-sm bg-primary text-white flex-shrink-0">
        <?= strtoupper(substr($_SESSION['staff_name'] ?? 'U', 0, 1)) ?>
      </span>
      <div class="sidebar-footer-name">
        <div class="fw-medium text-truncate" style="font-size:.82rem"><?= htmlspecialchars($_SESSION['staff_name'] ?? 'User') ?></div>
        <div class="text-muted text-truncate" style="font-size:.67rem"><?= htmlspecialchars($_SESSION['staff_role'] ?? 'Staff') ?></div>
      </div>
      <a href="logout.php" class="btn btn-sm btn-ghost-danger flex-shrink-0 p-1" title="Logout">
        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0-2-2h-7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2v-2"/><path d="M7 12h14l-3-3m0 6l3-3"/></svg>
      </a>
    </div>

  </div><!-- /.sidebar -->

  <!-- ═══════════════ MAIN ═══════════════ -->
  <div class="page-main">

    <!-- Mobile topbar -->
    <div class="d-lg-none d-flex align-items-center px-3 py-2 border-bottom bg-white gap-2">
      <button class="btn btn-sm btn-ghost-secondary p-1" onclick="openSidebar()">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <span class="fw-bold text-primary">Esther</span><span class="text-muted small"> AI Staff</span>
    </div>

<!-- ═══════════════ SPOTIFY-STYLE PLAYER BAR ═══════════════ -->
<div class="bot-player-bar" id="bot-player" style="display: none;">
  <div class="container-fluid d-flex align-items-center gap-3">
    
    <!-- Left: Info -->
    <div class="d-flex align-items-center gap-3" style="width: 280px; min-width: 0;">
      <div class="player-icon pulse-glow" id="player-status-icon">🤖</div>
      <div class="player-text">
        <div class="player-title">AI Department 1</div>
        <div class="player-subtitle text-truncate" id="player-bot-action">Waiting for staff to start...</div>
      </div>
    </div>

    <!-- Center: Seek Bar (Progress) -->
    <div class="flex-grow-1 mx-lg-5 d-none d-sm-block">
      <div class="d-flex justify-content-between mb-1">
        <span class="badge bg-success-lt fw-bold" id="player-status-label" style="font-size: .6rem;">STAFF ONLINE</span>
        <span class="fw-bold text-primary small" id="player-bot-percent">0%</span>
      </div>
      <div class="progress" style="height: 6px; background: rgba(0,0,0,0.06); border-radius: 10px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated" id="player-progress-bar" style="width: 0%"></div>
      </div>
    </div>

    <!-- Right: Quick Actions -->
    <div class="d-flex align-items-center gap-2" style="width: 120px; justify-content: flex-end;">
      <div class="badge badge-outline text-success d-none d-md-inline-flex" id="player-pulse-indicator">Pulse Active</div>
      <a href="index.php" class="btn btn-sm btn-ghost-primary btn-icon" title="Dashboard">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="5 12 3 12 12 3 21 12 19 12"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/><path d="M9 21v-6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v6"/></svg>
      </a>
    </div>

  </div>
</div>

