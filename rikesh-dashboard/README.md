# Ticket Dashboard & Status Update — Rikesh

**Week:** 2–4 April 2026  
**Developer:** Rikesh  
**Module:** Admin — Ticket Dashboard & Status Update

---

## Overview

This module implements the **Admin Ticket Dashboard** for the College IT Helpdesk system.
IT support staff can view all submitted tickets, filter them, and update each ticket's
status without leaving the dashboard.

---

## Files in This Submission

| File | Description |
|------|-------------|
| `admin/tickets.php` | **Core implementation** — dashboard + inline status update |
| `includes/config.php` | DB connection, helpers (shared, authored by Prakriti) |
| `includes/header.php` | Navigation bar (shared, authored by Sushmita) |
| `includes/footer.php` | Footer (shared, authored by Sushmita) |
| `assets/css/style.css` | Stylesheet (shared, authored by Sushmita) |
| `ticket.php` | Individual ticket view (shared) |
| `database.sql` | DB schema (shared, authored by Prakriti) |

> **Primary contribution:** `admin/tickets.php`

---

## Features Implemented

### 1. Ticket Dashboard (`admin/tickets.php`)
- Displays **all tickets** in a sortable table (newest first)
- Shows: Ticket #, Subject, User, Category, Priority, Status, Submission time
- **Stats cards** at the top: Total / Pending / In Progress / Resolved / Critical

### 2. Status Update (Inline)
- Each row contains a `<select>` with the three required states:
  - `Pending`
  - `In Progress`
  - `Resolved`
- Clicking **Save** submits a POST request that updates the DB and redirects back
- The updated row is **highlighted in green** and scrolled into view automatically

### 3. Filtering
- **Quick-status tabs** — one-click filter for Pending / In Progress / Resolved / All
- **Advanced filters** — search (ticket #, subject, user name), category, priority
- Filter state is preserved across status updates

### 4. Progress Tracking
- Status badges provide at-a-glance progress visibility
- Critical + unresolved tickets are highlighted in red
- Integrates with `ticket.php` (View → button) for full conversation and detail

---

## Setup

1. Import `database.sql` into MySQL.
2. Configure `includes/config.php` (DB credentials, `APP_URL`).
3. Deploy to your PHP server under the `helpdesk/` directory.
4. Log in as admin (`admin@college.edu` / `password`) and navigate to **All Tickets**.

---

## How Status Flow Works

```
User submits ticket
      │
      ▼
  [Pending]  ◄─── Default on creation
      │
      ▼ (Admin updates)
[In Progress] ◄─── Admin is actively working on it
      │
      ▼ (Admin updates)
  [Resolved]  ◄─── Issue fixed; user can see this in My Tickets
```

---

## Git Push Instructions

```bash
git clone <repo-url>
cd helpdesk
git checkout -b feature/rikesh-ticket-dashboard
cp -r rikesh-dashboard/admin/tickets.php admin/tickets.php
git add admin/tickets.php
git commit -m "feat: admin ticket dashboard with inline status update (Rikesh)"
git push origin feature/rikesh-ticket-dashboard
```
