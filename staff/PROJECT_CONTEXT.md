# YakPho AI Staff Project Context

This file serves as a persistent memory of the project environment and configuration. **READ THIS FIRST** when starting a new session.

## Project Overview
- **Name:** YakPho AI Staff Remote Control System
- **Main Admin Path:** `d:\xampp\htdocs\yakpho.com\staff`
- **Web URL:** `https://yakpho.com/staff/`

## Infrastructure
- **Hosting Server:** XAMPP (Windows) for the Admin Panel.
- **Bot Server:** Ubuntu VPS (Remote).
- **Remote Bot Path (VPS):** `/root/esther_aroma_ai/dept1_leads/`
- **Bot Identification:** Always use `bot_key` (e.g. `fb_joiner`) for code logic and database queries. The Thai names/nicknames are for display purposes only.
- **Bridge API:** `api/bot_bridge.php` connects the Hosting to the VPS.
- **API Key:** `yakpho_secret_api_2026_xyz`

## AI Staff Personas (Current)
| Bot Key | Full Name | Nickname (Thai) | Role |
| :--- | :--- | :--- | :--- |
| `yakpho_real_work` | Jonathan H. Mills | คุณจอน (Jon) | Lead-Gen Head |
| `fb_group_scraper` | Linda J. Robinson | คุณลินดา (Linda) | Data Scout |
| `fb_ai_processor` | Dr. Janie Bode | คุณหมอเจนี่ (Dr. Janie) | AI Analytic |
| `fb_joiner` | Mathew J. Adams | คุณแมท (Matt) | Community Relations |
| `fb_page_scraper` | Maya Crist MD (Legacy) | คุณหมอมายา (Dr. Maya) | Nationwide Scout |
| `fb_search_harvester` | James T. Harvester | คุณเจมส์ (James) | กวาดรายชื่อร้านจาก Search (URL Scanner) |
| `fb_deep_scraper` | Sarah V. Deep | คุณซาร่า (Sarah) | เจาะลึกเพจเพื่อดึงเบอร์โทรและอีเมล |

## Operational Commands (on VPS)
- **Run Agent in background:** `nohup python3 bot_remote_agent.py > agent.log 2>&1 &`
- **Service Name (if configured):** `yakpho-bot.service`

## Development Notes
- Always check `api/bot_manager.php` for command execution logic.
- Real-time logs are synchronized from VPS via `bot_remote_agent.py`.
- Personas are defined in `bot_operations.php`.
