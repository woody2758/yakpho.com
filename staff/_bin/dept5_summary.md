# Final Report: Facebook Leads Automation (Dept. 5)

This report summarizes the implementation of the **Facebook Group Scraper** and its integration into the YakPho ecosystem.

## 1. Technical Architecture
- **Engine**: Python 3 + Playwright (Headless Browser) running on a Linux VPS.
- **Storage**: MariaDB (MySQL) with a centralized `facebook_groups` table.
- **API Bridge**: PHP-based REST APIs for secure data exchange between the Bot and the Dashboard.

## 2. Core Scraper Capabilities
- **Multi-Lingual Parsing**: Advanced Regex for Thai (`หมื่น`, `พัน`, `แสน`, `ล้าน`) and English member counts.
- **Smart Data Extraction**:
    - **Group Name**: Cleaned from profile picture prefixes.
    - **Privacy**: Automated filtering for 'Public' vs 'Private' groups.
    - **Activity**: Extracts posts-per-day/week to identify active communities.
- **Anti-Blocking**: Randomized delays (5-15s) and standard search URL usage to mimic human behavior.
- **Diagnostic System**: Automatic screenshot capture (`error_0.png`) on navigation failures for VPS troubleshooting.

## 3. Management Dashboard
- **Dynamic Configuration**: Admin UI to manage keywords, scroll depth, and "Public Only" mode.
- **Advanced UI**: Sortable table by members, score, and activity with preserved pagination.
- **Lead Scoring**: Automated 1-10 suitability score based on keywords, member size, and group activity.

## 4. Suggested Next Phases (For Gemini Consultation)
- **Phase 2: Joining Automation**: Logic for the bot to auto-apply to groups based on the score.
- **Phase 3: Content AI**: Using Gemini to draft personalized "Introduction" posts for each specific group category (Thai Massage vs Spa Business).
- **Phase 4: Sales Funnel**: Tracking the conversion from "Scraped Group" -> "Joined" -> "Successful Outreach".

---
*Status: Production Ready*
